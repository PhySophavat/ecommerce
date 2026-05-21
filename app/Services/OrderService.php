<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly FinanceReportingService $financeReportingService,
        private readonly TelegramService $telegramService,
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
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => (int) $item['quantity'],
            ])
            ->groupBy(fn (array $item): string => $item['product_id'].'|'.($item['variant_id'] ?? 'base'))
            ->map(fn (Collection $rows): array => [
                'product_id' => (int) $rows->first()['product_id'],
                'variant_id' => $rows->first()['variant_id'],
                'quantity' => (int) $rows->sum('quantity'),
            ])
            ->values();

        return DB::transaction(function () use ($customer, $payload, $cartLines): Order {
            $products = Product::query()
                ->with(['images', 'variants', 'merchant.merchant'])
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
            $paymentType = $this->paymentTypeForMethod($payload['payment_method']);

            foreach ($cartLines as $line) {
                /** @var Product $product */
                $product = $products->get($line['product_id']);
                /** @var ProductVariant|null $variant */
                $variant = $line['variant_id']
                    ? $product->variants->firstWhere('id', $line['variant_id'])
                    : null;

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

                if ($line['variant_id'] && !$variant) {
                    throw ValidationException::withMessages([
                        'items' => ["The selected variant for {$product->name} is no longer available."],
                    ]);
                }

                if ((int) $product->inventory < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Only {$product->inventory} unit(s) of {$product->name} are available."],
                    ]);
                }

                if ($variant && (int) $variant->stock < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Only {$variant->stock} unit(s) of {$variant->label} are available."],
                    ]);
                }

                $unitPrice = (float) ($variant?->price ?? $product->price);
                $subtotal += round($unitPrice * $line['quantity'], 2);
            }

            $order = Order::query()->create([
                'customer_id' => $customer->id,
                'number' => $this->nextOrderNumber(),
                'order_code' => $this->nextOrderCode(),
                'status' => 'pending',
                'order_status' => match ($paymentType) {
                    'cash' => 'pending',
                    'manual_transfer' => 'pending_payment',
                    default => 'pending_payment',
                },
                'payment_method' => $payload['payment_method'],
                'payment_type' => $paymentType,
                'payment_status' => match ($paymentType) {
                    'cash' => 'unpaid',
                    'manual_transfer' => 'pending',
                    default => 'pending',
                },
                'payment_reference' => $payload['transaction_reference'] ?? ($payload['payment_reference'] ?? null),
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
                /** @var ProductVariant|null $variant */
                $variant = $line['variant_id']
                    ? $product->variants->firstWhere('id', $line['variant_id'])
                    : null;
                $merchant = $product->merchant?->merchant;
                $primaryImage = $product->images->sortBy('sort_order')->first();
                $unitPrice = (float) ($variant?->price ?? $product->price);
                $lineTotal = round($unitPrice * $line['quantity'], 2);

                $order->items()->create([
                    'product_id' => $product->id,
                    'merchant_id' => $merchant?->id,
                    'product_name' => $product->name,
                    'product_image' => $variant?->image_path ?? $primaryImage?->path,
                    'product_sku' => $variant?->sku ?? $product->sku,
                    'theme' => $product->theme ?: 'cobalt',
                    'unit_price' => $unitPrice,
                    'quantity' => $line['quantity'],
                    'line_total' => $lineTotal,
                ]);

                if ($variant) {
                    $variant->decrement('stock', $line['quantity']);
                }

                $product->decrement('inventory', $line['quantity']);
            }

            $order = $order->fresh([
                'customer',
                'items.merchant.user',
                'items.product',
                'merchantTransactions.merchant',
            ]);

            $this->financeReportingService->syncOrder($order);

            DB::afterCommit(function () use ($order): void {
                $this->telegramService->notifyOrderCreated($order);
            });

            return $order;
        });
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        if (!in_array($status, Order::LEGACY_ORDER_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid order status selected.'],
            ]);
        }

        $order->forceFill(['status' => $status]);

        if ($status === 'paid' && in_array($order->payment_status, ['pending', 'submitted', 'unpaid'], true)) {
            $order->payment_status = 'approved';
            $order->order_status = 'processing';
            $order->paid_at ??= now();
        }

        if (in_array($status, ['processing', 'shipped'], true)) {
            $order->order_status = 'processing';
        }

        if (in_array($status, ['completed', 'delivered'], true)) {
            $order->order_status = 'completed';
        }

        if (in_array($status, ['cancelled', 'failed', 'payment_failed', 'refunded'], true)) {
            $order->order_status = 'cancelled';

            if (!in_array($order->payment_status, ['paid', 'approved'], true)) {
                $order->payment_status = 'rejected';
                $order->paid_at = null;
            }
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

        if ($paymentStatus === 'approved') {
            $order->status = 'processing';
            $order->order_status = 'processing';
            $order->paid_at ??= now();
        }

        if (in_array($paymentStatus, ['unpaid', 'pending', 'failed', 'auto_failed', 'rejected'], true)) {
            $order->paid_at = null;
        }

        if ($paymentStatus === 'submitted') {
            $order->order_status = 'payment_submitted';
        }

        if ($paymentStatus === 'rejected') {
            $order->order_status = 'pending_payment';
        }

        if ($paymentStatus === 'auto_failed') {
            $order->order_status = 'pending_payment';
            $order->status = 'pending';
        }

        $order->save();

        $order = $order->fresh(['customer', 'items.merchant.user', 'items.product']);
        $this->financeReportingService->syncOrder($order);

        return $order;
    }

    public function markPaymentPaid(Order $order, ?CarbonInterface $paidAt = null): Order
    {
        $order->forceFill([
            'payment_status' => 'approved',
            'order_status' => 'processing',
            'status' => 'processing',
            'paid_at' => $paidAt ?? now(),
        ])->save();

        $order = $order->fresh(['customer', 'items.merchant.user', 'items.product']);
        $this->financeReportingService->syncOrder($order);

        return $order;
    }

    public function markPaymentOutcome(Order $order, string $paymentStatus): Order
    {
        if (!in_array($paymentStatus, ['failed', 'auto_failed', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'payment_status' => ['Invalid payment outcome selected.'],
            ]);
        }

        $order->forceFill([
            'payment_status' => $paymentStatus,
            'order_status' => in_array($paymentStatus, ['rejected', 'auto_failed'], true) ? 'pending_payment' : $order->order_status,
            'status' => in_array($paymentStatus, ['rejected', 'auto_failed'], true) ? 'pending' : $order->status,
            'paid_at' => null,
        ])->save();

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

    private function nextOrderCode(): string
    {
        do {
            $code = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Order::query()->where('order_code', $code)->exists());

        return $code;
    }

    public function paymentTypeForMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'cash',
            'aba_qr', 'acleda', 'wing' => 'manual_transfer',
            default => 'gateway',
        };
    }
}
