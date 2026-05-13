<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly FinanceReportingService $financeReportingService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function checkout(User $customer, array $payload): Order
    {
        $cartLines = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ])
            ->groupBy('product_id')
            ->map(fn (Collection $rows, $productId): array => [
                'product_id' => (int) $productId,
                'quantity' => (int) $rows->sum('quantity'),
            ])
            ->values();

        return DB::transaction(function () use ($customer, $payload, $cartLines): Order {
            $products = Product::query()
                ->with(['images', 'merchant.merchant'])
                ->whereIn('id', $cartLines->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $cartLines->count()) {
                throw ValidationException::withMessages([
                    'items' => ['One or more products are no longer available.'],
                ]);
            }

            $subtotal = 0.0;

            foreach ($cartLines as $line) {
                /** @var Product $product */
                $product = $products->get($line['product_id']);

                if (!$product->isApproved()) {
                    throw ValidationException::withMessages([
                        'items' => ["{$product->name} is not approved for ordering."],
                    ]);
                }

                if ($product->merchant_id !== null && !$product->merchant?->merchant?->isApproved()) {
                    throw ValidationException::withMessages([
                        'items' => ["{$product->name} is not available right now."],
                    ]);
                }

                if ((int) $product->inventory < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Only {$product->inventory} unit(s) of {$product->name} are available."],
                    ]);
                }

                $subtotal += round((float) $product->price * $line['quantity'], 2);
            }

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'number' => $this->nextOrderNumber(),
                'status' => 'pending',
                'payment_method' => $payload['payment_method'],
                'payment_status' => 'unpaid',
                'payment_reference' => $payload['payment_reference'] ?? null,
                'customer_name' => $payload['customer_name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'address_line1' => $payload['address_line1'],
                'address_line2' => $payload['address_line2'] ?? null,
                'city' => $payload['city'],
                'postal_code' => $payload['postal_code'],
                'notes' => $payload['notes'] ?? null,
                'payment_notes' => $payload['payment_notes'] ?? null,
                'subtotal_amount' => round($subtotal, 2),
                'shipping_amount' => 0,
                'total_amount' => round($subtotal, 2),
                'placed_at' => now(),
            ]);

            foreach ($cartLines as $line) {
                /** @var Product $product */
                $product = $products->get($line['product_id']);
                $merchant = $product->merchant?->merchant;
                $primaryImage = $product->images->sortBy('sort_order')->first();
                $lineTotal = round((float) $product->price * $line['quantity'], 2);

                $order->items()->create([
                    'product_id' => $product->id,
                    'merchant_id' => $merchant?->id,
                    'product_name' => $product->name,
                    'product_image' => $primaryImage?->path,
                    'product_sku' => $product->sku,
                    'theme' => $product->theme ?: 'cobalt',
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ]);

                $product->decrement('inventory', $line['quantity']);
            }

            $order = $order->fresh([
                'customer',
                'items.merchant.user',
                'items.product',
                'merchantTransactions.merchant',
            ]);

            $this->financeReportingService->syncOrder($order);

            return $order;
        });
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        if (!in_array($status, Order::ORDER_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid order status selected.'],
            ]);
        }

        $order->forceFill(['status' => $status]);

        if ($status === 'paid' && $order->payment_status === 'unpaid') {
            $order->payment_status = 'paid';
            $order->paid_at ??= now();
        }

        if ($status === 'refunded' && $order->payment_status === 'paid') {
            $order->payment_status = 'refunded';
        }

        $order->save();

        $order = $order->fresh(['customer', 'items.merchant.user', 'items.product']);
        $this->financeReportingService->syncOrder($order);

        return $order;
    }

    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        if (!in_array($paymentStatus, Order::PAYMENT_STATUSES, true)) {
            throw ValidationException::withMessages([
                'payment_status' => ['Invalid payment status selected.'],
            ]);
        }

        $order->payment_status = $paymentStatus;

        if ($paymentStatus === 'paid' && $order->status === 'pending') {
            $order->status = 'paid';
            $order->paid_at ??= now();
        }

        if (in_array($paymentStatus, ['unpaid', 'failed'], true)) {
            $order->paid_at = null;
        }

        if ($paymentStatus === 'refunded') {
            $order->status = 'refunded';
        }

        $order->save();

        $order = $order->fresh(['customer', 'items.merchant.user', 'items.product']);
        $this->financeReportingService->syncOrder($order);

        return $order;
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
