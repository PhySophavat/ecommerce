<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BackendProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_and_user_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('frontend.home'));
        $this->assertTrue(Route::has('admin.users.create'));
        $this->assertTrue(Route::has('admin.users.store'));
        $this->assertSame(url('/frontend'), route('frontend.home'));
        $this->assertSame(url('/admin/users/create'), route('admin.users.create'));
    }

    public function test_old_backend_routes_redirect_to_user_form(): void
    {
        $this->get('/backend')->assertRedirect('/admin/users/create');
        $this->get('/backend/products')->assertRedirect('/admin/users/create');
        $this->get('/admin/products')->assertRedirect('/admin/users/create');
    }

    public function test_backend_vue_request_creates_a_user(): void
    {
        $response = $this->postJson('/admin/users', [
            'name' => 'Jamie Carter',
            'email' => 'jamie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Jamie Carter')
            ->assertJsonPath('user.email', 'jamie@example.com');

        $this->assertDatabaseHas('users', [
            'name' => 'Jamie Carter',
            'email' => 'jamie@example.com',
        ]);

        $user = User::query()->where('email', 'jamie@example.com')->firstOrFail();

        $this->assertNotSame('password123', $user->password);
    }
}
