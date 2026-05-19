<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $email = trim((string) $googleUser->getEmail());

            if ($email === '') {
                return $this->redirectToLoginWithError('Google did not provide an email address for this account.');
            }

            $user = User::query()
                ->where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if ($user && !$user->isCustomer()) {
                return $this->redirectToLoginWithError('Only customer accounts can sign in from the storefront.');
            }

            if (!$user) {
                $user = User::query()->create([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'customer',
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();
            } else {
                $user->forceFill([
                    'name' => $googleUser->getName() ?: $user->name,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar() ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->away($this->frontendUrl('/'));
        } catch (Throwable $exception) {
            report($exception);

            return $this->redirectToLoginWithError('Google sign-in failed. Please try again or use email and password.');
        }
    }

    private function redirectToLoginWithError(string $message): RedirectResponse
    {
        return redirect()->away($this->frontendUrl('/login?google_error='.urlencode($message)));
    }

    private function frontendUrl(string $hashPath = '/'): string
    {
        $baseUrl = rtrim((string) config('services.frontend.url', config('app.url').'/frontend'), '/');

        return $baseUrl.'#'.$hashPath;
    }
}
