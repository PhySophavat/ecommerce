<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantDeposit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DepositService
{
    public function __construct(private readonly WalletTransactionService $walletTransactionService)
    {
    }

    public function create(Merchant $merchant, float $amount, string $paymentMethod, ?UploadedFile $paymentProof = null, ?string $note = null): MerchantDeposit
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be greater than zero.',
            ]);
        }

        $proofPath = $paymentProof?->store('merchant/deposits', 'public');

        return MerchantDeposit::query()->create([
            'merchant_id' => $merchant->getKey(),
            'amount' => round($amount, 2),
            'payment_method' => $paymentMethod,
            'khqr_code' => $paymentMethod === 'khqr' ? config('merchant_wallet.khqr_code') : null,
            'payment_proof' => $proofPath,
            'status' => 'pending',
            'note' => $this->nullableString($note),
        ])->load('merchant.user');
    }

    public function approve(MerchantDeposit $deposit, ?string $adminNote = null): MerchantDeposit
    {
        return DB::transaction(function () use ($deposit, $adminNote): MerchantDeposit {
            $deposit = MerchantDeposit::query()
                ->with('merchant')
                ->lockForUpdate()
                ->findOrFail($deposit->getKey());

            if ($deposit->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending deposits can be approved.',
                ]);
            }

            $merchant = Merchant::query()->lockForUpdate()->findOrFail($deposit->merchant_id);
            $merchant->increment('available_balance', $deposit->amount);
            $merchant->increment('balance_total', $deposit->amount);
            $merchant->increment('total_deposited', $deposit->amount);
            $merchant->refresh();

            $deposit->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'admin_note' => $this->mergedNote($deposit->admin_note, $adminNote),
            ])->save();

            $this->walletTransactionService->record(
                $merchant,
                'deposit',
                (float) $deposit->amount,
                'credit',
                MerchantDeposit::class,
                $deposit->getKey(),
                sprintf('Deposit approved via %s.', strtoupper($deposit->payment_method)),
            );

            return $deposit->fresh(['merchant.user']);
        });
    }

    public function reject(MerchantDeposit $deposit, ?string $adminNote = null): MerchantDeposit
    {
        return DB::transaction(function () use ($deposit, $adminNote): MerchantDeposit {
            $deposit = MerchantDeposit::query()
                ->with('merchant.user')
                ->lockForUpdate()
                ->findOrFail($deposit->getKey());

            if ($deposit->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Only pending deposits can be rejected.',
                ]);
            }

            $deposit->forceFill([
                'status' => 'rejected',
                'rejected_at' => now(),
                'admin_note' => $this->mergedNote($deposit->admin_note, $adminNote),
            ])->save();

            return $deposit->fresh(['merchant.user']);
        });
    }

    public function proofUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
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
