<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_payload_contains_catalog_and_cart_data(): void
    {
        $this->seed();

        $response = $this->getJson('/api/storefront');

        $response
            ->assertOk()
            ->assertJsonPath('cart.count', 0)
            ->assertJsonCount(3, 'categories')
            ->assertJsonCount(9, 'products');
    }
}
