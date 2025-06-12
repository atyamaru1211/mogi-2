<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Actions\Fortify\CreateNewUser;
//use Laravel\Fortify\Contracts\RegisterResponse;
use Illuminate\Support\Facades\Session; // 追加
use Illuminate\Support\Facades\Auth; // 必要であれば追加

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->validated());

        // ★この行が確実に存在し、 Auth::login($user); は削除されていることを再確認！★
        Session::put('unauthenticated_user', $user);
        // ★追加ログ開始★
        Log::info('RegisteredUserController: Stored unauthenticated_user in session. User ID: ' . $user->id);
        Log::info('RegisteredUserController: Session has unauthenticated_user after put: ' . (Session::has('unauthenticated_user') ? 'true' : 'false'));
        Log::info('RegisteredUserController: Retrieved user from session: ' . (Session::get('unauthenticated_user') ? Session::get('unauthenticated_user')->id : 'N/A'));
        // ★追加ログ終了★

        event(new Registered($user));

        return redirect()->route('verification.notice');
    }
}
