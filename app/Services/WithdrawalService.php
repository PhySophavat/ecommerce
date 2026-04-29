<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantBankAccount;
use App\Models\MerchantTransaction;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function __construct(private readonly WalletTransactionService $walletTransactionService)
    {
    }

    public function create(Merchant $merchant, MerchantBankAccount $bankAccount, float $amount, ?string $note = null): Withdrawal
    {
        return DB::transaction(function () use ($merchant, $bankAccount, $amount, $note): Withdrawal {
            $merchant = Merchant::query()->lockForUpdate()->findOrFail($merchant->getKey());

            $minimumAmount = $this->minimumAmount();
            $feeAmount = $this->feeAmount();
            $availableToRequest = $this->availableToRequest($merchant);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Withdrawal amount must be greater than zero.',
                ]);
            }

            if ($amount < $minimumAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Minimum withdrawal amount is $'.number_format($minimumAmount, 2).'.',
                ]);
            }

            if ($amount > $availableToRequest) {
                throw ValidationException::withMessages([
                    'amount' => 'Withdrawal amount exceeds your available balance.',
                ]);
            }

            if ($amount <= $feeAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Withdrawal amount must be greater than the withdrawal fee.',
                ]);
            }

            $merchant->increment('pending_balance', $amount);

            return Withdrawal::query()->create([
                'merchant_id' => $merchant->getKey(),
                'bank_account_id' => $bankAccount->getKey(),
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => round($amount - $feeAmount, 2),
                'status' => 'pending',
                'note' => $this->nullableString($note),
            ])->load(['merchant.user', 'bankAccount']);
        });
    }

    public function approve(Withdrawal $withdrawal, ?string $note = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $note): Withdrawal {
            $withdrawal = Withdrawal::query()
                ->with(['merchant', 'bankAccount'])
                ->lockForUpdate()
                ->findOrFail($withdrawal->getKey());

            if ($withdrawal->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending withdrawals can be approved.',
                ]);
            }

            $merchant = Merchant::query()->lockForUpdate()->findOrFail($withdrawal->merchant_id);
            $availableToRequest = $this->availableToRequest($merchant) + (float) $withdrawal->amount;

            if ((float) $withdrawal->amount > $availableToRequest) {
                throw ValidationException::withMessages([
                    'amount' => 'Merchant no longer has enough available balance to approve this withdrawal.',
                ]);
            }

            $withdrawal->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'note' => $this->mergedNote($withdrawal->note, $note),
            ])->save();

            return $withdrawal->fresh(['merchant.user', 'bankAccount']);
        });
    }

    public function reject(Withdrawal $withdrawal, ?string $note = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $note): Withdrawal {
            $withdrawal = Withdrawal::query()
                ->with(['merchant.user', 'bankAccount'])
                ->lockForUpdate()
                ->findOrFail($withdrawal->getKey());

            if ($withdrawal->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending withdrawals can be rejected.',
                ]);
            }

            $merchant = Merchant::query()->lockForUpdate()->findOrFail($withdrawal->merchant_id);
            $merchant->decrement('pending_balance', $withdrawal->amount);

            $withdrawal->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'note' => $this->mergedNote($withdrawal->note, $note),
            ])->save();

            return $withdrawal->fresh(['merchant.user', 'bankAccount']);
        });
    }

    public function markPaid(Withdrawal $withdrawal, ?string $note = null): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $note): Withdrawal {
            $withdrawal = Withdrawal::query()
                ->with(['merchant.user', 'bankAccount'])
                ->lockForUpdate()
                ->findOrFail($withdrawal->getKey());

            if ($withdrawal->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Only approved withdrawals can be marked as paid.',
                ]);
            }

            $merchant = Merchant::query()->lockForUpdate()->findOrFail($withdrawal->merchant_id);

            if ((float) $withdrawal->amount > (float) $merchant->available_balance) {
                throw ValidationException::withMessages([
                    'amount' => 'Merchant no longer has enough available balance to pay this withdrawal.',
                ]);
            }

            $merchant->decrement('available_balance', $withdrawal->amount);
            $merchant->decrement('pending_balance', $withdrawal->amount);
            $merchant->decrement('balance_total', $withdrawal->amount);
            $merchant->increment('total_withdrawn', $withdrawal->amount);
            $merchant->refresh();

            $withdrawal->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'note' => $this->mergedNote($withdrawal->note, $note),
            ])->save();

            MerchantTransaction::query()->create([
                'merchant_id' => $merchant->getKey(),
                'order_id' => null,
                'type' => 'withdrawal',
                'amount' => -((float) $withdrawal->amount),
                'description' => sprintf(
                    'Withdrawal paid to %s (%s). Fee: $%s. Net payout: $%s.',
                    $withdrawal->bankAccount->bank_name,
                    $withdrawal->bankAccount->maskedAccountNumber(),
                    number_format((float) $withdrawal->fee_amount, 2, '.', ''),
                    number_format((float) $withdrawal->net_amount, 2, '.', ''),
                ),
            ]);

            $this->walletTransactionService->record(
                $merchant,
                'withdrawal',
                (float) $withdrawal->amount,
                'debit',
                Withdrawal::class,
                $withdrawal->getKey(),
                sprintf(
                    'Withdrawal paid to %s (%s).',
                    $withdrawal->bankAccount->bank_name,
                    $withdrawal->bankAccount->maskedAccountNumber(),
                ),
            );

            return $withdrawal->fresh(['merchant.user', 'bankAccount']);
        });
    }

    public function availableToRequest(Merchant $merchant): float
    {
        return round(max((float) $merchant->available_balance - (float) $merchant->pending_balance, 0), 2);
    }

    public function minimumAmount(): float
    {
        return round((float) config('withdrawals.minimum_amount', 10), 2);
    }

    public function feeAmount(): float
    {
        return round(max((float) config('withdrawals.fixed_fee', 0), 0), 2);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function mergedNote(?string $existing, ?string $incoming): ?string
    {
        $incoming = $this->nullableString($incoming);

        if ($incoming === null) {
            return $existing;
        }

        if ($existing === null || trim($existing) === '') {
            return $incoming;
        }

        return trim($existing).PHP_EOL.PHP_EOL.$incoming;
    }
}
