<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_payload_contains_user_data_only(): void
    {
        $this->seed();

        $response = $this->getJson('/api/storefront');

        $response
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('meta.brand', 'Northstar Users')
                ->where('links.admin_users', url('/admin/products'))
                ->where('users.count', 3)
                ->has('users.items', 3)
                ->missing('categories')
                ->missing('products')
                ->missing('featured')
                ->missing('cart'));
    }
}
