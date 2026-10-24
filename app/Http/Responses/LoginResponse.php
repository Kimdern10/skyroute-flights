<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * After logging in, the admin lands on the admin dashboard and customers on their account,
 * unless they were on their way to another page (such as booking a flight).
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponse
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return redirect()->intended(route($request->user()?->is_admin ? 'admin.dashboard' : 'dashboard', absolute: false));
    }
}
