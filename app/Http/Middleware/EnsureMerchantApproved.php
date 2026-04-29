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
            return redirect()->route('merchant.register.step1');
        }

        if (!$merchant->isApproved()) {
            return redirect()->route('merchant.status');
        }

        return $next($request);
    }
}
