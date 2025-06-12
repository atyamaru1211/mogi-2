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
//use App\Http\Requests\LoginRequest;

use App\Providers\RouteServiceProvider; 
use Illuminate\Auth\Events\Verified;

use Illuminate\Support\Facades\Log; // ★ログ追加用★


//Mailhogへのルート
Route::get('/mailhog', function() {
    return redirect('http://localhost:8025');
});


// ログインルートに'email'ミドルウェアを適用
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('email');

// ログアウトルート
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');


// 勤怠画面 - 認証済みかつメール認証済みユーザーのみアクセス可能
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'index']);
});

//メール認証誘導画面
Route::get('/email/verify', function() {
    return view('pages.auth_verify');
})->name('verification.notice');

/*//メール再送
Route::post('/email/verification-notification', function (Request $request) {
    Log::info('Email Verification Notification Route: Accessed.');

    $user = session()->get('unauthenticated_user');

    // ★★★ここから追加・修正★★★
    if (!$user) {
        Log::warning('Email Verification Notification Route: No unauthenticated_user found in session. Redirecting to register.');
        return redirect()->route('register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }
    Log::info('Email Verification Notification Route: unauthenticated_user found. User ID: ' . $user->id);
    // ★★★ここまで追加・修正★★★


    if ($user->hasVerifiedEmail()) {
        Auth::login($user);
        session()->forget('unauthenticated_user');
        return redirect(RouteServiceProvider::HOME)->with('status', 'メールアドレスは既に認証済みです。勤怠登録画面へようこそ。');
    }
    $user->sendEmailVerificationNotification(); // ユーザーに認証メールを送信

    // ★★★ここから追加・修正★★★
    Log::info('Email Verification Notification Route: Email sent for User ID: ' . $user->id);
    session()->put('resent', true); // 'resent'セッションをセット
    Log::info('Email Verification Notification Route: "resent" session set to true.');
    // ★★★ここまで追加・修正★★★

    return back(); // 画面に戻る
})->name('verification.send');*/

// ★★★最も確実なメール認証リンク処理のルート★★★
// EmailVerificationRequest を使わず、直接ユーザーを特定してログインさせる
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) { // ★EmailVerificationRequest を Request に変更！★
/*    Log::info('Manual Email Verification Route: STARTED.');
    Log::info('Manual Email Verification Route: Request URL: ' . $request->fullUrl());
    Log::info('Manual Email Verification Route: Params - ID: ' . $request->route('id') . ', Hash: ' . $request->route('hash'));

    // ユーザーをIDで検索
    $user = User::find($request->route('id'));

    // ユーザーが存在しないか、ハッシュが一致しない場合
    // hasValidSignature() は署名検証用なので、ここではハッシュの検証とログイン処理に集中
    if (!$user || !hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        Log::error('Manual Email Verification Route: Invalid user or hash. User ID: ' . ($user ? $user->id : 'N/A') . ', Provided Hash: ' . $request->route('hash') . ', Expected Hash: ' . ($user ? sha1($user->getEmailForVerification()) : 'N/A') . '.');
        return redirect()->route('login')->with('error', '認証リンクが無効です。'); // ログイン画面にエラーメッセージ付きでリダイレクト
    }

    // ユーザーが既に認証済みの場合
    if ($user->hasVerifiedEmail()) {
        Log::info('Manual Email Verification Route: User ' . $user->id . ' already verified. Logging in and redirecting to HOME.');
        Auth::login($user); // 既に認証済みでも念のためログイン状態にする
        session()->forget('unauthenticated_user'); // 認証済みなのでセッションを削除
        return redirect()->intended(RouteServiceProvider::HOME)->with('status', 'メールアドレスは既に認証済みです。');
    }

    // ここでユーザーをログインさせる (これが最も重要)
    Auth::login($user);
    Log::info('Manual Email Verification Route: User ' . $user->id . ' explicitly logged in.');
    Log::info('Manual Email Verification Route: Auth check (AFTER login): ' . (Auth::check() ? 'true' : 'false') . ' (User ID: ' . (Auth::check() ? Auth::id() : 'N/A') . ')');

    // メールを認証済みにする
    $user->markEmailAsVerified();
    event(new Verified($user)); // Verified イベントを発火させる
    Log::info('Manual Email Verification Route: User ' . $user->id . ' email marked as verified and Verified event fired.');
*/
    Log::info('Email Verification Route: STARTED - Attempting to verify email for user ID: ' . ($request->user() ? $request->user()->id : 'N/A') . '.');
    Log::info('Email Verification Route: Auth check (BEFORE fulfill): ' . (Auth::check() ? 'true' : 'false') . ' (User ID: ' . (Auth::check() ? Auth::id() : 'N/A') . ')');

    // EmailVerificationRequest の fulfill() メソッドが、
    // ユーザーを認証済みにマークし、必要であればイベントを発火させます。
    // Auth::login() はここでは行いません。
    $request->fulfill();

    // 認証完了後、ユーザーをログインさせる
    // ここで初めてログイン状態にするため、メール認証誘導画面ではヘッダーのナビゲーションは表示されない
    Auth::login($request->user());
    Log::info('Email Verification Route: User ' . $request->user()->id . ' explicitly logged in AFTER verification.');
    Log::info('Email Verification Route: Auth check (AFTER fulfill & login): ' . (Auth::check() ? 'true' : 'false') . ' (User ID: ' . (Auth::check() ? Auth::id() : 'N/A') . ')');

    // unauthenticated_user セッションを削除（もうログイン済みなので不要）
    Session::forget('unauthenticated_user');
    Log::info('Email Verification Route: unauthenticated_user session forgotten.');


    $redirectUrl = redirect()->intended(RouteServiceProvider::HOME);
    Log::info('Manual Email Verification Route: Attempting final redirect to: ' . $redirectUrl->getTargetUrl());

    return $redirectUrl->with('status', 'メールアドレスを認証しました。勤怠登録画面へようこそ！');

})->middleware(['signed'])->name('verification.verify'); // ★'auth' ミドルウェアを削除！'signed'のみ残す★

