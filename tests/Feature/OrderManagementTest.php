<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_checkout_approved_products_and_stock_is_reduced(): void
    {
        $category = Category::query()->create([
            'name' => 'Beauty',
            'slug' => 'beauty',
        ]);

        $merchantUser = User::factory()->create(['role' => 'merchant']);
        $merchant = Merchant::query()->create([
            'user_id' => $merchantUser->id,
            'shop_name' => 'Glow Shop',
            'status' => 'Approved',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'merchant_id' => $merchantUser->id,
            'name' => 'Approved Serum',
            'slug' => 'approved-serum',
            'sku' => 'SERUM-1',
            'description' => 'Demo product',
            'price' => 12.50,
            'inventory' => 5,
            'status' => 'approved',
        ]);

        ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => 'products/serum.jpg',
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/frontend/checkout', [
                'customer_name' => 'Customer One',
                'email' => 'customer@example.com',
                'phone' => '012345678',
                'address_line1' => 'Street 1',
                'city' => 'Phnom Penh',
                'postal_code' => '12000',
                'payment_method' => 'aba_qr',
                'payment_reference' => 'ABA-REF-001',
                'payment_notes' => 'Paid from storefront mobile app',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('order.payment_status', 'unpaid')
            ->assertJsonPath('order.payment_method', 'aba_qr')
            ->assertJsonPath('order.payment_reference', 'ABA-REF-001')
            ->assertJsonPath('order.items.0.merchant_id', $merchant->id)
            ->assertJsonPath('order.items.0.product_name', 'Approved Serum');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'payment_method' => 'aba_qr',
            'payment_status' => 'unpaid',
            'payment_reference' => 'ABA-REF-001',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'merchant_id' => $merchant->id,
            'product_name' => 'Approved Serum',
            'product_image' => 'products/serum.jpg',
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'inventory' => 3,
        ]);
    }

    public function test_customer_cannot_checkout_unapproved_products(): void
    {
        $category = Category::query()->create([
            'name' => 'Beauty',
            'slug' => 'beauty',
        ]);

        $merchantUser = User::factory()->create(['role' => 'merchant']);
        Merchant::query()->create([
            'user_id' => $merchantUser->id,
            'shop_name' => 'Glow Shop',
            'status' => 'Approved',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'merchant_id' => $merchantUser->id,
            'name' => 'Pending Serum',
            'slug' => 'pending-serum',
            'sku' => 'SERUM-2',
            'description' => 'Demo product',
            'price' => 12.50,
            'inventory' => 5,
            'status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->postJson('/api/frontend/checkout', [
                'customer_name' => 'Customer One',
                'email' => 'customer@example.com',
                'phone' => '012345678',
                'address_line1' => 'Street 1',
                'city' => 'Phnom Penh',
                'postal_code' => '12000',
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');
    }

    public function test_merchant_only_sees_orders_for_their_products_and_admin_can_update_payment_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $merchantUserA = User::factory()->create(['role' => 'merchant']);
        $merchantUserB = User::factory()->create(['role' => 'merchant']);

        $merchantA = Merchant::query()->create([
            'user_id' => $merchantUserA->id,
            'shop_name' => 'Merchant A',
            'status' => 'Approved',
        ]);

        $merchantB = Merchant::query()->create([
            'user_id' => $merchantUserB->id,
            'shop_name' => 'Merchant B',
            'status' => 'Approved',
        ]);

        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'number' => 'ORD-TEST-001',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'customer_name' => 'Customer One',
            'email' => 'customer@example.com',
            'phone' => '012345678',
            'address_line1' => 'Street 1',
            'city' => 'Phnom Penh',
            'postal_code' => '12000',
            'subtotal_amount' => 20,
            'shipping_amount' => 0,
            'total_amount' => 20,
            'placed_at' => now(),
        ]);

        $order->items()->create([
            'merchant_id' => $merchantA->id,
            'product_name' => 'Merchant A Product',
            'product_sku' => 'A-1',
            'unit_price' => 10,
            'quantity' => 1,
            'line_total' => 10,
        ]);

        $otherOrder = Order::query()->create([
            'customer_id' => $customer->id,
            'number' => 'ORD-TEST-002',
            'status' => 'pending',
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'customer_name' => 'Customer One',
            'email' => 'customer@example.com',
            'phone' => '012345678',
            'address_line1' => 'Street 1',
            'city' => 'Phnom Penh',
            'postal_code' => '12000',
            'subtotal_amount' => 10,
            'shipping_amount' => 0,
            'total_amount' => 10,
            'placed_at' => now(),
        ]);

        $otherOrder->items()->create([
            'merchant_id' => $merchantB->id,
            'product_name' => 'Merchant B Product',
            'product_sku' => 'B-1',
            'unit_price' => 10,
            'quantity' => 1,
            'line_total' => 10,
        ]);

        $this->actingAs($merchantUserA)
            ->getJson('/api/merchant/orders')
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.number', 'ORD-TEST-001');

        $admin = $this->signInAsAdmin();

        $this->actingAs($admin)
            ->putJson("/api/admin/orders/{$order->id}/payment-status", [
                'payment_status' => 'paid',
            ])
            ->assertOk()
            ->assertJsonPath('order.payment_status', 'paid')
            ->assertJsonPath('order.paid_at', fn ($value) => !empty($value));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'paid',
        ]);
    }
}
