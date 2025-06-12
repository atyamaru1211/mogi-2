<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Session; // Sessionファサードを追加


class MailVerifiedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // ログインフォームから送信されたメールアドレスでユーザーを検索
        $user = User::where('email', $request->input('email'))->first();

        // ユーザーが存在し、かつメール認証が完了していない場合
        if ($user && !$user->hasVerifiedEmail()) {
            // ★この行が確実に存在することを再確認！★
            Session::put('unauthenticated_user', $user);
            return redirect()->route('verification.notice')->with('unverified_email_message', 'このアカウントはまだメール認証が完了していません。メールをご確認ください。');
        }

        return $next($request);
    }
}
