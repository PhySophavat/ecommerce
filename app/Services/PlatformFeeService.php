<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantTransaction;
use App\Models\Order;
use App\Models\PlatformFeeSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlatformFeeService
{
    private const MINIMUM_FEEABLE_TOTAL = 1.00;

    public function __construct(
        private readonly WalletTransactionService $walletTransactionService,
        private readonly FinanceReportingService $financeReportingService,
    )
    {
    }

    /**
     * Apply merchant payout and platform fee once for a qualifying order.
     *
     * In this multi-merchant catalog, fees are calculated per merchant subtotal
     * derived from the merchant's own order items instead of the full order total.
     */
    public function processOrder(Order $order): bool
    {
        $setting = PlatformFeeSetting::current();

        if (!$order->shouldApplyPlatformFeeForStage($setting->apply_stage)) {
            return false;
        }

        if ($order->platform_fee_processed_at) {
            return false;
        }

        $order->loadMissing(['items.merchant', 'items.product.merchant.merchant']);

        $merchantGroups = $order->items
            ->filter(fn ($item) => $item->merchant_id !== null)
            ->groupBy('merchant_id');

        if ($merchantGroups->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($merchantGroups, $order, $setting): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->platform_fee_processed_at) {
                return;
            }

            foreach ($merchantGroups as $merchantId => $items) {
                $this->applyForMerchant(
                    Merchant::query()->lockForUpdate()->findOrFail($merchantId),
                    $lockedOrder,
                    collect($items),
                    $setting,
                );
            }

            $lockedOrder->forceFill([
                'platform_fee_processed_at' => now(),
                'platform_fee_processed_stage' => $setting->apply_stage,
            ])->save();
        });

        return true;
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    private function applyForMerchant(Merchant $merchant, Order $order, Collection $items, PlatformFeeSetting $setting): void
    {
        $merchantOrderTotal = round((float) $items->sum(fn ($item) => (float) $item->line_total), 2);
        $fee = min($this->calculateFee($merchantOrderTotal, $setting), $merchantOrderTotal);
        $merchantReceive = round($merchantOrderTotal - $fee, 2);

        $merchant->increment('balance_total', $merchantOrderTotal);
        $merchant->increment('available_balance', $merchantOrderTotal);
        $merchant->refresh();

        MerchantTransaction::query()->create([
            'merchant_id' => $merchant->getKey(),
            'order_id' => $order->getKey(),
            'type' => 'sale',
            'amount' => $merchantOrderTotal,
            'description' => sprintf('Sale recorded for order %s.', $order->number),
        ]);

        MerchantTransaction::query()->create([
            'merchant_id' => $merchant->getKey(),
            'order_id' => $order->getKey(),
            'type' => 'platform_fee',
            'amount' => -$fee,
            'description' => sprintf('Platform fee deducted for order %s.', $order->number),
        ]);

        $this->walletTransactionService->record(
            $merchant,
            'sale',
            $merchantOrderTotal,
            'credit',
            Order::class,
            $order->getKey(),
            sprintf('Sale credited from order %s.', $order->number),
        );

        if ($fee > 0) {
            $merchant->decrement('balance_total', $fee);
            $merchant->decrement('available_balance', $fee);
            $merchant->increment('total_platform_fee_paid', $fee);
            $merchant->refresh();

            $this->walletTransactionService->record(
                $merchant,
                'platform_fee',
                $fee,
                'debit',
                Order::class,
                $order->getKey(),
                sprintf('Platform fee debited for order %s.', $order->number),
            );
        }

        $merchant->loadMissing('transactions');
        $merchant->transactions()->where('order_id', $order->getKey())->get()
            ->each(fn (MerchantTransaction $transaction) => $this->financeReportingService->syncMerchantTransaction(
                $transaction,
                $order->payment_method,
            ));
    }

    private function calculateFee(float $merchantOrderTotal, PlatformFeeSetting $setting): float
    {
        if (
            !$setting->is_enabled
            || round($merchantOrderTotal, 2) < self::MINIMUM_FEEABLE_TOTAL
        ) {
            return 0.0;
        }

        $fee = $setting->fee_type === 'fixed'
            ? (float) $setting->fee_value
            : round(($merchantOrderTotal * (float) $setting->fee_value) / 100, 2);

        if ($fee < 0) {
            return 0.0;
        }

        return round($fee, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(?PlatformFeeSetting $setting = null, float $orderTotal = 100): array
    {
        $setting ??= PlatformFeeSetting::current();
        $fee = min($this->calculateFee($orderTotal, $setting), $orderTotal);
        $merchantReceive = round(max($orderTotal - $fee, 0), 2);

        return [
            'order_total' => number_format($orderTotal, 2, '.', ''),
            'platform_fee' => number_format($fee, 2, '.', ''),
            'merchant_receives' => number_format($merchantReceive, 2, '.', ''),
            'platform_earns' => number_format($fee, 2, '.', ''),
            'fee_label' => $setting->fee_type === 'percentage'
                ? rtrim(rtrim(number_format((float) $setting->fee_value, 2, '.', ''), '0'), '.').'%' 
                : '$'.number_format((float) $setting->fee_value, 2, '.', ''),
        ];
    }
}
