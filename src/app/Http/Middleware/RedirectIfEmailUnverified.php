<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfEmailUnverified
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
        //★ ログイン処理が成功し、ユーザーが認証済みになった直後
        //★ かつメールが未認証の場合にリダイレクト
        if (Auth::check() && ! Auth::user()->hasVerifiedEmail()) {
            //★ 未認証ユーザーのログインを強制的に解除し、認証誘導画面へリダイレクト
            Auth::logout();
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
