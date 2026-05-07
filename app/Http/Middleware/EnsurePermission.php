<?php

namespace App\Http\Middleware;

use App\Support\DashboardAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (!DashboardAccess::hasPermission($user->role, $permission)) {
            abort(403, 'Forbidden.');
        }

        return $next($request);
    }
}
