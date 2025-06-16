<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest; // LoginFormRequest が存在しない場合、LoginRequest を使用
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login', ['is_admin' => true]);
    }

    public function login(LoginRequest $request)
    {
        // ★Auth::guard('admin') を使って管理者として認証を試みる
        if (Auth::guard('admin')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect('/admin/attendance/list');
        }

        throw ValidationException::withMessages([
            'email' => trans('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout(); // 'admin' ガードを使ってログアウト

        $request->session()->invalidate(); // セッションを無効化
        $request->session()->regenerateToken(); // CSRFトークンを再生成

        // ログアウト後のリダイレクト先を /admin/login に指定
        return redirect('/admin/login');
    }
}
