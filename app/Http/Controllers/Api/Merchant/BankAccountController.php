<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $accounts = $merchant->bankAccounts()->orderByDesc('is_default')->orderByDesc('id')->get();

        return response()->json([
            'accounts' => $accounts->map(fn (MerchantBankAccount $account): array => $this->payload($account))->all(),
            'meta' => [
                'bank_options' => collect(config('withdrawals.banks', []))
                    ->map(fn (string $bank): array => ['label' => $bank, 'value' => $bank])
                    ->values()
                    ->all(),
                'account_types' => [
                    ['label' => 'Bank', 'value' => 'bank'],
                    ['label' => 'E-Wallet', 'value' => 'ewallet'],
                ],
                'statuses' => [
                    ['label' => 'Active', 'value' => 'active'],
                    ['label' => 'Inactive', 'value' => 'inactive'],
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate($this->rules());

        $account = DB::transaction(function () use ($merchant, $validated): MerchantBankAccount {
            $shouldDefault = (bool) ($validated['is_default'] ?? false) || ! $merchant->bankAccounts()->exists();

            if ($shouldDefault) {
                $merchant->bankAccounts()->update(['is_default' => false]);
            }

            return $merchant->bankAccounts()->create([
                ...$validated,
                'is_default' => $shouldDefault,
            ]);
        });

        return response()->json([
            'message' => 'Bank account added successfully.',
            'account' => $this->payload($account),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $account = $merchant->bankAccounts()->findOrFail($id);
        $validated = $request->validate($this->rules(true));

        DB::transaction(function () use ($merchant, $account, $validated): void {
            $shouldDefault = (bool) ($validated['is_default'] ?? false);

            if ($shouldDefault) {
                $merchant->bankAccounts()
                    ->whereKeyNot($account->getKey())
                    ->update(['is_default' => false]);
            }

            $account->fill([
                'bank_name' => $validated['bank_name'],
                'account_name' => $validated['account_name'],
                'account_type' => $validated['account_type'],
                'status' => $validated['status'],
                'is_default' => $shouldDefault ?: ($account->is_default && $validated['status'] === 'active'),
            ]);

            if (!empty($validated['account_number'])) {
                $account->account_number = $validated['account_number'];
            }

            if ($account->status === 'inactive' && $account->is_default) {
                $account->is_default = false;
            }

            $account->save();

            if (! $merchant->bankAccounts()->where('is_default', true)->exists()) {
                $fallback = $merchant->bankAccounts()
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if ($fallback) {
                    $fallback->forceFill(['is_default' => true])->save();
                }
            }
        });

        return response()->json([
            'message' => 'Bank account updated successfully.',
            'account' => $this->payload($account->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $merchant = $this->merchant($request);
        $account = $merchant->bankAccounts()->withCount('withdrawals')->findOrFail($id);

        if ($account->withdrawals_count > 0) {
            return response()->json([
                'message' => 'Bank account with withdrawal history cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($merchant, $account): void {
            $wasDefault = (bool) $account->is_default;
            $account->delete();

            if ($wasDefault) {
                $fallback = $merchant->bankAccounts()
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if ($fallback) {
                    $fallback->forceFill(['is_default' => true])->save();
                }
            }
        });

        return response()->json([
            'message' => 'Bank account deleted successfully.',
        ]);
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->firstOrFail();
    }

    private function rules(bool $updating = false): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => [$updating ? 'nullable' : 'required', 'string', 'max:255'],
            'account_type' => ['required', Rule::in(['bank', 'ewallet'])],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    private function payload(MerchantBankAccount $account): array
    {
        return [
            'id' => $account->id,
            'bank_name' => $account->bank_name,
            'account_name' => $account->account_name,
            'account_number' => $account->maskedAccountNumber(),
            'account_number_last4' => substr($account->account_number, -4),
            'account_type' => $account->account_type,
            'is_default' => (bool) $account->is_default,
            'status' => $account->status,
            'created_at' => $account->created_at?->toIso8601String(),
            'updated_at' => $account->updated_at?->toIso8601String(),
        ];
    }
}
