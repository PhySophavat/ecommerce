<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartManager
{
    public const SESSION_KEY = 'northstar.cart';
    public const FREE_SHIPPING_THRESHOLD = 250;
    public const SHIPPING_RATE = 18;

    public function quantityFor(Product $product): int
    {
        return (int) ($this->storedItems()[$product->id] ?? 0);
    }

    public function add(Product $product, int $quantity): array
    {
        $items = $this->storedItems();
        $items[$product->id] = ($items[$product->id] ?? 0) + $quantity;

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function update(Product $product, int $quantity): array
    {
        $items = $this->storedItems();

        if ($quantity <= 0) {
            unset($items[$product->id]);
        } else {
            $items[$product->id] = $quantity;
        }

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function remove(Product $product): array
    {
        $items = $this->storedItems();
        unset($items[$product->id]);

        session([self::SESSION_KEY => $items]);

        return $this->summary();
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function lines(): Collection
    {
        $items = collect($this->storedItems())
            ->map(fn (int $quantity, int $productId): array => [
                'product_id' => $productId,
                'quantity' => $quantity,
            ])
            ->filter(fn (array $item): bool => $item['quantity'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with('category')
            ->whereIn('id', $items->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return $items
            ->map(function (array $item) use ($products): ?array {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    return null;
                }

                return [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'line_total' => round(((float) $product->price) * $item['quantity'], 2),
                ];
            })
            ->filter()
            ->values();
    }

    public function summary(): array
    {
        $lines = $this->lines();
        $subtotal = round($lines->sum('line_total'), 2);
        $shipping = $subtotal === 0.0 || $subtotal >= self::FREE_SHIPPING_THRESHOLD
            ? 0.0
            : (float) self::SHIPPING_RATE;

        return [
            'items' => $lines
                ->map(fn (array $line): array => [
                    'product' => StorefrontData::product($line['product']),
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ])
                ->values()
                ->all(),
            'count' => (int) $lines->sum('quantity'),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => round($subtotal + $shipping, 2),
            'shipping_threshold' => self::FREE_SHIPPING_THRESHOLD,
            'free_shipping_gap' => max(self::FREE_SHIPPING_THRESHOLD - $subtotal, 0),
            'is_empty' => $lines->isEmpty(),
        ];
    }

    protected function storedItems(): array
    {
        return collect(session(self::SESSION_KEY, []))
            ->mapWithKeys(fn (mixed $quantity, mixed $productId): array => [
                (int) $productId => max((int) $quantity, 0),
            ])
            ->all();
    }
}
