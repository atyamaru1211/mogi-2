<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;

class AttendanceController extends Controller
{
    //勤怠登録画面表示
    public function index()
    {
        // ★現在ログインしているユーザーを取得
        $user = Auth::user();
        //★今日の日付を取得
        $today = Carbon::today();
        //★ユーザーの今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();
        
        //★勤怠ステータスの初期値
        $status = '勤務外';

        //★ステータス判定ロジック
        if ($attendance) {
            if ($attendance->clock_out_time) {
                //★退勤時刻がある場合
                $status = '退勤済';
            } elseif ($attendance->clock_in_time) {
                //★出勤時刻はあるが退勤時刻がない場合→休憩中の判定
                $latestRest = Rest::where('attendance_id', $attendance->id)
                                    ->latest('rest_start_time')
                                    ->first();
                if ($latestRest && is_null($latestRest->rest_end_time)) {
                    //★最新の休憩レコードが有、休憩終了時刻がない場合→同じく休憩中の判定
                    $status = '休憩中';
                } else {
                    //★出勤中　休憩ではない
                    $status = '出勤中';
                }
            }
        }
        //★$statusが勤務外のままなら、出勤レコードなし

        //Viewに渡す
        return view('user.clock', [
            'status' => $status,
            'attendance' => $attendance
        ]);
    }

