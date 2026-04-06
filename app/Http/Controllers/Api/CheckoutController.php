<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Support\CartManager;
use App\Support\StorefrontData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __invoke(Request $request, CartManager $cart): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'address_line1' => ['required', 'string', 'max:120'],
            'address_line2' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $products = Product::query()
            ->whereIn('id', $lines->map(fn (array $line): int => $line['product']->id))
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $product = $products->get($line['product']->id);

            if (! $product || $line['quantity'] > $product->inventory) {
                return response()->json([
                    'message' => "Inventory changed for {$line['product']->name}. Refresh the cart and try again.",
                ], 422);
            }
        }

        $summary = $cart->summary();

        $order = DB::transaction(function () use ($validated, $lines, $products, $summary, $cart): Order {
            $order = Order::query()->create([
                'number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address_line1' => $validated['address_line1'],
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'notes' => $validated['notes'] ?? null,
                'subtotal_amount' => $summary['subtotal'],
                'shipping_amount' => $summary['shipping'],
                'total_amount' => $summary['total'],
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $product = $products->get($line['product']->id);
                $product->decrement('inventory', $line['quantity']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'theme' => $product->theme,
                    'unit_price' => $product->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $cart->clear();

            return $order;
        });

        $order->load('items');

        return response()->json([
            'message' => "Order {$order->number} has been placed.",
            'order' => StorefrontData::order($order),
            'cart' => $cart->summary(),
        ], 201);
    }

    protected function generateOrderNumber(): string
    {
        do {
            $number = 'NSG-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
