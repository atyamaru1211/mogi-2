<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;
use App\Http\Requests\StampCorrectionRequest;


class AdminAttendanceController extends Controller
{
    //管理者　勤怠一覧画面
    public function list(Request $request)
    {
        $dateParam = $request->query('date');
        $currentDate = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        $attendances = Attendance::with(['user', 'rests'])
                                ->whereDate('date', $currentDate->toDateString())
                                ->get();

        foreach ($attendances as $attendance) {
            $totalRestSeconds = 0;
            foreach ($attendance->rests as $rest) {
                if ($rest->rest_start_time && $rest->rest_end_time) {
                    $totalRestSeconds += $rest->rest_end_time->diffInSeconds($rest->rest_start_time);
                }
            }
            $attendance->formatted_rest_time = sprintf(
                '%d:%02d',
                floor($totalRestSeconds / 3600),
                floor(($totalRestSeconds % 3600) / 60)
            );

            $actualWorkSeconds = 0;
            if ($attendance->clock_in_time && $attendance->clock_out_time) {
                $totalWorkSeconds = $attendance->clock_out_time->diffInSeconds($attendance->clock_in_time);
                $actualWorkSeconds = $totalWorkSeconds - $totalRestSeconds;
            }
            $attendance->formatted_work_time = sprintf(
                '%d:%02d',
                floor($actualWorkSeconds / 3600),
                floor(($actualWorkSeconds % 3600) / 60)
            );
        }

        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();

        return view('admin.attendance', [
            'currentDate' => $currentDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
            'attendances' => $attendances,
        ]);
    }

    //管理者　勤怠詳細画面表示
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        $rests = [];
        $sortedRests = $attendance->rests->sortBy('rest_start_time');

        foreach ($sortedRests as $rest) {
            $rests[] = [
                'start' => $rest->rest_start_time ? $rest->rest_start_time->format('H:i') : null,
                'end' => $rest->rest_end_time ? $rest->rest_end_time->format('H:i') : null,
            ];
        }

        $rests[] = ['start' => null, 'end' => null];

        return view('admin.detail', [
            'attendance' =>$attendance,
            'rests' => $rests,
            'is_for_approval' => false,
            'attendanceCorrectionRequest' => null,
            'user' => $attendance->user,
        ]);
    }

    //管理者　勤怠修正処理
    public function update(StampCorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $clockInTime = $request->input('clock_in_time');
        $clockOutTime = $request->input('clock_out_time');
        $note = $request->input('note');
        $restsData = $request->input('rests', []);

        $attendance->update([
            'clock_in_time' => $clockInTime ? Carbon::parse($clockInTime) : null,
            'clock_out_time' => $clockOutTime ? Carbon::parse($clockOutTime) : null,
            'note' => $note,
        ]);

        $attendance->rests()->delete();

        $totalRestSeconds = 0;
        foreach ($restsData as $restData) {
            if (!empty($restData['start_time']) || !empty($restData['end_time'])) {
                $restStartTime = $restData['start_time'] ? Carbon::parse($restData['start_time']) : null;
                $restEndTime = $restData['end_time'] ? Carbon::parse($restData['end_time']) : null;

                $rest = new Rest([
                    'attendance_id' => $attendance->id,
                    'rest_start_time' =>$restStartTime,
                    'rest_end_time' =>$restEndTime,
                ]);
                $attendance->rests()->save($rest);

                if ($restStartTime && $restEndTime && $restEndTime->greaterThan($restStartTime)) {
                    $totalRestSeconds += $restEndTime->diffInSeconds($restStartTime);
                }
            }
        }
        $attendance->update([
            'total_rest_time' => gmdate('H:i:s', $totalRestSeconds),
        ]);

        return back()->with('success', '勤怠情報が修正されました');
    }
}
