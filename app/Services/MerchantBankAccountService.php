<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantBankAccount;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MerchantBankAccountService
{
    public function metadata(): array
    {
        return [
            'bank_options' => collect(config('withdrawals.banks', []))
                ->map(fn (string $bank): array => ['label' => $bank, 'value' => $bank])
                ->values()
                ->all(),
            'currencies' => [
                ['label' => 'USD', 'value' => 'USD'],
                ['label' => 'KHR', 'value' => 'KHR'],
            ],
            'account_types' => [
                ['label' => 'Bank Account', 'value' => 'bank_account'],
                ['label' => 'KHQR', 'value' => 'khqr'],
            ],
            'statuses' => [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Approved', 'value' => 'approved'],
                ['label' => 'Rejected', 'value' => 'rejected'],
                ['label' => 'Disabled', 'value' => 'disabled'],
            ],
        ];
    }

    public function merchantRules(bool $updating = false): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => [$updating ? 'nullable' : 'required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:50'],
            'currency' => ['required', Rule::in(['USD', 'KHR'])],
            'account_type' => ['required', Rule::in(['bank_account', 'khqr'])],
            'qr_image' => ['nullable', 'image', 'max:4096'],
            'khqr_code' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function createForMerchant(Merchant $merchant, array $data, ?UploadedFile $qrImage = null): MerchantBankAccount
    {
        $this->ensureKhqrAssets($data, $qrImage, false);

        return DB::transaction(function () use ($merchant, $data, $qrImage): MerchantBankAccount {
            return $merchant->bankAccounts()->create([
                'bank_name' => $data['bank_name'],
                'account_holder_name' => $data['account_holder_name'],
                'account_number' => $data['account_number'],
                'phone_number' => $data['phone_number'],
                'currency' => $data['currency'],
                'account_type' => $data['account_type'],
                'qr_image_path' => $qrImage?->store('merchant/bank-accounts', 'public'),
                'khqr_code' => $this->nullableString($data['khqr_code'] ?? null),
                'is_default' => false,
                'status' => 'pending',
                'reject_reason' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'disabled_at' => null,
            ]);
        });
    }

    public function updateForMerchant(MerchantBankAccount $account, array $data, ?UploadedFile $qrImage = null): MerchantBankAccount
    {
        return DB::transaction(function () use ($account, $data, $qrImage): MerchantBankAccount {
            $account = MerchantBankAccount::query()
                ->with('merchant')
                ->lockForUpdate()
                ->findOrFail($account->getKey());

            if (($data['is_default'] ?? false) && $account->isApproved()) {
                $this->assignDefaultApprovedAccount($account->merchant, $account);

                return $account->fresh();
            }

            $this->ensureKhqrAssets($data, $qrImage, true, $account);

            if ($qrImage) {
                $this->deleteQrImage($account->qr_image_path);
                $account->qr_image_path = $qrImage->store('merchant/bank-accounts', 'public');
            }

            $account->fill([
                'bank_name' => $data['bank_name'],
                'account_holder_name' => $data['account_holder_name'],
                'phone_number' => $data['phone_number'],
                'currency' => $data['currency'],
                'account_type' => $data['account_type'],
                'khqr_code' => $this->nullableString($data['khqr_code'] ?? null),
                'status' => 'pending',
                'is_default' => false,
                'reject_reason' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'disabled_at' => null,
            ]);

            if (!empty($data['account_number'])) {
                $account->account_number = $data['account_number'];
            }

            if ($account->account_type !== 'khqr') {
                $this->deleteQrImage($account->qr_image_path);
                $account->qr_image_path = null;
                $account->khqr_code = null;
            }

            $account->save();
            $this->ensureApprovedFallbackDefault($account->merchant);

            return $account->fresh();
        });
    }

    public function deleteForMerchant(MerchantBankAccount $account): void
    {
        if ($account->withdrawals()->exists()) {
            throw ValidationException::withMessages([
                'account' => 'Bank account with withdrawal history cannot be deleted.',
            ]);
        }

        DB::transaction(function () use ($account): void {
            $merchant = $account->merchant()->firstOrFail();
            $this->deleteQrImage($account->qr_image_path);
            $account->delete();
            $this->ensureApprovedFallbackDefault($merchant);
        });
    }

    public function approve(MerchantBankAccount $account): MerchantBankAccount
    {
        return DB::transaction(function () use ($account): MerchantBankAccount {
            $account = MerchantBankAccount::query()->with('merchant')->lockForUpdate()->findOrFail($account->getKey());

            $account->forceFill([
                'status' => 'approved',
                'reject_reason' => null,
                'approved_at' => now(),
                'rejected_at' => null,
                'disabled_at' => null,
            ])->save();

            $merchant = $account->merchant()->firstOrFail();
            $approvedDefaultExists = $merchant->bankAccounts()
                ->where('status', 'approved')
                ->where('is_default', true)
                ->whereKeyNot($account->getKey())
                ->exists();

            if (!$approvedDefaultExists) {
                $this->assignDefaultApprovedAccount($merchant, $account);
            }

            return $account->fresh(['merchant.user']);
        });
    }

    public function reject(MerchantBankAccount $account, ?string $reason = null): MerchantBankAccount
    {
        return $this->setAdminStatus($account, 'rejected', $reason);
    }

    public function disable(MerchantBankAccount $account, ?string $reason = null): MerchantBankAccount
    {
        return $this->setAdminStatus($account, 'disabled', $reason);
    }

    public function deleteForAdmin(MerchantBankAccount $account): void
    {
        $this->deleteForMerchant($account);
    }

    public function payload(MerchantBankAccount $account, bool $withMerchant = false): array
    {
        $account->loadMissing('merchant.user');

        $payload = [
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_holder_name' => $account->account_holder_name,
            'account_number' => $account->maskedAccountNumber(),
            'account_number_last4' => substr((string) $account->account_number, -4),
            'phone_number' => $account->phone_number,
            'currency' => $account->currency,
            'account_type' => $account->account_type,
            'qr_image_url' => $account->qr_image_path ? Storage::disk('public')->url($account->qr_image_path) : null,
            'khqr_code' => $account->khqr_code,
            'is_default' => (bool) $account->is_default,
            'status' => $account->status,
            'reject_reason' => $account->reject_reason,
            'created_at' => $account->created_at?->toIso8601String(),
            'updated_at' => $account->updated_at?->toIso8601String(),
            'approved_at' => $account->approved_at?->toIso8601String(),
            'rejected_at' => $account->rejected_at?->toIso8601String(),
            'disabled_at' => $account->disabled_at?->toIso8601String(),
        ];

        if ($withMerchant) {
            $payload['merchant'] = [
                'id' => $account->merchant?->id,
                'shop_name' => $account->merchant?->shop_name,
                'owner_name' => $account->merchant?->user?->name,
                'email' => $account->merchant?->user?->email,
            ];
        }

        return $payload;
    }

    public function assignDefaultApprovedAccount(Merchant $merchant, MerchantBankAccount $account): void
    {
        if (!$account->isApproved()) {
            throw ValidationException::withMessages([
                'account' => 'Only approved bank accounts can be set as default.',
            ]);
        }

        $merchant->bankAccounts()->where('is_default', true)->update(['is_default' => false]);
        $account->forceFill(['is_default' => true])->save();
    }

    public function ensureApprovedFallbackDefault(Merchant $merchant): void
    {
        $approvedDefaultExists = $merchant->bankAccounts()
            ->where('status', 'approved')
            ->where('is_default', true)
            ->exists();

        if ($approvedDefaultExists) {
            return;
        }

        $fallback = $merchant->bankAccounts()
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->first();

        if ($fallback) {
            $merchant->bankAccounts()->where('is_default', true)->update(['is_default' => false]);
            $fallback->forceFill(['is_default' => true])->save();
        }
    }

    private function setAdminStatus(MerchantBankAccount $account, string $status, ?string $reason = null): MerchantBankAccount
    {
        return DB::transaction(function () use ($account, $status, $reason): MerchantBankAccount {
            $account = MerchantBankAccount::query()->with('merchant')->lockForUpdate()->findOrFail($account->getKey());

            $account->forceFill([
                'status' => $status,
                'is_default' => false,
                'reject_reason' => $this->nullableString($reason),
                'approved_at' => $status === 'approved' ? now() : null,
                'rejected_at' => $status === 'rejected' ? now() : null,
                'disabled_at' => $status === 'disabled' ? now() : null,
            ])->save();

            $this->ensureApprovedFallbackDefault($account->merchant()->firstOrFail());

            return $account->fresh(['merchant.user']);
        });
    }

    private function ensureKhqrAssets(array $data, ?UploadedFile $qrImage, bool $updating, ?MerchantBankAccount $account = null): void
    {
        if (($data['account_type'] ?? null) !== 'khqr') {
            return;
        }

        $hasExistingImage = $updating && $account?->qr_image_path;
        $hasExistingCode = $updating && $this->nullableString($account?->khqr_code) !== null;

        if (!$qrImage && !$hasExistingImage && !$hasExistingCode && $this->nullableString($data['khqr_code'] ?? null) === null) {
            throw ValidationException::withMessages([
                'qr_image' => 'QR image or KHQR code is required for KHQR accounts.',
            ]);
        }
    }

    private function deleteQrImage(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
