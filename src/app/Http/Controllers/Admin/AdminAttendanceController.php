<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Http\Requests\StampCorrectionRequest;


class AdminAttendanceController extends Controller
{
    //★管理者　勤怠一覧画面
    public function list(Request $request)
    {
        //★日付の初期設定
        $dateParam = $request->query('date');
        $currentDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        //★全ユーザーの勤怠データを取得
        $attendances = Attendance::with(['user', 'rests'])
                                ->whereDate('date', $currentDate->toDateString())
                                ->get();

        //★各勤怠レコードに対して休憩時間と合計勤務時間を計算
        foreach ($attendances as $attendance) {
            //★合計休憩時間の計算
            $totalRestSeconds = 0;
            foreach ($attendance->rests as $rest) {
                if ($rest->rest_start_time && $rest->rest_end_time) {
                    $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                }
            }
            //★休憩時間を'H:i'形式に変える
            $attendance->formatted_rest_time = sprintf(
                '%d:%02d',
                floor($totalRestSeconds / 3600),
                floor(($totalRestSeconds % 3600) / 60)
            );

            //★合計勤務時間を計算
            $actualWorkSeconds = 0;
            if ($attendance->clock_in_time && $attendance->clock_out_time) {
                $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                $actualWorkSeconds = $totalWorkSeconds - $totalRestSeconds;
            }
            //★合計勤務時間をH:i形式に変える
            $attendance->formatted_work_time = sprintf(
                '%d:%02d',
                floor($actualWorkSeconds / 3600),
                floor(($actualWorkSeconds % 3600) / 60)
            );
        }

        //日付ナビ用の前後日付を計算
        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();

        return view('admin.attendance', [
            'currentDate' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'attendances' => $attendances,
        ]);
    }

    //★管理者　勤怠詳細画面表示
    public function show($id)
    {
        //★指定されたIDの勤怠情報をユーザー情報と休憩情報も一緒に取得
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        //★休憩リストを準備　フォーム表示用
        $rests = [];
        $sortedRests = $attendance->rests->sortBy('rest_start_time');

        foreach ($sortedRests as $rest) {
            $rests[] = [
                'start' => $rest->rest_start_time ? $rest->rest_start_time->format('H:i') : null,
                'end' => $rest->rest_end_time ? $rest->rest_end_time->format('H:i') : null,
            ];
        }

        //★新しいきゅけいを追加できるように空のフィールドを1つ追加
        $rests[] = ['start' => null, 'end' => null];

        return view('admin.detail', [
            'attendance' =>$attendance,
            'rests' => $rests,
            'is_for_approval' => false,
            'attendanceCorrectionRequest' => null,
            'user' => $attendance->user,
        ]);
    }

    //★管理者　勤怠修正処理
    public function update(StampCorrectionRequest $request, $id)
    {
        //★修正対象の勤怠レコードを取得
        $attendance = Attendance::findOrFail($id);

        //フォームからの入力値を取得
        $clockInTime = $request->input('clock_in_time');
        $clockOutTime = $request->input('clock_out_time');
        $note = $request->input('note');
        $restsData = $request->input('rests', []);

        //★勤怠レコードの更新
        $attendance->update([
            'clock_in_time' => $clockInTime ? Carbon::parse($clockInTime) : null,
            'clock_out_time' => $clockOutTime ? Carbon::parse($clockOutTime) : null,
            'note' => $note,
        ]);

        //★休憩レコードの更新・作成・削除。まずは既存の休憩をすべて削除し、新しい休憩を全て再作成する方法が最もシンプルで安全。
        $attendance->rests()->delete();

        $totalRestSeconds = 0;
        foreach ($restsData as $restData) {
            //開始時刻もしくは終了時刻のいずれか一方が入力されていれば有効な休憩とみなす。StampCorrectionRequest.phpでバリデーション済みなので。
            if (!empty($restData['start_time']) || !empty($restData['end_time'])) {
                $restStartTime = $restData['start_time'] ? Carbon::parse($restData['start_time']) : null;
                $restEndTime = $restData['end_time'] ? Carbon::parse($restData['end_time']) : null;

                $rest = new Rest([
                    'attendance_id' => $attendance->id,
                    'rest_start_time' =>$restStartTime,
                    'rest_end_time' =>$restEndTime,
                ]);
                $attendance->rests()->save($rest);

                //★合計休憩時間を再計算
                if ($restStartTime && $restEndTime && $restEndTime->greaterThan($restStartTime)) {
                    $totalRestSeconds += $restEndTime->diffInSeconds($restStartTime);
                }
            }
        }
        //★totla_rest_timeをattendancesテーブルに保存
        $attendance->update([
            'total_rest_time' => gmdate('H:i:s', $totalRestSeconds),
        ]);

        //★修正後、管理者の勤怠詳細画面にリダイレクト
        return back()->with('success', '勤怠情報が修正されました');
    }
}
