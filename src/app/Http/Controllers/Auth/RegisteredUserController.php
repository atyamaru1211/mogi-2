<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\Session; 
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 

class RegisteredUserController extends Controller
{
    //★新規ユーザーを登録し、メール認証誘導画面へリダイレクトする
    public function store(RegisterRequest $request, CreateNewUser $creator)
    {
        // FortifyのCreateNewUserアクションでユーザーを作成
        $user = $creator->create($request->validated());

        // 登録したユーザー情報をセッションに保存し、メール認証のために一時的に管理
        Session::put('unauthenticated_user', $user);

        // Fortifyが自動的にログインさせてしまった場合の強制ログアウト
        // これにより、メール認証誘導画面で未ログイン状態を維持する
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Registeredイベント発火 メール認証メールが送信されます
        event(new Registered($user));

        // メール認証誘導画面へリダイレクト
        return redirect()->route('verification.notice');
    }
}
