<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_json_user_form_validates_required_and_unique_fields(): void
    {
        $this->signInAsAdmin();

        User::query()->create([
            'name' => 'Existing User',
            'email' => 'jamie@example.com',
            'password' => 'password123',
        ]);

        $response = $this
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => '',
                'email' => 'jamie@example.com',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_non_json_user_form_redirects_back_after_success(): void
    {
        $this->signInAsAdmin();

        $response = $this->post('/admin/users', [
            'name' => 'Morgan Lee',
            'email' => 'morgan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertRedirect('/admin/products')
            ->assertSessionHas('status');
    }
}
