<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            if ($request->expectsJson()) {
                abort(401, 'Unauthenticated.');
            }

            return redirect()->route('login');
        }

        $allowedRoles = collect($roles)
            ->flatMap(fn (string $value): array => explode(',', $value))
            ->map(fn (string $value): string => trim($value))
            ->filter()
            ->values()
            ->all();

        if (!in_array($request->user()->role, $allowedRoles, true)) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthorized access.');
            }

            return $this->redirectForRole($request->user()->role);
        }

        return $next($request);
    }

    private function redirectForRole(?string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'merchant' => $this->redirectMerchant(),
            default => redirect('/'),
        };
    }

    private function redirectMerchant(): RedirectResponse
    {
        $merchant = auth()->user()?->merchant;

        if (!$merchant || !$merchant->isApproved()) {
            return redirect()->route('merchant.status');
        }

        return redirect()->route('merchant.dashboard');
    }
}
