<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantDeposit;
use App\Models\MerchantTransaction;
use App\Models\MerchantBalance;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\WithdrawRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinanceReportingService
{
    public function syncMerchantBalance(Merchant $merchant): MerchantBalance
    {
        $merchant->refresh();

        $totalIn = (float) Transaction::query()
            ->where('merchant_id', $merchant->getKey())
            ->where('type', 'IN')
            ->where('status', 'success')
            ->sum('amount');

        $totalOut = (float) Transaction::query()
            ->where('merchant_id', $merchant->getKey())
            ->where('type', 'OUT')
            ->where('status', 'success')
            ->sum('amount');

        return MerchantBalance::query()->updateOrCreate(
            ['merchant_id' => $merchant->getKey()],
            [
                'total_balance' => round((float) $merchant->balance_total, 2),
                'available_balance' => round((float) $merchant->available_balance, 2),
                'pending_balance' => round((float) $merchant->pending_balance, 2),
                'total_in' => round($totalIn, 2),
                'total_out' => round($totalOut, 2),
                'currency' => 'USD',
            ],
        );
    }

    public function syncOrder(Order $order): void
    {
        $order->loadMissing('items');

        $merchantGroups = $this->merchantGroups($order);

        foreach ($merchantGroups as $merchantId => $group) {
            $merchant = Merchant::query()->find($merchantId);

            if (! $merchant) {
                continue;
            }

            $amount = round((float) $group['amount'], 2);
            $method = $this->normalizeMethod($order->payment_method);
            $paymentStatus = $this->normalizePaymentStatus($order->payment_status);
            $paymentCode = sprintf('PAY-%s-%s', $order->getKey(), $merchantId);

            Payment::query()->updateOrCreate(
                [
                    'order_id' => $order->getKey(),
                    'merchant_id' => $merchantId,
                ],
                [
                    'payment_code' => $paymentCode,
                    'customer_id' => $order->customer_id,
                    'payment_method' => $method,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'status' => $paymentStatus,
                    'paid_at' => $order->paid_at,
                ],
            );

            if ($paymentStatus === 'success') {
                $this->upsertTransaction(
                    sprintf('TXN-PAY-%s-%s', $order->getKey(), $merchantId),
                    $merchant,
                    $order,
                    'IN',
                    $amount,
                    'USD',
                    $method,
                    'success',
                    sprintf('Customer payment received for order %s.', $order->number),
                );
            }

            if (in_array($paymentStatus, ['failed', 'pending', 'cancelled'], true)) {
                $this->deleteTransaction(sprintf('TXN-PAY-%s-%s', $order->getKey(), $merchantId));
            }

            $shouldRecordRefund = in_array($order->status, ['cancelled', 'failed', 'payment_failed', 'refunded'], true)
                || $paymentStatus === 'cancelled';

            if ($shouldRecordRefund) {
                $this->upsertTransaction(
                    sprintf('TXN-RFD-%s-%s', $order->getKey(), $merchantId),
                    $merchant,
                    $order,
                    'OUT',
                    $amount,
                    'USD',
                    $method,
                    'success',
                    sprintf('Refund or reversal recorded for order %s.', $order->number),
                );
            } else {
                $this->deleteTransaction(sprintf('TXN-RFD-%s-%s', $order->getKey(), $merchantId));
            }

            $this->syncMerchantBalance($merchant);
        }
    }

    public function syncDeposit(MerchantDeposit $deposit): void
    {
        $merchant = $deposit->merchant()->first();

        if (! $merchant) {
            return;
        }

        $status = match ($deposit->status) {
            'approved' => 'success',
            'rejected' => 'failed',
            default => 'pending',
        };

        $this->upsertTransaction(
            sprintf('TXN-DEP-%s-%s', $deposit->getKey(), $merchant->getKey()),
            $merchant,
            null,
            'IN',
            (float) $deposit->amount,
            'USD',
            $this->normalizeMethod($deposit->bank_name ?: $deposit->payment_method),
            $status,
            sprintf('Merchant deposit via %s.', $deposit->bank_name ?: 'Cash'),
            $deposit->created_at,
        );

        $this->syncMerchantBalance($merchant);
    }

    public function syncWithdrawal(Withdrawal $withdrawal): void
    {
        $withdrawal->loadMissing(['merchant', 'bankAccount']);

        $merchant = $withdrawal->merchant;

        if (! $merchant) {
            return;
        }

        $status = match ($withdrawal->status) {
            'paid' => 'success',
            'rejected' => 'failed',
            'approved', 'pending' => 'pending',
            default => 'cancelled',
        };

        WithdrawRequest::query()->updateOrCreate(
            ['withdrawal_id' => $withdrawal->getKey()],
            [
                'merchant_id' => $merchant->getKey(),
                'merchant_bank_account_id' => $withdrawal->bank_account_id,
                'amount' => round((float) $withdrawal->amount, 2),
                'currency' => strtoupper((string) $withdrawal->currency ?: 'USD'),
                'status' => $status,
                'note' => $withdrawal->note,
                'requested_at' => $withdrawal->created_at,
                'processed_at' => $withdrawal->paid_at ?? $withdrawal->rejected_at ?? $withdrawal->approved_at,
            ],
        );

        $this->upsertTransaction(
            sprintf('TXN-WDR-%s-%s', $withdrawal->getKey(), $merchant->getKey()),
            $merchant,
            null,
            'OUT',
            (float) $withdrawal->amount,
            strtoupper((string) $withdrawal->currency ?: 'USD'),
            $this->normalizeMethod($withdrawal->bankAccount?->bank_name),
            $status,
            sprintf('Withdraw request to %s.', $withdrawal->bankAccount?->bank_name ?: 'bank account'),
            $withdrawal->created_at,
        );

        $this->syncMerchantBalance($merchant);
    }

    public function syncMerchantTransaction(MerchantTransaction $merchantTransaction, ?string $method = null): void
    {
        if ($merchantTransaction->type !== 'platform_fee') {
            return;
        }

        $merchant = $merchantTransaction->merchant()->first();

        if (! $merchant) {
            return;
        }

        $amount = round(abs((float) $merchantTransaction->amount), 2);

        $this->upsertTransaction(
            sprintf('TXN-MTX-%s', $merchantTransaction->getKey()),
            $merchant,
            $merchantTransaction->order,
            'OUT',
            $amount,
            'USD',
            $this->normalizeMethod($method),
            'success',
            $merchantTransaction->description,
            $merchantTransaction->created_at,
        );

        $this->syncMerchantBalance($merchant);
    }

    public function rebuildSnapshots(?Merchant $merchant = null): void
    {
        $merchantIds = $merchant
            ? collect([$merchant->getKey()])
            : Merchant::query()->pluck('id');

        if ($merchantIds->isEmpty()) {
            return;
        }

        Order::query()
            ->whereHas('items', fn (Builder $query) => $query->whereIn('merchant_id', $merchantIds))
            ->with('items')
            ->get()
            ->each(fn (Order $order) => $this->syncOrder($order));

        MerchantDeposit::query()
            ->whereIn('merchant_id', $merchantIds)
            ->with('merchant')
            ->get()
            ->each(fn (MerchantDeposit $deposit) => $this->syncDeposit($deposit));

        Withdrawal::query()
            ->whereIn('merchant_id', $merchantIds)
            ->with(['merchant', 'bankAccount'])
            ->get()
            ->each(fn (Withdrawal $withdrawal) => $this->syncWithdrawal($withdrawal));

        MerchantTransaction::query()
            ->whereIn('merchant_id', $merchantIds)
            ->with(['merchant', 'order'])
            ->get()
            ->each(fn (MerchantTransaction $merchantTransaction) => $this->syncMerchantTransaction($merchantTransaction));

        Merchant::query()
            ->whereIn('id', $merchantIds)
            ->get()
            ->each(fn (Merchant $row) => $this->syncMerchantBalance($row));
    }

    public function overview(?Merchant $merchant = null): array
    {
        $merchantIds = $merchant
            ? collect([$merchant->getKey()])
            : Merchant::query()->pluck('id');

        $balanceQuery = MerchantBalance::query()->whereIn('merchant_id', $merchantIds);
        $transactionQuery = Transaction::query()->whereIn('merchant_id', $merchantIds);
        $paymentQuery = Payment::query()->whereIn('merchant_id', $merchantIds);
        $orderQuery = Order::query()
            ->when(
                $merchant !== null,
                fn (Builder $query) => $query->whereHas('items', fn (Builder $itemQuery) => $itemQuery->where('merchant_id', $merchant->getKey()))
            );

        $orders = $orderQuery->with('items')->get();

        $orderCounts = [
            'Success' => 0,
            'Failed' => 0,
            'Cancelled' => 0,
            'Pending' => 0,
        ];

        foreach ($orders as $order) {
            $bucket = $this->orderBucket($order->status);
            $orderCounts[$bucket] += 1;
        }

        return [
            'cards' => [
                'total_balance' => round((float) $balanceQuery->sum('total_balance'), 2),
                'total_transactions' => (int) $transactionQuery->count(),
                'total_in' => round((float) (clone $transactionQuery)->where('type', 'IN')->where('status', 'success')->sum('amount'), 2),
                'total_out' => round((float) (clone $transactionQuery)->where('type', 'OUT')->where('status', 'success')->sum('amount'), 2),
                'successful_orders' => $orderCounts['Success'],
                'failed_orders' => $orderCounts['Failed'],
                'successful_payments' => (int) (clone $paymentQuery)->where('status', 'success')->count(),
                'failed_payments' => (int) (clone $paymentQuery)->where('status', 'failed')->count(),
            ],
            'charts' => [
                'transaction_flow' => [
                    ['label' => 'IN', 'value' => round((float) (clone $transactionQuery)->where('type', 'IN')->where('status', 'success')->sum('amount'), 2), 'color' => '#A25F88'],
                    ['label' => 'OUT', 'value' => round((float) (clone $transactionQuery)->where('type', 'OUT')->where('status', 'success')->sum('amount'), 2), 'color' => '#E7B6D1'],
                ],
                'payments_by_bank' => collect(['ABA', 'ACLEDA', 'Wing', 'Cash', 'Card'])
                    ->map(fn (string $method): array => [
                        'label' => $method,
                        'value' => (int) (clone $paymentQuery)->where('payment_method', $method)->count(),
                        'color' => [
                            'ABA' => '#A25F88',
                            'ACLEDA' => '#C77CA2',
                            'Wing' => '#DCA5C0',
                            'Cash' => '#EAC7D9',
                            'Card' => '#F2DCE7',
                        ][$method],
                    ])->values()->all(),
                'orders_by_status' => [
                    ['label' => 'Success', 'value' => $orderCounts['Success'], 'color' => '#A25F88'],
                    ['label' => 'Failed', 'value' => $orderCounts['Failed'], 'color' => '#D88AAA'],
                    ['label' => 'Cancelled', 'value' => $orderCounts['Cancelled'], 'color' => '#E7B6D1'],
                    ['label' => 'Pending', 'value' => $orderCounts['Pending'], 'color' => '#F3DCE8'],
                ],
            ],
            'recent_transactions' => $transactionQuery
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn (Transaction $transaction): array => [
                    'transaction_code' => $transaction->transaction_code,
                    'type' => $transaction->type,
                    'amount' => number_format((float) $transaction->amount, 2, '.', ''),
                    'currency' => $transaction->currency,
                    'method' => $transaction->method,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'meta' => [
                'merchant_count' => $merchant ? 1 : (int) $merchantIds->count(),
                'payment_count' => (int) $paymentQuery->count(),
                'order_count' => (int) $orders->count(),
            ],
        ];
    }

    private function merchantGroups(Order $order): Collection
    {
        return $order->items
            ->filter(fn ($item) => $item->merchant_id !== null)
            ->groupBy('merchant_id')
            ->map(fn (Collection $items): array => [
                'amount' => (float) $items->sum('line_total'),
            ]);
    }

    private function upsertTransaction(
        string $code,
        Merchant $merchant,
        ?Order $order,
        string $type,
        float $amount,
        string $currency,
        string $method,
        string $status,
        ?string $description = null,
        $createdAt = null,
    ): Transaction {
        $transaction = Transaction::query()->updateOrCreate(
            ['transaction_code' => $code],
            [
                'merchant_id' => $merchant->getKey(),
                'order_id' => $order?->getKey(),
                'type' => $type,
                'amount' => round(abs($amount), 2),
                'currency' => in_array(strtoupper($currency), ['USD', 'KHR'], true) ? strtoupper($currency) : 'USD',
                'method' => $method,
                'status' => $status,
                'description' => $description,
            ],
        );

        if ($createdAt !== null) {
            $transaction->timestamps = false;
            $transaction->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
            $transaction->timestamps = true;
        }

        return $transaction;
    }

    private function deleteTransaction(string $code): void
    {
        Transaction::query()->where('transaction_code', $code)->delete();
    }

    private function normalizeMethod(?string $value): string
    {
        return match (strtolower((string) $value)) {
            'aba', 'aba_qr', 'aba qr' => 'ABA',
            'acleda' => 'ACLEDA',
            'wing' => 'Wing',
            'card' => 'Card',
            default => 'Cash',
        };
    }

    private function normalizePaymentStatus(?string $value): string
    {
        return match (strtolower((string) $value)) {
            'paid', 'success' => 'success',
            'failed', 'rejected' => 'failed',
            'refunded', 'cancelled', 'canceled' => 'cancelled',
            default => 'pending',
        };
    }

    private function orderBucket(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'cancelled' => 'Cancelled',
            'failed', 'payment_failed', 'refunded' => 'Failed',
            'pending' => 'Pending',
            default => 'Success',
        };
    }
}
