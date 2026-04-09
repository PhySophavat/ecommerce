<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorefrontData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function create(): View
    {
        return view('backend.users.create', [
            'title' => 'Admin | User Management',
            'context' => [
                'app' => 'backend-users',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$user->name} was created successfully.",
                'user' => StorefrontData::user($user),
            ], 201);
        }

        return redirect()
            ->route('admin.users.create')
            ->with('status', "{$user->name} was created successfully.");
    }
}
