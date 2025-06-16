<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Illuminate\Support\Facades\Session;
use App\Providers\RouteServiceProvider; 
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminAttendanceController;



//Mailhogへのルート
Route::get('/mailhog', function() {
    return redirect('http://localhost:8025');
});

// ログインルートに'email'ミドルウェアを適用
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('email');

// 会員登録ルート
Route::post('/register', [RegisteredUserController::class, 'store']);

// 勤怠画面 - 認証済みかつメール認証済みユーザーのみアクセス可能
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'index']);
});

//メール認証誘導画面 
Route::get('/email/verify', function() {

    $user = Auth::user();
    if ($user && Session::has('unauthenticated_user') && $user->id === Session::get('unauthenticated_user')->id && !$user->hasVerifiedEmail()) {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('verification.notice');
    }

    return view('auth.verify');
})->name('verification.notice');

// メール認証メール再送
Route::post('/email/verification-notification', function (Request $request) {

    $userToSendEmail = null;
    // ログイン中のユーザーがいればそのユーザーにメールを送る
    if (Auth::check()) {
        $userToSendEmail = Auth::user();
    }
    // ログインしていなければセッションのunauthenticated_userを使う
    elseif (Session::has('unauthenticated_user')) {
        $tempUser = Session::get('unauthenticated_user');
        // セッションから取得したユーザーモデルが古い可能性があるので、データベースから最新のものを取得
        $userToSendEmail = User::find($tempUser->id);
        if (!$userToSendEmail) {
            return redirect()->route('register')->with('error', 'ユーザー情報が見つかりません。再度会員登録をしてください。');
        }
    } else {
        // どちらにもユーザー情報がない場合
        return redirect()->route('register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }

    // ユーザーが既にメール認証済みの場合
    if ($userToSendEmail->hasVerifiedEmail()) {
        if (!Auth::check()) {
            Auth::login($userToSendEmail);
        }
        Session::forget('unauthenticated_user');
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    // 認証メールを再送
    $userToSendEmail->sendEmailVerificationNotification();
    session()->flash('status', '新しい認証メールを送信しました。メールをご確認ください。');

    return back();
})->name('verification.send');

// メール認証リンククリック
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id) {
    $user = User::find($id);

    // ユーザーが見つからない、またはハッシュが不正な場合はエラーまたはリダイレクト
    if (!$user || ! hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
        return redirect('/register')->with('error', 'メール認証リンクが無効です。');
    }

    // 既に認証済みであれば、そのままログインしてリダイレクト
    if ($user->hasVerifiedEmail()) {
        if (!Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
        }
        Session::forget('unauthenticated_user');
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    // メールを認証済みとしてマークし、Verifiedイベントを発火
    $user->markEmailAsVerified(); 
    event(new Verified($user));

    // メール認証後、明示的にログインさせる
    if (!Auth::check() || Auth::id() !== $user->id) { // 既にログインしているユーザーと異なる場合のみログイン
        Auth::login($user);
    }

    Session::forget('unauthenticated_user');

    return redirect()->intended(RouteServiceProvider::HOME);

})->middleware(['signed'])->name('verification.verify');


// --- 管理者向けルート ---
Route::prefix('admin')->group(function () {
    // ... 管理者ログインルート (AdminLoginController 関連) ...
    Route::get('/login', [AdminLoginController::class, 'showLoginForm']);
    Route::post('/login', [AdminLoginController::class, 'login']);
    // 認証必須の管理者向けルート
    Route::middleware('auth:admin')->group(function () {
        // 管理者ダッシュボード（オプション）
        Route::get('/dashboard', function () {
            return view('admin.dashboard'); // 仮のビュー
        });

        // ★勤怠一覧画面（管理者）★
        Route::get('/attendance/list', [AdminAttendanceController::class, 'list']);

        Route::post('/logout', [AdminLoginController::class, 'logout']);
    });
});