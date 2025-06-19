<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestRest;
use App\Http\Requests\StampCorrectionRequest;

class CorrectionRequestController extends Controller
{
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
        //★承認待ちの修正申請があるかチェック
        $hasPendingCorrectionRequest = AttendanceCorrectionRequest::where('attendance_id', $id)
                                                                ->where('status', 'pending')
                                                                ->exists();

        //★note=nullの修正
        $pendingRequest = null;
        if ($hasPendingCorrectionRequest) {
            //申請済の承認がある場合、その申請内容も取得しロード
            $pendingRequest = AttendanceCorrectionRequest::where('attendance_id', $id)
                                                        ->where('status', 'pending')
                                                        ->with('requestedRests')
                                                        ->first();
        }

        return view('components.detail', [
            'attendance' => $attendance,
            'rests' => $rests,
            'hasPendingCorrectionRequest' => $hasPendingCorrectionRequest,
            'pendingRequest' => $pendingRequest,
        ]);
    }

    //★勤怠修正処理
    public function update (StampCorrectionRequest $request)
    {
        $user = Auth::user();
        $attendanceId = $request->input('id');

        //★対象の勤怠レコードを再度取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->findOrFail($attendanceId);

        //★すでに承認待ちの申請じゃないか再チェック
        if (AttendanceCorrectionRequest::where('attendance_id', $attendanceId)
                                        ->where('status', 'pending')
                                        ->exists()) {
            //★リダイレク
            return redirect('/attendance/' . $attendanceId);
        }
        //★まだ申請されていない場合
        $requested_clock_in_time = Carbon::parse($request->input('clock_in_time'));
        $requested_clock_out_time = Carbon::parse($request->input('clock_out_time'));
        $requested_note = $request->input('note');
        $requested_rests_raw = $request->input('rests', []);

        //★申請された休憩データの空のものを除外
        $filtered_requested_rests = collect($requested_rests_raw)->filter(function($rest) {
            return !empty($rest['start_time']) || !empty($rest['end_time']);
        });

        //★requested_total_rest_timeを計算(time型)
        $requestedTotalRestSeconds = 0;
        foreach ($filtered_requested_rests as $restData) {
            $start = Carbon::parse($restData['start_time']);
            $end = Carbon::parse($restData['end_time']);
            if ($start && $end && $end->greaterThan($start)) {
                $requestedTotalRestSeconds += $end->diffInSeconds($start);
            }
        }
        $requested_total_rest_time = gmdate('H:i:s', $requestedTotalRestSeconds); //★time型に変換

        //★修正申請レコードを作成
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

        //★休憩申請レコードを個別に作成（attendance_correction_request_restsテーブル）
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

    //★申請一覧画面表示
    public function requestList(Request $request)
    {
        $user = Auth::user();
        $pendingRequests = AttendanceCorrectionRequest::where('user_id', $user->id)
                                                    ->where('status', 'pending')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
        $approvedRequests = AttendanceCorrectionRequest::where('user_id', $user->id)
                                                    ->where('status', 'approved')
                                                    ->with('user')
                                                    ->orderBy('created_at', 'desc')
                                                    ->get();
        
        return view('components.request_list', [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ]);
    }
}
