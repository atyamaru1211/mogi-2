<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
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
        $message = null;

        //★ステータス判定ロジック
        if ($attendance) {
            if ($attendance->clock_out_time) {
                //★退勤時刻がある場合
                $status = '退勤済';
                $message = 'お疲れさまでした';
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

        //★セッションにメッセージがあれば取得????
        if (session('status_message')) {
            $message = session('status_message');
        }

        //Viewに渡す
        return view('user.clock', [
            'status' => $status,
            'message' => $message,
            'attendance' => $attendance
        ]);
    }

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
        }
    }
}
