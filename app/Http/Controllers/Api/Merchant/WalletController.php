<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\WalletTransaction;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request)->load([
            'walletTransactions' => fn ($query) => $query->limit((int) config('merchant_wallet.recent_transactions_limit', 8)),
        ]);

        return response()->json([
            'wallet' => $this->walletPayload($merchant),
            'recent_transactions' => $merchant->walletTransactions
                ->map(fn (WalletTransaction $transaction): array => $this->transactionPayload($transaction))
                ->values()
                ->all(),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $merchant = $this->merchant($request);
        $type = (string) $request->query('type', 'all');

        $query = $merchant->walletTransactions();

        if ($type !== '' && $type !== 'all') {
            $query->where('type', $type);
        }

        $transactions = $query->get();

        return response()->json([
            'wallet' => $this->walletPayload($merchant->fresh()),
            'filters' => [
                ['label' => 'All', 'value' => 'all'],
                ['label' => 'Sales', 'value' => 'sale'],
                ['label' => 'Deposits', 'value' => 'deposit'],
                ['label' => 'Withdrawals', 'value' => 'withdrawal'],
                ['label' => 'Platform Fees', 'value' => 'platform_fee'],
                ['label' => 'Adjustments', 'value' => 'adjustment'],
            ],
            'selected_type' => $type ?: 'all',
            'transactions' => $transactions->map(fn (WalletTransaction $transaction): array => $this->transactionPayload($transaction))->all(),
        ]);
    }

    private function merchant(Request $request): Merchant
    {
        return $request->user()->merchant()->firstOrFail();
    }

    private function walletPayload(Merchant $merchant): array
    {
        return [
            'balance_total' => number_format((float) $merchant->balance_total, 2, '.', ''),
            'available_balance' => number_format((float) $merchant->available_balance, 2, '.', ''),
            'pending_balance' => number_format((float) $merchant->pending_balance, 2, '.', ''),
            'available_to_withdraw' => number_format($this->withdrawalService->availableToRequest($merchant), 2, '.', ''),
            'total_withdrawn' => number_format((float) $merchant->total_withdrawn, 2, '.', ''),
            'total_deposited' => number_format((float) $merchant->total_deposited, 2, '.', ''),
            'total_platform_fee_paid' => number_format((float) $merchant->total_platform_fee_paid, 2, '.', ''),
        ];
    }

    private function transactionPayload(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'direction' => $transaction->direction,
            'balance_after' => number_format((float) $transaction->balance_after, 2, '.', ''),
            'reference_type' => $transaction->reference_type,
            'reference_id' => $transaction->reference_id,
            'description' => $transaction->description,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
