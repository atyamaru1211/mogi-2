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
        $redirectPath = '/login';

        if (Auth::guard('admin')->check()) {
            $redirectPath = '/admin/login';
            Auth::guard('admin')->logout();
        } elseif (Auth::guard('web')->check()) {
            $redirectPath = '/login';
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new RedirectResponse(url($redirectPath));
    }
}