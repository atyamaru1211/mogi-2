<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\Session; 
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, CreateNewUser $creator)
    {
        $user = $creator->create($request->validated());

        Session::put('unauthenticated_user', $user);

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        event(new Registered($user));

        return redirect()->route('verification.notice');
    }
}
