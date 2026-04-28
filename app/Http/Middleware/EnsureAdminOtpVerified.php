<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOtpVerified
{
    private const ADMIN_OTP_SESSION_KEY = 'auth.admin_otp_verified_user_id';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return $next($request);
        }

        if ($request->session()->get(self::ADMIN_OTP_SESSION_KEY) === $user->getKey()) {
            return $next($request);
        }

        return redirect()->route('auth.otp.form');
    }
}
