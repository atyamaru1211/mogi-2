<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RegisteredUserController;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


//Mailhogへのルート
Route::get('/mailhog', function() {
    return redirect('http://localhost:8025');
});


//ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('redirect_if_email_unverified');

//会員登録
Route::post('/register', [RegisteredUserController::class, 'store']);


//メール認証誘導画面
Route::get('/email/verify', function() {
    return view('pages.auth_verify');
})->name('verification.notice');

//認証メールの再送
Route::post('/email/verification-notification', function (Request $request) {
    //★registered_emailを取得。これはRegisteredUserControllerで作られてる
    $email = Session::get('registered_email');
    //★もしセッションにメアドがなかったら会員登録画面に戻す
    if (!$email) {
        return redirect('/register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }
    //★そのメアドを使って、データベースからユーザーを探す
    $user = User::where('email', $email)->first();
    //★もしデータベースに見つからなかったら会員登録画面に戻す
    if (!$user) {
        return redirect('/register')->with('error', '指定されたメールアドレスのユーザーが見つかりませんでした。再度会員登録をしてください。');
    }
    //★ 既に認証済みの場合はログイン画面に行く
    if ($user->hasVerifiedEmail()) {
        return redirect('/login')->with('status', 'メールアドレスは既に認証済みです。ログインしてください。');
    }
    //★認証メールを再送する
    $user->sendEmailVerificationNotification();
    //★resentという情報を一時的にセッションに保存。メールが再送されたことをビューに伝えるためによく使われる手法
    Session::flash('resent', true);
    //★メール認証誘導画面に戻り(まあ元々動かないんだけど)メッセージを表示させる
    return back()->with('status', '新しい認証メールを送信しました。メールをご確認ください。');
})->name('verification.send');


//勤怠画面
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'index']);
});