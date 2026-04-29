<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const ADMIN_OTP = '123456';

    private const ADMIN_OTP_SESSION_KEY = 'auth.admin_otp_verified_user_id';

    /**
     * Show login form
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        return view('auth.login', $this->authViewData('Auth | Login'));
    }

    /**
     * Handle login request
     */
    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $user = $this->resolveUserFromLogin($credentials['login']);

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->forget(self::ADMIN_OTP_SESSION_KEY);

            return $this->redirectToDashboard($request);
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    /**
     * Show registration form
     */
    public function showRegisterForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectToDashboard($request);
        }

        return view('auth.register', $this->authViewData('Auth | Register'));
    }

    /**
     * Handle registration request
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:merchant,customer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'customer',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectToDashboard($request);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget(self::ADMIN_OTP_SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect user to appropriate dashboard based on role
     */
    public function dashboard(Request $request): RedirectResponse
    {
        return $this->redirectToDashboard($request);
    }

    /**
     * Show the fixed OTP verification form for admin users.
     */
    public function showOtpForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return $this->redirectToDashboard($request);
        }

        if ($this->hasVerifiedAdminOtp($request)) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.verify-otp', $this->authViewData('Auth | Verify OTP'));
    }

    /**
     * Verify the fixed OTP code for admin users.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return $this->redirectToDashboard($request);
        }

        $validated = $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        if ($validated['otp'] !== self::ADMIN_OTP) {
            return back()->withErrors([
                'otp' => 'The OTP code is invalid.',
            ])->onlyInput('otp');
        }

        $request->session()->put(self::ADMIN_OTP_SESSION_KEY, $user->getKey());

        return redirect()->route('admin.dashboard');
    }

    /**
     * Redirect to role-specific dashboard
     */
    private function redirectToDashboard(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        return match ($user->role) {
            'admin' => $this->hasVerifiedAdminOtp($request)
                ? redirect()->route('admin.dashboard')
                : redirect()->route('auth.otp.form'),
            'merchant' => $user->merchant?->isApproved()
                ? redirect('/merchant/products')
                : redirect()->route('merchant.status'),
            'customer' => redirect('/'),
            default => redirect('/'),
        };
    }

    /**
     * Resolve a user from either email or username-style login input.
     */
    private function resolveUserFromLogin(string $login): ?User
    {
        return User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();
    }

    /**
     * Check whether the current admin session already passed OTP verification.
     */
    private function hasVerifiedAdminOtp(Request $request): bool
    {
        $user = $request->user();

        return $user?->isAdmin()
            && $request->session()->get(self::ADMIN_OTP_SESSION_KEY) === $user->getKey();
    }

    /**
     * Shared view data for the server-rendered auth screens.
     *
     * @return array<string, mixed>
     */
    private function authViewData(string $title): array
    {
        return [
            'title' => $title,
            'context' => [
                'app' => 'backend-auth',
            ],
            'mountVueApp' => false,
        ];
    }
}
