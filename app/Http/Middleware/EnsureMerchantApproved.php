<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $merchant = $request->user()?->merchant;

        if (!$merchant) {
            if ($request->expectsJson()) {
                abort(403, 'Merchant profile not found.');
            }

            return redirect()->route('merchant.register.step1');
        }

        if (!$merchant->isApproved()) {
            if ($request->expectsJson()) {
                abort(403, 'Merchant account is not approved.');
            }

            return redirect()->route('merchant.status');
        }

        return $next($request);
    }
}
