<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\RegisterResponse;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, CreateNewUser $creator)
    {
        //★CreateNewUserで検証したデータで新しいユーザーをデータベースに作成
        $user = $creator->create($request->validated());
        //★Registerdというイベントを着火。ユーザーが登録されたことを皆に知らせる
        event(new Registered($user));
        //★登録したユーザーのメアドをセッションにregisterd_emailという名前で保存。これが再送に使われる
        $request->session()->put('registered_email', $user->email);
        //★/email/verifyメール認証誘導画面にリダイレクト
        return redirect()->route('verification.notice');
    }
}
