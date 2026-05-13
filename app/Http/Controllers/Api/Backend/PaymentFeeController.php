<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantTransaction;
use App\Models\Order;
use App\Models\PlatformFeeSetting;
use App\Support\AdminDashboardData;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;

class PaymentFeeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $collectedRecords = MerchantTransaction::query()
            ->with(['merchant.user', 'order'])
            ->where('type', 'platform_fee')
            ->latest('id')
            ->get();
        $pendingRecords = $this->pendingRecords();
        $records = $collectedRecords
            ->map(function (MerchantTransaction $transaction): array {
                $feeAmount = abs((float) $transaction->amount);

                return [
                    'id' => 'collected-'.$transaction->id,
                    'merchant_id' => $transaction->merchant_id,
                    'merchant_name' => $transaction->merchant?->shop_name ?? 'Unknown merchant',
                    'owner_name' => $transaction->merchant?->user?->name,
                    'order_id' => $transaction->order_id,
                    'order_number' => $transaction->order?->number,
                    'amount' => number_format($feeAmount, 2, '.', ''),
                    'description' => $transaction->description,
                    'status' => 'collected',
                    'created_at' => optional($transaction->created_at)?->toIso8601String(),
                    'created_at_label' => optional($transaction->created_at)?->format('d M Y, h:i A'),
                ];
            })
            ->concat($pendingRecords)
            ->sortByDesc(fn (array $record) => $record['created_at'] ?? '')
            ->values();

        $platformFeeTotal = (float) Merchant::query()->sum('total_platform_fee_paid');
        $merchantIds = $records->pluck('merchant_id')->filter()->unique()->count();

        return response()->json([
            ...AdminDashboardData::paymentFeesPage(),
            'summary' => [
                'total_fees' => number_format($platformFeeTotal + (float) $pendingRecords->sum(fn (array $record) => (float) $record['amount']), 2, '.', ''),
                'fee_records' => $records->count(),
                'merchants_charged' => $merchantIds,
                'average_fee' => number_format($records->count() > 0 ? $platformFeeTotal / $records->count() : 0, 2, '.', ''),
            ],
            'records' => $records->all(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingRecords(): Collection
    {
        $setting = PlatformFeeSetting::current();

        return Order::query()
            ->with(['items.merchant.user'])
            ->whereNull('platform_fee_processed_at')
            ->where(function ($query): void {
                $query->whereNotNull('payment_reference')
                    ->orWhere('payment_status', 'paid');
            })
            ->latest('placed_at')
            ->get()
            ->flatMap(function (Order $order) use ($setting): Collection {
                return $order->items
                    ->filter(fn ($item) => $item->merchant_id !== null)
                    ->groupBy('merchant_id')
                    ->map(function (Collection $items, $merchantId) use ($order, $setting): array {
                        $merchant = $items->first()?->merchant;
                        $subtotal = round((float) $items->sum('line_total'), 2);
                        $fee = $this->calculateFee($subtotal, $setting);

                        return [
                            'id' => 'pending-'.$order->id.'-'.$merchantId,
                            'merchant_id' => (int) $merchantId,
                            'merchant_name' => $merchant?->shop_name ?? 'Unknown merchant',
                            'owner_name' => $merchant?->user?->name,
                            'order_id' => $order->id,
                            'order_number' => $order->number,
                            'amount' => number_format($fee, 2, '.', ''),
                            'description' => sprintf('Pending fee from customer payment for order %s.', $order->number),
                            'status' => 'pending',
                            'created_at' => optional($order->placed_at)?->toIso8601String(),
                            'created_at_label' => optional($order->placed_at)?->format('d M Y, h:i A'),
                        ];
                    })
                    ->filter(fn (array $record) => (float) $record['amount'] > 0)
                    ->values();
            })
            ->values();
    }

    private function calculateFee(float $subtotal, PlatformFeeSetting $setting): float
    {
        if (! $setting->is_enabled) {
            return 0.0;
        }

        return round(min(($subtotal * (float) $setting->fee_value) / 100, $subtotal), 2);
    }
}
