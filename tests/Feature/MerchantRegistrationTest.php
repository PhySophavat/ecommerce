<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_complete_three_step_merchant_registration(): void
    {
        Storage::fake('public');

        $this->post('/merchant/register/step1', [
            'shop_name' => 'Demo Shop',
            'business_type' => 'Fashion',
            'business_description' => 'Fashion for everyone',
            'shop_logo' => UploadedFile::fake()->image('logo.jpg'),
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect('/merchant/register/step2');

        $this->post('/merchant/register/step2', [
            'owner_name' => 'Demo Owner',
            'phone' => '012345678',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'profile_image' => UploadedFile::fake()->image('profile.jpg'),
            'id_card' => UploadedFile::fake()->create('id-card.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/merchant/register/step3');

        $this->post('/merchant/register/step3', [
            'full_address' => 'Street 2004',
            'province_city' => 'Phnom Penh',
            'district' => 'Sen Sok',
            'commune' => 'Teuk Thla',
            'google_map_link' => 'https://maps.google.com/example',
            'delivery_area' => 'Phnom Penh',
        ])->assertRedirect('/login');

        $merchant = Merchant::query()->with(['user', 'location'])->first();

        $this->assertNotNull($merchant);
        $this->assertSame('Pending', $merchant->status);
        $this->assertSame('Pending', $merchant->verification_status);
        $this->assertSame('Demo Shop', $merchant->shop_name);
        $this->assertSame('Demo Owner', $merchant->user->name);
        $this->assertSame('012345678', $merchant->user->phone);
        $this->assertSame('Phnom Penh', $merchant->location->province_city);
        $this->assertNotNull($merchant->id_card_document);
    }

    public function test_pending_merchant_is_redirected_to_status_until_admin_approves(): void
    {
        $merchantUser = User::create([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'phone' => '011111111',
            'password' => 'password',
            'role' => 'merchant',
        ]);

        $merchant = Merchant::create([
            'user_id' => $merchantUser->id,
            'shop_name' => 'Pending Shop',
            'business_type' => 'Fashion',
            'status' => 'Pending',
            'verification_status' => 'Pending',
        ]);

        $this->actingAs($merchantUser);

        $this->get('/merchant/products')->assertRedirect('/merchant/status');
        $this->get('/merchant/status')->assertOk();

        $admin = $this->signInAsAdmin();
        $this->post("/admin/merchants/{$merchant->id}/approve")->assertRedirect();

        $merchantUser->refresh();
        $merchant->refresh();

        $this->assertSame('Approved', $merchant->status);
        $this->assertSame('Verified', $merchant->verification_status);

        $this->actingAs($merchantUser);
        $this->get('/merchant/status')->assertRedirect('/merchant/dashboard');
    }

    public function test_admin_cannot_access_merchant_finance_routes(): void
    {
        $this->signInAsAdmin();

        $this->get('/merchant/wallet')->assertRedirect('/admin/dashboard');
        $this->get('/merchant/deposits')->assertRedirect('/admin/dashboard');
        $this->get('/merchant/withdrawals')->assertRedirect('/admin/dashboard');
        $this->get('/merchant/bank-accounts')->assertRedirect('/admin/dashboard');
    }
}
