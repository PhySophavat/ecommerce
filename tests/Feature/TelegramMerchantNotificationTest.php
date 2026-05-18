<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramMerchantNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_sends_merchant_telegram_notification(): void
    {
        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.enabled', true);
        config()->set('services.telegram.admin_chat_id', '999888777');

        $customer = User::factory()->create([
            'role' => 'customer',
            'telegram_chat_id' => '555444333',
        ]);
        $merchantUser = User::factory()->create([
            'role' => 'merchant',
            'telegram_chat_id' => '123456789',
        ]);
        User::factory()->create(['role' => 'admin']);

        $merchant = Merchant::query()->create([
            'user_id' => $merchantUser->id,
            'shop_name' => 'Kh Shop',
            'business_type' => 'Electronic',
            'business_description' => 'Telegram test merchant',
            'status' => 'Approved',
        ]);

        $category = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'merchant_id' => $merchantUser->id,
            'name' => 'Bluetooth Speaker',
            'slug' => 'bluetooth-speaker',
            'sku' => 'BT-SPK-01',
            'description' => 'Portable speaker',
            'price' => 49.99,
            'inventory' => 10,
            'status' => 'approved',
        ]);

        $response = $this
            ->actingAs($customer)
            ->postJson('/api/frontend/checkout', [
                'customer_name' => 'Dara',
                'email' => 'dara@example.com',
                'phone' => '012345678',
                'address_line1' => 'Street 1',
                'city' => 'Phnom Penh',
                'postal_code' => '12000',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertCreated();

        Http::assertSentCount(3);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request->data()['chat_id'] === '123456789'
            && str_contains($request->data()['text'], 'សួស្តី Kh Shop!')
            && str_contains($request->data()['text'], 'មានការកម្មង់ថ្មី #ORD-')
            && str_contains($request->data()['text'], '👤 អតិថិជន: Dara')
            && str_contains($request->data()['text'], '💰 សរុប: 99.98'));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request->data()['chat_id'] === '555444333'
            && str_contains($request->data()['text'], 'ការកម្មង់ #ORD-')
            && str_contains($request->data()['text'], '💰 សរុប: 99.98'));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
            && $request->data()['chat_id'] === '999888777'
            && str_contains($request->data()['text'], 'Admin Alert!')
            && str_contains($request->data()['text'], 'មាន order ថ្មី #ORD-'));
    }
}
