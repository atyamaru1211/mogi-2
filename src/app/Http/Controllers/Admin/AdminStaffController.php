<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminStaffController extends Controller
{
    //★スタッフ一覧画面表示
    public function list(Request $request)
    {
        //★全てのユーザーを取得
        $users = User::orderBy('name')->get();

        return view('admin.staff', ['users' => $users]);
    }

    //★スタッフ別勤怠一覧表示
    public function attendanceList(Request $request, $id)
    {
        //★指定されたIDのユーザーがいるか確認
        $user = User::findOrFail($id);

        //★月次勤怠表示のための基準月を設定
        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::today();

        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();

        //★指定ユーザーの勤怠データを取得
        $attendances = Attendance::where('user_id', $user->id)
                                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                                ->with('rests')
                                ->get()
                                ->keyBy(function($item) {
                                    return $item->date->format('Y-m-d');
                                });
            
        //★月の日付を生成
        $daysInMonth = [];
        $day = $firstDayOfMonth->copy();
        while ($day->lte($lastDayOfMonth)) {
            $daysInMonth[] = $day->copy();
            $day->addDay();
        }

        //★各日付の勤怠情報を整形
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
            'userForDisplay' => $user,//★特定のユーザー情報をビューに渡す
            'is_admin_view' => true,//★管理者のビューであることを示す
            'targetUserId' => $user->id,//★現在ターゲットにしているユーザーIDを渡す
        ]);
    }

    //★CSV出力機能
    public function export(Request $request, $id)
    {
        $user = User::findOrFail($id);

        //★対象月を設定（attendanceListと同様、monthパラメータが現在の月を使用）
        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::today();
        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();

        //★勤怠データを取得
        $attendanceDate = Attendance::where('user_id', $user->id)
                                    ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                                    ->with('rests')
                                    ->orderBy('date', 'asc')
                                    ->get();

        //★CSVヘッダーの定義
        //★UIのカラム名に合わせる
        $csvHeader = [
            '日付', '曜日', '出勤', '退勤', '休憩', '合計'
        ];

        //★ファイル名→ユーザー名と月を含める
        $fileName = sprintf('%sさんの勤怠_%s.csv', $user->name, $currentMonth->format('Y_m'));

        $response = new StreamedResponse(function () use ($csvHeader, $attendanceDate, $firstDayOfMonth, $lastDayOfMonth) {
            $file = fopen('php://output', 'w');

            // ヘッダーの文字コードをSJIS-winに変換
            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);
            fputcsv($file, $csvHeader); // 変換後のヘッダーを書き込む

            //★月の全日付を網羅し、勤怠データがない日は空白とする
            $day = $firstDayOfMonth->copy();
            $attendancesMap = $attendanceDate->keyBy(function($item) {
                return $item->date->format('Y-m-d');
            });

            while ($day->lte($lastDayOfMonth)) {
                $dateString = $day->format('Y-m-d');
                $attendance = $attendancesMap->get($dateString);

                $row = [
                    $day->format('m/d'),//★日付
                    ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek],//★曜日
                    '',//★出勤時間
                    '',//★退勤時間
                    '',//★休憩時間
                    '',//★合計勤務時間
                ];

                if ($attendance) {
                    $row[2] = $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '';//★出勤
                    $row[3] = $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '';//★退勤

                    //★休憩時間の計算
                    $totalRestSeconds = 0;
                    foreach ($attendance->rests as $rest) {
                        if ($rest->rest_start_time && $rest->rest_end_time) {
                            $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                        }
                    }
                    $row[4] = sprintf('%d:%02d', floor($totalRestSeconds / 3600), floor(($totalRestSeconds % 3600) / 60));//★休憩

                    //★合計勤務時間の計算
                    $actualWorkSeconds = 0;
                    if ($attendance->clock_in_time && $attendance->clock_out_time) {
                        $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                        $actualWorkSeconds = $totalWorkSeconds - $totalRestSeconds;
                    }
                    $row[5] = sprintf('%d:%02d', floor($actualWorkSeconds / 3600), floor(($actualWorkSeconds % 3600) / 60));//★合計
                }

                //★Windows　Excelでの文字化け対策
                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($file, $row);

                $day->addDay();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=SJIS-win',//★SJIS-winを指定
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);

        return $response;
    }
}
