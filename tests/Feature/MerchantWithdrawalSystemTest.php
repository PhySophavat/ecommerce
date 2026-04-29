<?php

namespace Tests\Feature;

use App\Models\Merchant;
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

    public function test_merchant_can_manage_bank_accounts_with_single_default(): void
    {
        [$user, $merchant] = $this->approvedMerchant();

        $this->actingAs($user);

        $this->postJson('/api/merchant/bank-accounts', [
            'bank_name' => 'ABA',
            'account_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'account_type' => 'bank',
            'is_default' => true,
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('account.account_number', '****7890')
            ->assertJsonPath('account.is_default', true);

        $secondResponse = $this->postJson('/api/merchant/bank-accounts', [
            'bank_name' => 'Wing',
            'account_name' => 'Wallet',
            'account_number' => '999988887777',
            'account_type' => 'ewallet',
            'is_default' => true,
            'status' => 'active',
        ])->assertCreated();

        $secondId = $secondResponse->json('account.id');

        $this->getJson('/api/merchant/bank-accounts')
            ->assertOk()
            ->assertJsonCount(2, 'accounts')
            ->assertJsonPath('accounts.0.id', $secondId)
            ->assertJsonPath('accounts.0.is_default', true)
            ->assertJsonPath('accounts.1.is_default', false);
    }

    public function test_merchant_withdrawal_request_reserves_pending_balance_and_validates_available_amount(): void
    {
        [$user, $merchant] = $this->approvedMerchant([
            'balance_total' => 200,
            'available_balance' => 200,
            'pending_balance' => 0,
        ]);

        $this->actingAs($user);

        $accountId = $this->postJson('/api/merchant/bank-accounts', [
            'bank_name' => 'ABA',
            'account_name' => 'Primary Shop',
            'account_number' => '1234567890',
            'account_type' => 'bank',
            'is_default' => true,
            'status' => 'active',
        ])->json('account.id');

        $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $accountId,
            'amount' => 150,
            'note' => 'Weekly payout',
        ])->assertCreated()
            ->assertJsonPath('withdrawal.status', 'pending')
            ->assertJsonPath('withdrawal.net_amount', '150.00')
            ->assertJsonPath('balances.pending_balance', '150.00');

        $merchant->refresh();

        $this->assertSame('200.00', $merchant->available_balance);
        $this->assertSame('150.00', $merchant->pending_balance);

        $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $accountId,
            'amount' => 100,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_admin_can_approve_and_mark_withdrawal_paid(): void
    {
        [$merchantUser, $merchant] = $this->approvedMerchant([
            'balance_total' => 300,
            'available_balance' => 300,
            'pending_balance' => 0,
        ]);

        $this->actingAs($merchantUser);

        $accountId = $this->postJson('/api/merchant/bank-accounts', [
            'bank_name' => 'ACLEDA',
            'account_name' => 'Shop Owner',
            'account_number' => '112233445566',
            'account_type' => 'bank',
            'is_default' => true,
            'status' => 'active',
        ])->json('account.id');

        $withdrawalId = $this->postJson('/api/merchant/withdrawals', [
            'bank_account_id' => $accountId,
            'amount' => 120,
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
    }

    public function test_admin_can_approve_deposit_and_credit_wallet(): void
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
            'payment_method' => 'khqr',
            'payment_proof' => UploadedFile::fake()->image('proof.png'),
            'note' => 'Top up',
        ], [
            'Accept' => 'application/json',
        ])->assertCreated()
            ->json('deposit.id');

        $this->signInAsAdmin();

        $this->putJson("/api/admin/deposits/{$depositId}/approve", [
            'admin_note' => 'Verified',
        ])->assertOk()
            ->assertJsonPath('deposit.status', 'approved');

        $merchant->refresh();

        $this->assertSame('125.00', $merchant->balance_total);
        $this->assertSame('125.00', $merchant->available_balance);
        $this->assertSame('75.00', $merchant->total_deposited);
        $this->assertSame('approved', MerchantDeposit::query()->findOrFail($depositId)->status);
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
