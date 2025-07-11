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
        $user = Auth::user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->with('rests')
                                ->first();
        
        $status = '勤務外';

        if ($attendance) {
            if ($attendance->clock_out_time) {
                $status = '退勤済';
            } elseif ($attendance->clock_in_time) {
                $latestRest = $attendance->rests->sortByDesc('rest_start_time')->first();
                if ($latestRest && is_null($latestRest->rest_end_time)) {
                    $status = '休憩中';
                } else {
                    $status = '出勤中';
                }
            }
        }
        
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

        $action = $request->input('action');

        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', $today)
                                ->first();

        switch ($action) {
            case 'punch_in':
                if ($attendance) {
                    return redirect('/attendance');
                }
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today->toDateString(),
                    'clock_in_time' => Carbon::now(),
                ]);
                return redirect('/attendance');

            case 'rest_start':
                if (!$attendance || $attendance->clock_out_time) {
                    return redirect('/attendance');
                }
                $activeRest = Rest::where('attendance_id', $attendance->id)
                                    ->whereNull('rest_end_time')
                                    ->first();
                if ($activeRest) {
                    return redirect('/attendance');
                }
                Rest::create([
                    'attendance_id' => $attendance->id,
                    'rest_start_time' => Carbon::now(),
                ]);
                return redirect('/attendance');
            
            case 'rest_end':
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

            case 'punch_out':
                if (!$attendance || $attendance->clock_out_time) {
                    return redirect('/attendance');
                }
                $latestRest = Rest::where('attendance_id', $attendance->id)
                                    ->whereNull('rest_end_time')
                                    ->first();
                if ($latestRest) {
                    return redirect('/attendance');
                }

                $attendance->load('rests'); 

                $attendance->update(['clock_out_time' => Carbon::now()]);

                $totalRestSeconds = 0;
                foreach ($attendance->rests as $rest) {
                    if ($rest->rest_start_time && $rest->rest_end_time) {
                        $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                    }
                }
                $totalRestTime = gmdate('H:i:s', $totalRestSeconds);
                $attendance->update(['total_rest_time' => $totalRestTime]);

                return redirect('/attendance');
            default:
                return redirect('/attendance');
        }
    }

    //勤怠一覧画面表示
    public function list(Request $request)
    {
        $user = Auth::user();

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
        ]);
    }
}
