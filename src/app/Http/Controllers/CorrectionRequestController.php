<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestRest;
use App\Http\Requests\StampCorrectionRequest;

class CorrectionRequestController extends Controller
{
    //勤怠詳細画面表示
    public function show ($id)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                                ->with('rests')
                                ->findOrFail($id);
        
        $rests = [];
        $sortedRests = $attendance->rests->sortBy('rest_start_time');

        foreach ($sortedRests as $rest) {
            $rests[] = [
                'start' => $rest->rest_start_time ? $rest->rest_start_time->format('H:i') : null,
                'end' => $rest->rest_end_time ? $rest->rest_end_time->format('H:i') : null,
            ];
        }
        $hasPendingCorrectionRequest = AttendanceCorrectionRequest::where('attendance_id', $id)
                                                                ->where('status', 'pending')
                                                                ->exists();

        $pendingRequest = null;
        if ($hasPendingCorrectionRequest) {
            $pendingRequest = AttendanceCorrectionRequest::where('attendance_id', $id)
                                                        ->where('status', 'pending')
                                                        ->with('requestedRests')
                                                        ->first();
        }

        return view('user.detail', [
            'attendance' => $attendance,
            'rests' => $rests,
            'hasPendingCorrectionRequest' => $hasPendingCorrectionRequest,
            'pendingRequest' => $pendingRequest,
        ]);
    }

    //勤怠修正処理
    public function update (StampCorrectionRequest $request)
    {
        $user = Auth::user();
        $attendanceId = $request->input('id');

        $attendance = Attendance::where('user_id', $user->id)
                                ->findOrFail($attendanceId);

        if (AttendanceCorrectionRequest::where('attendance_id', $attendanceId)
                                        ->where('status', 'pending')
                                        ->exists()) {
            return redirect('/attendance/' . $attendanceId);
        }
        $requested_clock_in_time = Carbon::parse($request->input('clock_in_time'));
        $requested_clock_out_time = Carbon::parse($request->input('clock_out_time'));
        $requested_note = $request->input('note');
        $requested_rests_raw = $request->input('rests', []);

        $filtered_requested_rests = collect($requested_rests_raw)->filter(function($rest) {
            return !empty($rest['start_time']) || !empty($rest['end_time']);
        });

        $requestedTotalRestSeconds = 0;
        foreach ($filtered_requested_rests as $restData) {
            $start = Carbon::parse($restData['start_time']);
            $end = Carbon::parse($restData['end_time']);
            if ($start && $end && $end->greaterThan($start)) {
                $requestedTotalRestSeconds += $end->diffInSeconds($start);
            }
        }
        $requested_total_rest_time = gmdate('H:i:s', $requestedTotalRestSeconds);

        $correctionRequest = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendanceId,
            'requested_date' => $attendance->date,
            'requested_clock_in_time' => $requested_clock_in_time,
            'requested_clock_out_time' => $requested_clock_out_time,
            'requested_total_rest_time' => $requested_total_rest_time,
            'requested_note' => $requested_note,
            'status' => 'pending',
        ]);

        foreach ($filtered_requested_rests as $restData) {
            $start_time = Carbon::parse($restData['start_time']);
            $end_time = Carbon::parse($restData['end_time']);

            AttendanceCorrectionRequestRest::create([
                'attendance_correction_request_id' => $correctionRequest->id,
                'requested_rest_start_time' => $start_time,
                'requested_rest_end_time' => $end_time,
            ]);
        }
        return redirect('/attendance/' . $attendanceId);
    }

    //申請一覧画面表示
    public function requestList(Request $request)
    {
        $user = Auth::user();

        $activeTab = $request->query('tab', 'pending');
        $requests = collect();
        $statusText = '';

        if ($activeTab === 'pending') {
            $requests = AttendanceCorrectionRequest::where('user_id', $user->id)
                                                    ->where('status', 'pending')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
            $statusText = '承認待ち';
        } else {
            $requests = AttendanceCorrectionRequest::where('user_id', $user->id)
                                                    ->where('status', 'approved')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
            $statusText = '承認済み';
        }
        
        return view('components.request_list', [
            'requests' => $requests,
            'activeTab' => $activeTab,
            'statusText' => $statusText,
            'is_admin_view' => false,
        ]);
    }
}
