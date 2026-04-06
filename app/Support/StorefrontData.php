<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

class StorefrontData
{
    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'accent' => $category->accent,
            'product_count' => (int) ($category->products_count ?? 0),
        ];
    }

    public static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'tagline' => $product->tagline,
            'description' => $product->description,
            'price' => (float) $product->price,
            'compare_at_price' => $product->compare_at_price !== null ? (float) $product->compare_at_price : null,
            'inventory' => (int) $product->inventory,
            'is_featured' => (bool) $product->is_featured,
            'theme' => $product->theme,
            'rating' => (float) $product->rating,
            'reviews_count' => (int) $product->reviews_count,
            'category' => $product->relationLoaded('category') && $product->category
                ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                    'accent' => $product->category->accent,
                ]
                : null,
        ];
    }

    public static function order(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'customer_name' => $order->customer_name,
            'email' => $order->email,
            'phone' => $order->phone,
            'address_line1' => $order->address_line1,
            'address_line2' => $order->address_line2,
            'city' => $order->city,
            'postal_code' => $order->postal_code,
            'notes' => $order->notes,
            'subtotal' => (float) $order->subtotal_amount,
            'shipping' => (float) $order->shipping_amount,
            'total' => (float) $order->total_amount,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'items' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'theme' => $item->theme,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])
                ->values()
                ->all(),
        ];
    }
}
