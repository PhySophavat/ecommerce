<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantDeposit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DepositService
{
    public function __construct(private readonly WalletTransactionService $walletTransactionService)
    {
    }

    public function create(
        Merchant $merchant,
        float $amount,
        string $bankName,
        string $accountName,
        string $accountNumber,
        string $phoneNumber,
        ?UploadedFile $paymentProof = null,
        ?string $note = null,
    ): MerchantDeposit
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be greater than zero.',
            ]);
        }

        $provider = $this->provider($bankName);
        $proofPath = $paymentProof?->store('merchant/deposits', 'public');

        return DB::transaction(function () use (
            $merchant,
            $provider,
            $accountName,
            $accountNumber,
            $phoneNumber,
            $amount,
            $proofPath,
            $note
        ): MerchantDeposit {
            $merchant = Merchant::query()->lockForUpdate()->findOrFail($merchant->getKey());
            $amount = round($amount, 2);

            $deposit = MerchantDeposit::query()->create([
                'merchant_id' => $merchant->getKey(),
                'bank_name' => $provider['bank_name'],
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'phone_number' => $phoneNumber,
                'amount' => $amount,
                'payment_method' => 'khqr',
                'khqr_code' => $this->buildKhqrCode($merchant, $provider, $amount),
                'payment_proof' => $proofPath,
                'status' => 'approved',
                'note' => $this->nullableString($note),
                'admin_note' => 'Auto-approved on merchant submission.',
                'approved_at' => now(),
            ]);

            $merchant->increment('available_balance', $amount);
            $merchant->increment('balance_total', $amount);
            $merchant->increment('total_deposited', $amount);
            $merchant->refresh();

            $this->walletTransactionService->record(
                $merchant,
                'deposit',
                $amount,
                'credit',
                MerchantDeposit::class,
                $deposit->getKey(),
                sprintf('Deposit submitted via %s and credited automatically.', strtoupper((string) $provider['bank_name'])),
            );

            return $deposit->fresh(['merchant.user']);
        });
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
                sprintf('Deposit approved via %s.', strtoupper((string) ($deposit->bank_name ?: $deposit->payment_method))),
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

    /**
     * @return array{bank_name:string,merchant_name:string,account_name:string,account_number:string,phone_number:string,khqr_prefix:string}
     */
    public function provider(string $bankName): array
    {
        $providers = config('merchant_wallet.providers', []);

        if (! array_key_exists($bankName, $providers)) {
            throw ValidationException::withMessages([
                'bank_name' => 'The selected bank is invalid.',
            ]);
        }

        return $providers[$bankName];
    }

    /**
     * @return array<int, array{bank_name:string,merchant_name:string,account_name:string,account_number:string,phone_number:string,khqr_prefix:string}>
     */
    public function providers(): array
    {
        return array_values(config('merchant_wallet.providers', []));
    }

    /**
     * @param  array{bank_name:string,merchant_name:string,account_name:string,account_number:string,phone_number:string,khqr_prefix:string}  $provider
     */
    public function buildKhqrCode(Merchant $merchant, array $provider, float $amount): string
    {
        $merchantName = $merchant->shop_name ?: $merchant->user?->name ?: 'Merchant';

        return implode('|', [
            Arr::get($provider, 'khqr_prefix', 'KHQR'),
            'BANK:'.Arr::get($provider, 'bank_name', 'KHQR'),
            'MERCHANT:'.$merchantName,
            'ACCOUNT:'.Arr::get($provider, 'account_number', ''),
            'AMOUNT:'.number_format($amount, 2, '.', ''),
            'COUNTRY:KH',
        ]);
    }
}
