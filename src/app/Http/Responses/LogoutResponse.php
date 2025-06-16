<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response
    {
        // ★ログアウト後のリダイレクト先を決定
        $redirectPath = '/login'; // デフォルトは一般ユーザーのログインページ

        // ログアウト処理を行う前に、どのガードで認証されていたかを確認
        // この時点でAuth::check()がどのガードに紐づいているか判断し、そのガードをログアウト
        if (Auth::guard('admin')->check()) {
            $redirectPath = '/admin/login'; // 管理者としてログインしていたら管理者ログインページへ
            Auth::guard('admin')->logout(); // 管理者ガードをログアウト
        } elseif (Auth::guard('web')->check()) {
            $redirectPath = '/login'; // 一般ユーザーとしてログインしていたら一般ログインページへ
            Auth::guard('web')->logout(); // 一般ユーザーガードをログアウト
        } else {
            // どちらのガードも認証されていないが、念のためセッションをクリア
            // これは通常発生しないが、複数ガード対応の安全策
        }

        // ★全てのセッションを無効化し、CSRFトークンを再生成
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 決定されたパスにリダイレクト
        return new RedirectResponse(url($redirectPath));
    }
}