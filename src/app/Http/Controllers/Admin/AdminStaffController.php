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
    //スタッフ一覧画面表示
    public function list(Request $request)
    {
        $users = User::orderBy('name')->get();

        return view('admin.staff', ['users' => $users]);
    }

    //スタッフ別勤怠一覧表示
    public function attendanceList(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::today();

        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)
                                ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                                ->with('rests')
                                ->get()
                                ->keyBy(function($item) {
                                    return $item->date->format('Y-m-d');
                                });
            
        $daysInMonth = [];
        $day = $firstDayOfMonth->copy();
        while ($day->lte($lastDayOfMonth)) {
            $daysInMonth[] = $day->copy();
            $day->addDay();
        }

        foreach ($daysInMonth as $date) {
            $dateString = $date->format('Y-m-d');
            $attendance = $attendances->get($dateString);

            if ($attendance) {
                $totalWorkSeconds = 0;
                if ($attendance->clock_in_time && $attendance->clock_out_time) {
                    $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                    $restTimeParts = explode(':', $attendance->total_rest_time ?? '00:00:00');
                    $totalRestSeconds = ($restTimeParts[0] * 3600) + ($restTimeParts[1] * 60) + $restTimeParts[2];
                    $attendance->actual_work_seconds = $totalWorkSeconds - $totalRestSeconds;
                    $hours = floor($attendance->actual_work_seconds / 3600);
                    $minutes = floor(($attendance->actual_work_seconds % 3600) / 60);
                    $attendance->actual_work_time = sprintf('%d:%02d', $hours, $minutes);
                } else {
                    $attendance->actual_work_seconds = 0;
                    $attendance->actual_work_time = null;
                }
                if ($attendance->total_rest_time) {
                    $restTimeParts = explode(':', $attendance->total_rest_time);
                    $totalRestMinutes = ($restTimeParts[0] * 60) + $restTimeParts[1];
                    $attendance->formatted_rest_time = sprintf('%d:%02d', floor($totalRestMinutes / 60), $totalRestMinutes % 60);
                } else {
                    $attendance->formatted_rest_time = '';
                }
            }
        }
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        return view('components.list', [
            'daysInMonth' => $daysInMonth,
            'attendancesMap' => $attendances,
            'currentMonth' => $currentMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'userForDisplay' => $user,
            'is_admin_view' => true,
            'targetUserId' => $user->id,
        ]);
    }

    //CSV出力機能
    public function export(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::today();
        $firstDayOfMonth = $currentMonth->copy()->startOfMonth();
        $lastDayOfMonth = $currentMonth->copy()->endOfMonth();

        $attendanceDate = Attendance::where('user_id', $user->id)
                                    ->whereBetween('date', [$firstDayOfMonth, $lastDayOfMonth])
                                    ->with('rests')
                                    ->orderBy('date', 'asc')
                                    ->get();

        $csvHeader = [
            '日付', '曜日', '出勤', '退勤', '休憩', '合計'
        ];

        $fileName = sprintf('%sさんの勤怠_%s.csv', $user->name, $currentMonth->format('Y_m'));

        $response = new StreamedResponse(function () use ($csvHeader, $attendanceDate, $firstDayOfMonth, $lastDayOfMonth) {
            $file = fopen('php://output', 'w');

            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);
            fputcsv($file, $csvHeader);

            $day = $firstDayOfMonth->copy();
            $attendancesMap = $attendanceDate->keyBy(function($item) {
                return $item->date->format('Y-m-d');
            });

            while ($day->lte($lastDayOfMonth)) {
                $dateString = $day->format('Y-m-d');
                $attendance = $attendancesMap->get($dateString);

                $row = [
                    $day->format('m/d'),
                    ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek],
                    '',
                    '',
                    '',
                    '',
                ];

                if ($attendance) {
                    $row[2] = $attendance->clock_in_time ? $attendance->clock_in_time->format('H:i') : '';
                    $row[3] = $attendance->clock_out_time ? $attendance->clock_out_time->format('H:i') : '';

                    $totalRestSeconds = 0;
                    foreach ($attendance->rests as $rest) {
                        if ($rest->rest_start_time && $rest->rest_end_time) {
                            $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                        }
                    }
                    $row[4] = sprintf('%d:%02d', floor($totalRestSeconds / 3600), floor(($totalRestSeconds % 3600) / 60));

                    $actualWorkSeconds = 0;
                    if ($attendance->clock_in_time && $attendance->clock_out_time) {
                        $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                        $actualWorkSeconds = $totalWorkSeconds - $totalRestSeconds;
                    }
                    $row[5] = sprintf('%d:%02d', floor($actualWorkSeconds / 3600), floor(($actualWorkSeconds % 3600) / 60));
                }

                mb_convert_variables('SJIS-win', 'UTF-8', $row);
                fputcsv($file, $row);

                $day->addDay();
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=SJIS-win',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);

        return $response;
    }
}