/*//Mailhogへのルート
Route::get('/mailhog', function() {
    return redirect('http://localhost:8025');
});


//ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    //->middleware('redirect_if_email_unverified');

//会員登録
Route::post('/register', [RegisteredUserController::class, 'store']);


//メール認証誘導画面
Route::get('/email/verify', function() {
    return view('pages.auth_verify');
})->name('verification.notice');

//認証メールの再送
Route::post('/email/verification-notification', function (Request $request) {
    Log::info('Verification Notification Route: Started.');
    // セッションから未認証ユーザーを取得
    $user = Session::get('unauthenticated_user');
    if (!$user) {
        // もしセッションに未認証ユーザーがいなければ、ユーザーは登録直後ではないか、セッションが切れた可能性
        // ログイン状態であれば勤怠画面へ、そうでなければ登録画面へ
        if (Auth::check()) {
            return redirect()->intended(RouteServiceProvider::HOME)->with('status', 'メールアドレスは既に認証済みです。勤怠登録画面へようこそ。');
        }
        Log::warning('Verification Notification Route: No unauthenticated_user in session. Redirecting to register.');
        return redirect('/register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }
    // 既に認証済みの場合はログイン画面に行く
    if ($user->hasVerifiedEmail()) {
        Log::info('Verification Notification Route: User ' . $user->id . ' already verified. Redirecting to HOME.');
        // 既に認証済みであれば、そのユーザーでログインさせてHOMEへ
        Auth::login($user);
        return redirect()->intended(RouteServiceProvider::HOME)->with('status', 'メールアドレスは既に認証済みです。勤怠登録画面へようこそ。');
    }
    $user->sendEmailVerificationNotification();
    Session::flash('resent', true);
    Log::info('Verification Notification Route: Sent new verification email for user ' . $user->id . '.');
    return back()->with('status', '新しい認証メールを送信しました。メールをご確認ください。');
    /*Log::info('Verification Notification Route: Started.'); // ログ追加
    //★registered_emailを取得。これはRegisteredUserControllerで作られてる
    $email = Session::get('registered_email');
    //★もしセッションにメアドがなかったら会員登録画面に戻す
    if (!$email) {
        Log::warning('Verification Notification Route: No registered_email in session. Redirecting to register.'); // ログ追加
        return redirect('/register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }
    //★そのメアドを使って、データベースからユーザーを探す
    $user = User::where('email', $email)->first();
    //★もしデータベースに見つからなかったら会員登録画面に戻す
    if (!$user) {
        Log::warning('Verification Notification Route: User not found for email: ' . $email . '. Redirecting to register.'); // ログ追加
        return redirect('/register')->with('error', '指定されたメールアドレスのユーザーが見つかりませんでした。再度会員登録をしてください。');
    }
    //★ 既に認証済みの場合はログイン画面に行く
    if ($user->hasVerifiedEmail()) {
        Log::info('Verification Notification Route: User ' . $user->id . ' already verified. Redirecting to HOME.'); // ログ追加
        return redirect('/login')->with('status', 'メールアドレスは既に認証済みです。ログインしてください。');
    }
    //★認証メールを再送する
    $user->sendEmailVerificationNotification();
    //★resentという情報を一時的にセッションに保存。メールが再送されたことをビューに伝えるためによく使われる手法
    Session::flash('resent', true);
    //★メール認証誘導画面に戻り(まあ元々動かないんだけど)メッセージを表示させる
    Log::info('Verification Notification Route: Sent new verification email for user ' . $user->id . '.'); // ログ追加
    return back()->with('status', '新しい認証メールを送信しました。メールをご確認ください。');
})->name('verification.send');

// ★★★最重要修正点：メール認証リンク処理のルートをLaravel標準のEmailVerificationRequestを使用するように変更★★★
// このクロージャが、メール内のリンクをクリックした際にメールアドレスを認証済みとマークし、
// RouteServiceProvider::HOME にリダイレクトする処理を担います。
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    Log::info('Email Verification Route: STARTED - Attempting to verify email for user ID: ' . ($request->user() ? $request->user()->id : 'N/A') . '.');
    Log::info('Email Verification Route: Auth check (BEFORE fulfill): ' . (Auth::check() ? 'true' : 'false') . ' (User ID: ' . (Auth::check() ? Auth::id() : 'N/A') . ')');

    // EmailVerificationRequest が自動的にユーザーを認証済みにマーク (Auth::login は行わない)
    $request->fulfill();

    // 認証完了後、そのユーザーをログインさせる
    Auth::login($request->user());
    Log::info('Email Verification Route: User ' . $request->user()->id . ' explicitly logged in AFTER verification.');
    Log::info('Email Verification Route: Auth check (AFTER fulfill & login): ' . (Auth::check() ? 'true' : 'false') . ' (User ID: ' . (Auth::check() ? Auth::id() : 'N/A') . ')');

    // セッションから未認証ユーザー情報を削除
    Session::forget('unauthenticated_user');
    Log::info('Email Verification Route: unauthenticated_user session forgotten.');


    $redirectUrl = redirect()->intended(RouteServiceProvider::HOME); // 出勤登録画面へリダイレクト
    Log::info('Email Verification Route: Attempting redirect to: ' . $redirectUrl->getTargetUrl());
    return $redirectUrl;

})->middleware(['signed'])->name('verification.verify'); // ★'auth' ミドルウェアは不要。'signed'のみ★

    /*Log::info('Email Verification Route: Auth check: ' . (Auth::check() ? 'true' : 'false') . ' (AFTER fulfill)'); // ログ追加
    Log::info('Email Verification Route: User ID from Auth: ' . (Auth::check() ? Auth::id() : 'N/A') . ' (AFTER fulfill)'); // ログ追加
    $redirectUrl = redirect()->intended(RouteServiceProvider::HOME);
    Log::info('Email Verification Route: Redirecting to: ' . $redirectUrl->getTargetUrl()); // ログ追加
    return redirect()->intended(RouteServiceProvider::HOME); // RouteServiceProvider::HOME へリダイレクト
})->name('verification.verify'); // 'throttle:6,1' は必須ではないので削除してもOK*/

/*//勤怠画面
Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'index']);
});*/