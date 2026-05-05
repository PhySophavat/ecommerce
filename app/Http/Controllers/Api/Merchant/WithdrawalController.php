<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request)->load([
            'bankAccounts' => fn ($query) => $query->where('status', 'approved')->orderByDesc('is_default')->orderByDesc('id'),
        ]);

        $withdrawals = $merchant->withdrawals()
            ->with('bankAccount')
            ->latest()
            ->get();

        return response()->json([
            'balances' => $this->balancesPayload($merchant),
            'minimum_amount' => number_format($this->withdrawalService->minimumAmount(), 2, '.', ''),
            'withdraw_fee' => number_format($this->withdrawalService->feeAmount(), 2, '.', ''),
            'bank_accounts' => $merchant->bankAccounts->map(fn ($account): array => [
                'id' => $account->id,
                'label' => sprintf('%s - %s (%s)', $account->bank_name, $account->account_holder_name, $account->maskedAccountNumber()),
                'is_default' => (bool) $account->is_default,
                'currency' => $account->currency,
                'account_type' => $account->account_type,
            ])->values()->all(),
            'withdrawals' => $withdrawals->map(fn (Withdrawal $withdrawal): array => $this->payload($withdrawal))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:merchant_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'in:USD,KHR'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validated['currency'] === 'KHR' && floor((float) $validated['amount']) !== (float) $validated['amount']) {
            return response()->json([
                'message' => 'The amount field must be a whole number when currency is KHR.',
                'errors' => [
                    'amount' => ['The amount field must be a whole number when currency is KHR.'],
                ],
            ], 422);
        }

        $bankAccount = $merchant->bankAccounts()
            ->whereKey($validated['bank_account_id'])
            ->where('status', 'approved')
            ->where('currency', $validated['currency'])
            ->firstOrFail();

        $withdrawal = $this->withdrawalService->create(
            $merchant,
            $bankAccount,
            $validated['currency'] === 'KHR'
                ? (float) ((int) $validated['amount'])
                : round((float) $validated['amount'], 2),
            $validated['currency'],
            $validated['note'] ?? null,
        );

        return response()->json([
            'message' => 'Withdrawal request submitted successfully.',
            'balances' => $this->balancesPayload($merchant->fresh()),
            'withdrawal' => $this->payload($withdrawal),
        ], 201);
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->firstOrFail();
    }

    private function balancesPayload(Merchant $merchant): array
    {
        return [
            'balance_total' => number_format((float) $merchant->balance_total, 2, '.', ''),
            'available_balance' => number_format((float) $merchant->available_balance, 2, '.', ''),
            'pending_balance' => number_format((float) $merchant->pending_balance, 2, '.', ''),
            'available_to_withdraw' => number_format($this->withdrawalService->availableToRequest($merchant), 2, '.', ''),
        ];
    }

    private function payload(Withdrawal $withdrawal): array
    {
        $withdrawal->loadMissing('bankAccount');

        return [
            'id' => $withdrawal->id,
            'amount' => number_format((float) $withdrawal->amount, 2, '.', ''),
            'currency' => $withdrawal->currency ?? 'USD',
            'fee_amount' => number_format((float) $withdrawal->fee_amount, 2, '.', ''),
            'net_amount' => number_format((float) $withdrawal->net_amount, 2, '.', ''),
            'status' => $withdrawal->status,
            'note' => $withdrawal->note,
            'bank_account' => [
                'id' => $withdrawal->bankAccount?->id,
                'bank_name' => $withdrawal->bankAccount?->bank_name,
                'account_holder_name' => $withdrawal->bankAccount?->account_holder_name,
                'account_number' => $withdrawal->bankAccount?->maskedAccountNumber(),
                'account_type' => $withdrawal->bankAccount?->account_type,
                'currency' => $withdrawal->bankAccount?->currency,
            ],
            'created_at' => $withdrawal->created_at?->toIso8601String(),
            'approved_at' => $withdrawal->approved_at?->toIso8601String(),
            'rejected_at' => $withdrawal->rejected_at?->toIso8601String(),
            'paid_at' => $withdrawal->paid_at?->toIso8601String(),
        ];
    }
}
