<?php

namespace App\Support;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Storage;

class OrderData
{
    public static function detail(Order $order): array
    {
        $order->loadMissing([
            'customer',
            'items.merchant.user',
            'items.product',
        ]);

        $merchantGroups = $order->items
            ->groupBy('merchant_id')
            ->map(function ($items, $merchantId): array {
                /** @var OrderItem|null $firstItem */
                $firstItem = $items->first();
                $merchant = $firstItem?->merchant;

                return [
                    'merchant_id' => $merchantId ? (int) $merchantId : null,
                    'merchant_name' => self::merchantName($merchant),
                    'subtotal' => self::decimal($items->sum('line_total')),
                    'items' => $items->map(fn (OrderItem $item): array => self::item($item))->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $order->id,
            'number' => $order->number,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'order_status' => $order->order_status,
            'payment_method' => $order->payment_method,
            'payment_type' => $order->payment_type,
            'payment_method_label' => self::paymentMethodLabel($order->payment_method),
            'payment_status' => $order->payment_status,
            'payment_reference' => $order->payment_reference,
            'payment_notes' => $order->payment_notes,
            'payment_instructions' => self::paymentInstructions($order->payment_method),
            'customer_name' => $order->customer_name,
            'email' => $order->email,
            'phone' => $order->phone,
            'address_line1' => $order->address_line1,
            'address_line2' => $order->address_line2,
            'city' => $order->city,
            'postal_code' => $order->postal_code,
            'notes' => $order->notes,
            'subtotal_amount' => self::decimal($order->subtotal_amount),
            'shipping_amount' => self::decimal($order->shipping_amount),
            'total_amount' => self::decimal($order->total_amount),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ] : null,
            'items' => $order->items->map(fn (OrderItem $item): array => self::item($item))->values()->all(),
            'merchant_groups' => $merchantGroups,
        ];
    }

    public static function listItem(Order $order): array
    {
        $detail = self::detail($order);

        return [
            'id' => $detail['id'],
            'number' => $detail['number'],
            'status' => $detail['status'],
            'order_code' => $detail['order_code'],
            'order_status' => $detail['order_status'],
            'payment_method' => $detail['payment_method'],
            'payment_type' => $detail['payment_type'],
            'payment_method_label' => $detail['payment_method_label'],
            'payment_status' => $detail['payment_status'],
            'payment_reference' => $detail['payment_reference'],
            'customer_name' => $detail['customer_name'],
            'total_amount' => $detail['total_amount'],
            'placed_at' => $detail['placed_at'],
            'paid_at' => $detail['paid_at'],
            'items_count' => count($detail['items']),
            'merchant_groups' => $detail['merchant_groups'],
        ];
    }

    public static function item(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'merchant_id' => $item->merchant_id,
            'merchant_name' => self::merchantName($item->merchant),
            'product_name' => $item->product_name,
            'product_image' => self::imageUrl($item->product_image),
            'price' => self::decimal($item->unit_price),
            'quantity' => (int) $item->quantity,
            'total' => self::decimal($item->line_total),
        ];
    }

    private static function merchantName(?Merchant $merchant): string
    {
        return $merchant?->shop_name
            ?: ($merchant?->user?->name ?? 'Merchant shop');
    }

    private static function imageUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/storage/')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private static function decimal(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private static function paymentMethodLabel(?string $value): string
    {
        return match ($value) {
            'aba_qr' => 'ABA QR',
            'acleda' => 'ACLEDA',
            'wing' => 'Wing',
            'card' => 'Card',
            default => 'Cash',
        };
    }

    private static function paymentInstructions(?string $value): string
    {
        return match ($value) {
            'aba_qr' => 'Transfer the exact amount with ABA QR, then submit the transfer reference or screenshot for admin verification.',
            'acleda' => 'Transfer the exact amount with ACLEDA, then submit the transfer reference or screenshot for admin verification.',
            'wing' => 'Transfer the exact amount with Wing, then submit the transfer reference or screenshot for admin verification.',
            'card' => 'Card payment will confirm automatically only when a real gateway is connected.',
            default => 'Cash orders stay unpaid until delivery or manual collection.',
        };
    }
}
