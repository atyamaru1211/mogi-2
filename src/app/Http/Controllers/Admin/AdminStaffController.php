<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

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
}
