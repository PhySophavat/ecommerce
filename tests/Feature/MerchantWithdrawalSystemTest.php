<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\MerchantBankAccount;
use App\Models\MerchantDeposit;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchantWithdrawalSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_bank_account_submission_starts_as_pending(): void
    {
        [$user] = $this->approvedMerchant();

        $this->actingAs($user);

        $response = $this->postJson('/api/merchant/bank-accounts', [
            'bank_name' => 'ABA',
            'account_holder_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'phone_number' => '0881234567',
            'currency' => 'USD',
            'account_type' => 'bank_account',
        ])->assertCreated()
            ->assertJsonPath('account.status', 'pending')
            ->assertJsonPath('account.is_default', false);

        $this->assertSame('pending', MerchantBankAccount::query()->findOrFail($response->json('account.id'))->status);
    }

    public function test_admin_can_approve_accounts_and_merchant_can_set_single_default_on_approved_accounts(): void
    {
        [$user, $merchant] = $this->approvedMerchant();

        $this->actingAs($user);

        $firstId = $this->createMerchantBankAccount([
            'bank_name' => 'ABA',
            'account_holder_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'phone_number' => '0881234567',
            'currency' => 'USD',
            'account_type' => 'bank_account',
        ]);

        $secondId = $this->createMerchantBankAccount([
            'bank_name' => 'Wing',
            'account_holder_name' => 'Second Shop',
            'account_number' => '999988887777',
            'phone_number' => '0977654321',
            'currency' => 'USD',
            'account_type' => 'khqr',
            'khqr_code' => 'KHQR-123',
        ]);

        $this->signInAsAdmin();
        $this->putJson("/api/admin/bank-accounts/{$firstId}/approve")->assertOk();
        $this->putJson("/api/admin/bank-accounts/{$secondId}/approve")->assertOk();

        $this->actingAs($user);
        $this->post("/api/merchant/bank-accounts/{$secondId}", [
            '_method' => 'PUT',
            'bank_name' => 'Wing',
            'account_holder_name' => 'Second Shop',
            'account_number' => '',
            'phone_number' => '0977654321',
            'currency' => 'USD',
            'account_type' => 'khqr',
            'khqr_code' => 'KHQR-123',
            'is_default' => true,
        ], ['Accept' => 'application/json'])->assertOk();

        $merchant->refresh();
        $accounts = $merchant->bankAccounts()->orderBy('id')->get();

        $this->assertTrue((bool) $accounts->firstWhere('id', $secondId)?->is_default);
        $this->assertFalse((bool) $accounts->firstWhere('id', $firstId)?->is_default);
    }

    public function test_merchant_can_open_merchant_bank_accounts_page(): void
    {
        [$user] = $this->approvedMerchant();

        $this->actingAs($user);

        $this->get('/merchant/bank-accounts')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_merchant_withdrawal_requires_approved_matching_currency_bank_account(): void
    {
        [$user, $merchant] = $this->approvedMerchant([
            'balance_total' => 200,
            'available_balance' => 200,
            'pending_balance' => 0,
        ]);

        $this->actingAs($user);

        $pendingAccountId = $this->createMerchantBankAccount([
            'bank_name' => 'ABA',
            'account_holder_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'phone_number' => '0881234567',
            'currency' => 'USD',
            'account_type' => 'bank_account',
        ]);

        $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $pendingAccountId,
            'amount' => 150,
            'currency' => 'USD',
            'note' => 'Weekly payout',
        ])->assertStatus(404);

        $this->signInAsAdmin();
        $this->putJson("/api/admin/bank-accounts/{$pendingAccountId}/approve")->assertOk();

        $this->actingAs($user);

        $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $pendingAccountId,
            'amount' => 150,
            'currency' => 'KHR',
            'note' => 'Wrong currency payout',
        ])->assertStatus(404);

        $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $pendingAccountId,
            'amount' => 150,
            'currency' => 'USD',
            'note' => 'Weekly payout',
        ])->assertCreated()
            ->assertJsonPath('withdrawal.status', 'pending')
            ->assertJsonPath('withdrawal.currency', 'USD')
            ->assertJsonPath('balances.pending_balance', '150.00');

        $merchant->refresh();

        $this->assertSame('200.00', $merchant->available_balance);
        $this->assertSame('150.00', $merchant->pending_balance);
    }

    public function test_admin_can_approve_and_mark_withdrawal_paid(): void
    {
        [$merchantUser, $merchant] = $this->approvedMerchant([
            'balance_total' => 300,
            'available_balance' => 300,
            'pending_balance' => 0,
        ]);

        $this->actingAs($merchantUser);

        $accountId = $this->createMerchantBankAccount([
            'bank_name' => 'ACLEDA',
            'account_holder_name' => 'Shop Owner',
            'account_number' => '112233445566',
            'phone_number' => '010222333',
            'currency' => 'KHR',
            'account_type' => 'bank_account',
        ]);

        $this->signInAsAdmin();
        $this->putJson("/api/admin/bank-accounts/{$accountId}/approve")->assertOk();

        $this->actingAs($merchantUser);
        $withdrawalId = $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $accountId,
            'amount' => 120,
            'currency' => 'KHR',
        ])->json('withdrawal.id');

        $this->signInAsAdmin();

        $this->putJson("/api/admin/withdrawals/{$withdrawalId}/approve", [
            'note' => 'Approved by finance',
        ])->assertOk()
            ->assertJsonPath('withdrawal.status', 'approved');

        $merchant->refresh();

        $this->assertSame('300.00', $merchant->balance_total);
        $this->assertSame('300.00', $merchant->available_balance);
        $this->assertSame('120.00', $merchant->pending_balance);

        $this->putJson("/api/admin/withdrawals/{$withdrawalId}/mark-paid", [
            'note' => 'Transfer completed',
        ])->assertOk()
            ->assertJsonPath('withdrawal.status', 'paid');

        $merchant->refresh();

        $this->assertSame('180.00', $merchant->balance_total);
        $this->assertSame('180.00', $merchant->available_balance);
        $this->assertSame('0.00', $merchant->pending_balance);
        $this->assertSame('paid', Withdrawal::query()->findOrFail($withdrawalId)->status);
        $this->assertDatabaseHas('withdraw_requests', [
            'withdrawal_id' => $withdrawalId,
            'merchant_id' => $merchant->id,
            'status' => 'success',
        ]);
        $this->assertDatabaseHas('transactions', [
            'merchant_id' => $merchant->id,
            'type' => 'OUT',
            'status' => 'success',
        ]);
    }

    public function test_admin_bank_accounts_index_includes_merchant_submission(): void
    {
        [$merchantUser] = $this->approvedMerchant();

        $this->actingAs($merchantUser);

        $this->createMerchantBankAccount([
            'bank_name' => 'ABA',
            'account_holder_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'phone_number' => '0881234567',
            'currency' => 'USD',
            'account_type' => 'bank_account',
        ]);

        $this->signInAsAdmin();

        $this->getJson('/api/admin/bank-accounts')
            ->assertOk()
            ->assertJsonPath('summary.all', 1)
            ->assertJsonPath('summary.pending', 1)
            ->assertJsonPath('accounts.0.status', 'pending')
            ->assertJsonPath('accounts.0.currency', 'USD')
            ->assertJsonPath('accounts.0.merchant.shop_name', 'Payout Shop');
    }

    public function test_merchant_deposit_is_auto_approved_and_credits_wallet(): void
    {
        Storage::fake('public');

        [$merchantUser, $merchant] = $this->approvedMerchant([
            'balance_total' => 50,
            'available_balance' => 50,
            'pending_balance' => 0,
        ]);

        $this->actingAs($merchantUser);

        $depositId = $this->post('/api/merchant/deposits', [
            'amount' => 75,
            'bank_name' => 'ABA',
            'account_name' => 'Merchant User',
            'account_number' => '123456789',
            'phone_number' => '0881234567',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'note' => 'Top up',
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->assertJsonPath('deposit.status', 'approved')
            ->json('deposit.id');

        $merchant->refresh();

        $this->assertSame('125.00', $merchant->balance_total);
        $this->assertSame('125.00', $merchant->available_balance);
        $this->assertSame('75.00', $merchant->total_deposited);
        $this->assertSame('approved', MerchantDeposit::query()->findOrFail($depositId)->status);
        $this->assertDatabaseHas('transactions', [
            'merchant_id' => $merchant->id,
            'type' => 'IN',
            'status' => 'success',
        ]);
    }

    public function test_merchant_deposit_requires_bank_sender_fields_and_returns_provider_metadata(): void
    {
        Storage::fake('public');

        [$merchantUser] = $this->approvedMerchant();

        $this->actingAs($merchantUser);

        $this->getJson('/api/merchant/deposits')
            ->assertOk()
            ->assertJsonPath('merchant.shop_name', 'Payout Shop')
            ->assertJsonPath('providers.0.bank_name', 'ABA');

        $this->post('/api/merchant/deposits', [
            'amount' => 20,
            'bank_name' => 'ABA',
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'account_name',
                'account_number',
                'phone_number',
                'payment_proof',
            ]);
    }

    private function createMerchantBankAccount(array $payload): int
    {
        return $this->post('/api/merchant/bank-accounts', $payload, [
            'Accept' => 'application/json',
        ])->assertCreated()->json('account.id');
    }

    /**
     * @return array{0: User, 1: Merchant}
     */
    private function approvedMerchant(array $merchantOverrides = []): array
    {
        $user = User::create([
            'name' => 'Merchant User',
            'email' => 'merchant'.uniqid().'@example.com',
            'phone' => '01'.random_int(1000000, 9999999),
            'password' => 'password',
            'role' => 'merchant',
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'shop_name' => 'Payout Shop',
            'business_type' => 'Fashion',
            'status' => 'Approved',
            'verification_status' => 'Verified',
            'balance_total' => 0,
            'available_balance' => 0,
            'pending_balance' => 0,
            'total_withdrawn' => 0,
            'total_deposited' => 0,
            ...$merchantOverrides,
        ]);

        return [$user, $merchant];
    }
}
