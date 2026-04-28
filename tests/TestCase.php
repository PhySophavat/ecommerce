<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function signInAsAdmin(bool $otpVerified = true): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'admin',
                'password' => 'admin',
                'role' => 'admin',
            ],
        );

        $this->actingAs($user);

        if ($otpVerified) {
            $this->withSession([
                'auth.admin_otp_verified_user_id' => $user->getKey(),
            ]);
        }

        return $user;
    }
}
