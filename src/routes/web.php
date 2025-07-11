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
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\Admin\AdminCorrectionRequestController;
use App\Http\Controllers\Admin\AdminStaffController;


Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('email');
Route::post('/register', [RegisteredUserController::class, 'store']);

Route::middleware(['auth', 'verified'])->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::post('/stamp_correction_request', [CorrectionRequestController::class, 'update']);
});


// --- 共有パスの認証済みルート ---
Route::middleware(['auth:web,admin', 'verified'])->group(function () {

    Route::get('/attendance/{id}', function ($id) {
        if (Auth::guard('admin')->check()) {
            return app(AdminAttendanceController::class)->show($id);
        } else {
            return app(CorrectionRequestController::class)->show($id);
        }
    });

    Route::get('/stamp_correction_request/list', function (Request $request) {
        if (Auth::guard('admin')->check()) {
            return app(AdminCorrectionRequestController::class)->requestList($request); 
        } else {
            return app(CorrectionRequestController::class)->requestList($request);
        }
    });
});


Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm']);
Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'list']);
    Route::patch('/admin/attendance/update/{id}', [AdminAttendanceController::class, 'update']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'showApprovalForm']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminCorrectionRequestController::class, 'approve']);
    Route::post('/admin/logout', [AdminLoginController::class, 'logout']);
    Route::get('/admin/staff/list', [AdminStaffController::class, 'list']);
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'attendanceList']);
    Route::get('/admin/attendance/staff/{id}/export', [AdminStaffController::class, 'export']);
});

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

Route::post('/email/verification-notification', function (Request $request) {

    $userToSendEmail = null;
    if (Auth::check()) {
        $userToSendEmail = Auth::user();
    }
    elseif (Session::has('unauthenticated_user')) {
        $tempUser = Session::get('unauthenticated_user');
        $userToSendEmail = User::find($tempUser->id);
        if (!$userToSendEmail) {
            return redirect()->route('register')->with('error', 'ユーザー情報が見つかりません。再度会員登録をしてください。');
        }
    } else {
        return redirect()->route('register')->with('error', '認証メールを再送するには、ユーザー情報が必要です。再度会員登録をしてください。');
    }

    if ($userToSendEmail->hasVerifiedEmail()) {
        if (!Auth::check()) {
            Auth::login($userToSendEmail);
        }
        Session::forget('unauthenticated_user');
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    $userToSendEmail->sendEmailVerificationNotification();
    session()->flash('status', '新しい認証メールを送信しました。メールをご確認ください。');

    return back();
})->name('verification.send');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id) {
    $user = User::find($id);

    if (!$user || ! hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
        return redirect('/register')->with('error', 'メール認証リンクが無効です。');
    }

    if ($user->hasVerifiedEmail()) {
        if (!Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user);
        }
        Session::forget('unauthenticated_user');
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    $user->markEmailAsVerified(); 
    event(new Verified($user));

    if (!Auth::check() || Auth::id() !== $user->id) {
        Auth::login($user);
    }

    Session::forget('unauthenticated_user');

    return redirect()->intended(RouteServiceProvider::HOME);

})->middleware(['signed'])->name('verification.verify');

Route::get('/mailhog', function() {
    return redirect('http://localhost:8025');
});