    //　勤怠登録
    public function store(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        //★アクションの種類をリクエストから取得
        $action = $request->input('action');

        //★今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        switch ($action) {
            case 'punch_in': //★出勤
                //★1日に1回だけ押せるように
                if ($attendance) {
                    return redirect('/attendance');
                }
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today->toDateString(),
                    'clock_in_time' => Carbon::now(),
                ]);
                return redirect('/attendance');

            case 'break_start': //休憩入り
                //勤務中じゃないと休憩できない
                if (!$attendance || $attendance->clock_out_time) {
                    return redirect('/attendance');
                }
                //★休憩中の場合は二重休憩を防ぐ
                $latestRest = Rest::where('attendance_id', $attendance->id)
                                    ->latest('rest_start_time')
                                    ->first();
                if ($latestRest && is_null($latestRest->rest_end_time)) {
                    return redirect('/attendance');
                }
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'rest_start_time' => Carbon::now(),
                ]);
                return redirect('/attendance');
            
            case 'break_end'://休憩戻り
                //休憩中でなければ休憩終了できない
                if (!$attendance || $attendance->clock_out_time) {
                    return redirect('attendance');
                }
                $latestRest = Rest::where('attendance_id', $attendance->id)
                                    ->whereNull('rest_end_time')
                                    ->latest('rest_start_time')
                                    ->first();
                if (!$latestRest) {
                    return redirect('/attendance');
                }
                $latestRest->update(['rest_end_time' => Carbon::now()]);
                return redirect('/attendance');

            case 'punch_out': //退勤
                //勤務中じゃなきゃ退勤できない
                if (!$attendance || $attendance->clock_out_time) {
                    return redirect('/attendance');
                }
                //★休憩中の場合は退勤できない
                $latestRest = Rest::where('attendance_id', $attendance->id)
                                    ->whereNull('rest_end_time')
                                    ->first();
                if ($latestRest) {
                    return redirect('/attendance');
                }

                //★リレーションをロード
                $attendance->load('rests'); 

                //★退勤時刻を記録
                $attendance->update(['clock_out_time' => Carbon::now()]);

                //★合計休憩時間を計算
                $totalRestSeconds = 0;
                foreach ($attendance->rests as $rest) {
                    if ($rest->rest_start_time && $rest->rest_end_time) {
                        $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                    }
                }
                //★time型に変換
                $totalRestTime = gmdate('H:i:s', $totalRestSeconds);
                $attendance->update(['total_rest_time' => $totalRestTime]);

                // 合計休憩時間を記録
                $attendance->update(['total_rest_time' => $totalRestTime]);

                return redirect('/attendance');
            default:
                return redirect('/attendance');
        }
    }

    //★勤怠一覧画面表示
    public function list(Request $request)
    {
        $user = Auth::user();
        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::today();
        //★選択された月の初日と最終日を取得
        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();
        //★ユーザーのその月の勤怠データを取得し、休憩データも同時にロード
        $attendances = Attendance::where('user_id', $user->id)
                                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                                ->with('rests')
                                ->get()
                                ->keyBy(function($item) {
                                    return $item->date->format('Y-m-d');
                                });
        //★月の全日付を生成
        $daysInMonth = [];
        $day = $firstDayOfMonth->copy();
        while ($day->lte($lastDayOfMonth)) {
            $daysInMonth[] = $day->copy();
            $day->addDay();
        }
        //★各日付に対して勤怠データを紐づけ、合計勤務時間を計算
        foreach ($daysInMonth as $date) {
            $dateString = $date->format('Y-m-d');
            $attendance = $attendances->get($dateString);

            if ($attendance) {
                $totalWorkSeconds = 0;
                if ($attendance->clock_in_time && $attendance->clock_out_time) {
                    //★総勤務時間を計算（休憩時間を差し引く前）
                    $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                    // 合計休憩時間を秒で取得 (total_rest_timeがH:i:s形式で保存されている場合)
                    // total_rest_timeが既に秒数で保存されている場合はこの変換は不要
                    // H:i:s 形式から秒数への変換
                    $restTimeParts = explode(':', $attendance->total_rest_time ?? '00:00:00');
                    $totalRestSeconds = ($restTimeParts[0] * 3600) + ($restTimeParts[1] * 60) + $restTimeParts[2];
                    //★総勤務時間から休憩時間を差し引く
                    $attendance->actual_work_seconds = $totalWorkSeconds - $totalRestSeconds;
                    $hours = floor($attendance->actual_work_seconds / 3600);
                    $minutes = floor(($attendance->actual_work_seconds % 3600) / 60);
                    $attendance->actual_work_time = sprintf('%d:%02d', $hours, $minutes);
                } else {
                    $attendance->actual_work_seconds = 0;
                    $attendance->actual_work_time = null;
                }
                //★時間の表記を変更
                if ($attendance->total_rest_time) {
                    $restTimeParts = explode(':', $attendance->total_rest_time);
                    $totalRestMinutes = ($restTimeParts[0] * 60) + $restTimeParts[1];
                    $attendance->formatted_rest_time = sprintf('%d:%02d', floor($totalRestMinutes / 60), $totalRestMinutes % 60);
                } else {
                    $attendance->formatted_rest_time = '';
                }
            }
        }
        //★月のナビゲーションリンク用に、前月と翌月のCarbonインスタンスを作成
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        return view('components.list', [
            'daysInMonth' => $daysInMonth,
            'attendancesMap' => $attendances,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    //★勤怠詳細画面表示
    public function show ($id)
    {
        $user = Auth::user();
        //★指定されたIDの勤怠レコードをユーザーIDと紐づけて取得。休憩情報も。
        $attendance = Attendance::where('user_id', $user->id)
                                ->with('rests')
                                ->findOrFail($id);
        //★休憩時間リストを準備 休憩の開始と終了時刻のペア配列を生成
        $rests = [];
        //★DBから取得した休憩データを時系列でソートして処理
        $sortedRests = $attendance->rests->sortBy('rest_start_time');

        foreach ($sortedRests as $rest) {
            $rests[] = [
                'start' => $rest->rest_start_time ? $rest->rest_start_time->format('H:i') : null,
                'end' => $rest->rest_end_time ? $rest->rest_end_time->format('H:i') : null,
            ];
        }
        //★休憩のフィールドを休憩＋１回分表示。
        $numExistingRests = count($rests);
        if ($numExistingRests === 0 || $numExistingRests > 0) {
            $rests[] = ['start' => null, 'end' => null];
        }
        return view('components.detail', [
            'attendance' => $attendance,
            'rests' => $rests,
        ]);
    }
}
