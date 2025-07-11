<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\DB;

class AdminCorrectionRequestController extends Controller
{
    //管理者　申請一覧画面
    public function requestList(Request $request)
    {
        $activeTab = $request->query('tab', 'pending');

        if ($activeTab === 'pending') {
            $requests = AttendanceCorrectionRequest::with('user')
                                                    ->where('status', 'pending')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
            $statusText = '承認待ち';
        } else {
            $requests = AttendanceCorrectionRequest::with('user')
                                                    ->where('status', 'approved')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
            $statusText = '承認済み';
        }

        return view('components.request_list', [
            'requests' => $requests,
            'activeTab' => $activeTab,
            'statusText' => $statusText,
            'is_admin_view' => true,
        ]);
    }

    //管理者　修正申請承認画面
    public function showApprovalForm(AttendanceCorrectionRequest $attendance_correct_request)
    {
        $attendance_correct_request->load('user', 'requestedRests');
        $user = $attendance_correct_request->user;

        $requestedRestsForDisplay = [];
        foreach ($attendance_correct_request->requestedRests->sortBy('requested_rest_start_time') as $rest) {
            $requestedRestsForDisplay[] = [
                'start' => $rest->requested_rest_start_time ? $rest->requested_rest_start_time->format('H:i') : null,
                'end' => $rest->requested_rest_end_time ? $rest->requested_rest_end_time->format('H:i') : null,
            ];
        }
        $requestedRestsForDisplay[] = ['start' => null, 'end' => null];

        return view('admin.detail', [
            'attendanceCorrectionRequest' => $attendance_correct_request,
            'user' => $user,
            'is_for_approval' => true,
            'attendance' => null,
            'rests' => [],
            'requestedRestsForApproval' => $requestedRestsForDisplay,
        ]);
    }

    //修正申請を承認
    public function approve(Request $request, AttendanceCorrectionRequest $attendance_correct_request)
    {
        DB::transaction(function () use ($attendance_correct_request) {
            $attendance_correct_request->status = 'approved';
            $attendance_correct_request->save();

            $attendance = $attendance_correct_request->attendance;

            if ($attendance) {
                $attendance->clock_in_time = $attendance_correct_request->requested_clock_in_time;
                $attendance->clock_out_time = $attendance_correct_request->requested_clock_out_time;
                $attendance->note = $attendance_correct_request->requested_note;
                $attendance->save();

                $attendance->rests()->delete();

                foreach ($attendance_correct_request->requestedRests as $requestedRest) {
                    $attendance->rests()->create([
                        'rest_start_time' => $requestedRest->requested_rest_start_time,
                        'rest_end_time' => $requestedRest->requested_rest_end_time,
                    ]);
                }
            }
        });

        return redirect('/stamp_correction_request/approve/' . $attendance_correct_request->id)
            ->with('success', '修正申請を承認しました');
    }
}
