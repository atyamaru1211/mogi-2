<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // ★この行を追加！

class AdminCorrectionRequestController extends Controller
{
    //★管理者　申請一覧画面
    public function requestList(Request $request)
    {
        //最初は承認待ちのタブを表示
        $activeTab = $request->query('tab', 'pending');

        //★承認待ちの申請取得
        if ($activeTab === 'pending') {
            $requests = AttendanceCorrectionRequest::with('user')
                                                    ->where('status', 'pending')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
            $statusText = '承認待ち';
        } else {
            //★承認済みの申請取得
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

    //★管理者　修正申請承認画面
    public function showApprovalForm(AttendanceCorrectionRequest $attendance_correct_request)
    {
        //★requestedRestsのリレーションをロード
        $attendance_correct_request->load('user', 'requestedRests');
        $user = $attendance_correct_request->user;

        //★申請された休憩と、常に一つ追加の空の休憩項目を準備
        $requestedRestsForDisplay = [];
        foreach ($attendance_correct_request->requestedRests->sortBy('requested_rest_start_time') as $rest) {
            $requestedRestsForDisplay[] = [
                'start' => $rest->requested_rest_start_time ? $rest->requested_rest_start_time->format('H:i') : null,
                'end' => $rest->requested_rest_end_time ? $rest->requested_rest_end_time->format('H:i') : null,
            ];
        }
        //★常に一つ、空の休憩項目を追加
        $requestedRestsForDisplay[] = ['start' => null, 'end' => null];

        return view('admin.detail', [
            'attendanceCorrectionRequest' => $attendance_correct_request,
            'user' => $user,
            'is_for_approval' => true,
            'attendance' => null,
            'rests' => [],//★これは直接修正時にのみ使用するため空。
            'requestedRestsForApproval' => $requestedRestsForDisplay,
        ]);
    }

    //★修正申請を承認
    public function approve(Request $request, AttendanceCorrectionRequest $attendance_correct_request)
    {
        //★データベースト書き換え開始
        DB::transaction(function () use ($attendance_correct_request) {
            //★１、修正申請のステータスを承認済に変更
            $attendance_correct_request->status = 'approved';
            $attendance_correct_request->save();

            //★２，一般ユーザーの勤怠情報を更新
            $attendance = $attendance_correct_request->attendance;

            if ($attendance) {
                $attendance->clock_in_time = $attendance_correct_request->requested_clock_in_time;
                $attendance->clock_out_time = $attendance_correct_request->requested_clock_out_time;
                $attendance->note = $attendance_correct_request->requested_note;
                $attendance->save();

                //★３，勤怠の休憩情報を更新　既存の休憩を全て削除し、申請された休憩を登録する
                $attendance->rests()->delete();

                foreach ($attendance_correct_request->requestedRests as $requestedRest) {
                    $attendance->rests()->create([
                        'rest_start_time' => $requestedRest->requested_rest_start_time,
                        'rest_end_time' => $requestedRest->requested_rest_end_time,
                    ]);
                }
            }
        });

        //★承認後、修正申請詳細画面にリダイレクト、成功メッセージ
        return redirect('/stamp_correction_request/approve/' . $attendance_correct_request->id)
            ->with('success', '修正申請を承認しました');
    }
}
