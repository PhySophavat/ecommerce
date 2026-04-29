<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_admin_requests_redirect_to_the_public_login_route(): void
    {
        $this->assertTrue(Route::has('login'));
        $this->assertTrue(Route::has('auth.otp.form'));

        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/api/admin/products')->assertRedirect('/login');
    }

    public function test_public_login_form_posts_to_shared_auth_login_route(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('action="'.route('auth.login.submit').'"', false);
    }

    public function test_admin_login_requires_fixed_otp_before_dashboard_access(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->post('/auth/login', [
            'login' => 'admin',
            'password' => 'admin',
        ])->assertRedirect('/auth/verify-otp');

        $this->assertAuthenticated();
        $this->get('/admin/dashboard')->assertRedirect('/auth/verify-otp');

        $this->post('/auth/verify-otp', [
            'otp' => '123456',
        ])->assertRedirect('/admin/dashboard');

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_authenticated_admin_can_logout_and_is_redirected_to_login(): void
    {
        $this->signInAsAdmin();

        $this->post('/auth/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_merchant_can_switch_to_admin_from_the_login_form(): void
    {
        $this->seed(AdminUserSeeder::class);

        $merchant = User::query()->create([
            'name' => 'merchant-user',
            'email' => 'merchant@example.com',
            'password' => 'merchant-password',
            'role' => 'merchant',
        ]);

        $this->actingAs($merchant);

        $this->get('/login')->assertOk();

        $this->post('/auth/login', [
            'login' => 'admin',
            'password' => 'admin',
        ])->assertRedirect('/auth/verify-otp');

        $this->assertAuthenticated();
        $this->assertSame('admin@example.com', auth()->user()?->email);
    }
}
