<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_an_order_and_clears_the_cart(): void
    {
        $this->seed();

        $product = Product::query()->firstOrFail();

        $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk()->assertJsonPath('cart.count', 2);

        $response = $this->postJson('/api/checkout', [
            'customer_name' => 'Jamie Carter',
            'email' => 'jamie@example.com',
            'phone' => '555-0100',
            'address_line1' => '123 Market Street',
            'address_line2' => 'Suite 7',
            'city' => 'Seattle',
            'postal_code' => '98101',
            'notes' => 'Leave at the front desk.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('order.customer_name', 'Jamie Carter')
            ->assertJsonPath('cart.count', 0);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertSame($product->inventory - 2, $product->fresh()->inventory);
    }
}
