<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest; // LoginFormRequest が存在しない場合、LoginRequest を使用
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;//★

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        Log::info('AdminLoginController: showLoginForm called. Returning admin.login view.'); // ★ログメッセージも更新
        // ★修正: 管理者専用のログインビューを返す
        return view('admin.login'); 
    }

    public function login(LoginRequest $request)
    {
        Log::info('AdminLoginController: Attempting admin login for email: ' . $request->input('email'));

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            Log::info('AdminLoginController: Admin login successful. Guard check (admin): ' . (Auth::guard('admin')->check() ? 'true' : 'false') . ' Guard check (web): ' . (Auth::check() ? 'true' : 'false'));
            Log::info('AdminLoginController: Redirecting to /admin/attendance/list');

            return redirect()->intended('/admin/attendance/list'); 
        }

        Log::warning('AdminLoginController: Admin login failed for email: ' . $request->input('email'));

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);

        // ★Auth::guard('admin') を使って管理者として認証を試みる
        /*if (Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect('/admin/attendance/list');
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);*/
    }

    public function logout(Request $request)
    {
        Log::info('AdminLoginController: Admin logout initiated.');
        Auth::guard('admin')->logout(); // 'admin' ガードを使ってログアウト

        $request->session()->invalidate(); // セッションを無効化
        $request->session()->regenerateToken(); // CSRFトークンを再生成

        Log::info('AdminLoginController: Admin logged out successfully.');
        // ログアウト後のリダイレクト先を /admin/login に指定
        return redirect('/admin/login');
    }
}
