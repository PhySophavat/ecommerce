<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StorefrontData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function csrfToken(Request $request): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }

    public function session(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $token = null;

        if (!$user) {
            $sessionUser = $request->user();

            if ($sessionUser?->isCustomer()) {
                $user = $sessionUser;
                $token = $user->createToken('customer-api-token')->plainTextToken;
            }
        }

        return response()->json([
            'authenticated' => (bool) $user,
            'user' => $user?->isCustomer() ? StorefrontData::user($user) : null,
            'token' => $token,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = \App\Models\User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => 'customer',
        ]);
        $token = $user->createToken('customer-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Customer account created successfully.',
            'user' => StorefrontData::user($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !$this->passwordMatches($user, $credentials['password'])) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 422);
        }

        if (!$user || !$user->isCustomer()) {
            return response()->json([
                'message' => 'Only customer accounts can sign in from the storefront.',
            ], 403);
        }

        $token = $user->createToken('customer-api-token')->plainTextToken;

        return response()->json([
            'message' => 'Signed in successfully.',
            'user' => StorefrontData::user($user),
            'token' => $token,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => StorefrontData::user($user->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Signed out successfully.',
        ]);
    }

    private function passwordMatches(User $user, string $password): bool
    {
        try {
            return Hash::check($password, $user->password);
        } catch (RuntimeException) {
            if (!hash_equals((string) $user->password, $password)) {
                return false;
            }

            // Self-heal legacy plain-text records by rehashing after a successful fallback match.
            $user->password = $password;
            $user->save();

            return true;
        }
    }
}
