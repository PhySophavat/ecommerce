<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\CartManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function store(Request $request, CartManager $cart): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $nextQuantity = $cart->quantityFor($product) + $validated['quantity'];

        if ($nextQuantity > $product->inventory) {
            return response()->json([
                'message' => "Only {$product->inventory} units are available for {$product->name}.",
            ], 422);
        }

        return response()->json([
            'message' => "{$product->name} added to cart.",
            'cart' => $cart->add($product, $validated['quantity']),
        ]);
    }

    public function update(Request $request, Product $product, CartManager $cart): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validated['quantity'] > $product->inventory) {
            return response()->json([
                'message' => "Only {$product->inventory} units are available for {$product->name}.",
            ], 422);
        }

        return response()->json([
            'message' => "{$product->name} quantity updated.",
            'cart' => $cart->update($product, $validated['quantity']),
        ]);
    }

    public function destroy(Product $product, CartManager $cart): JsonResponse
    {
        return response()->json([
            'message' => "{$product->name} removed from cart.",
            'cart' => $cart->remove($product),
        ]);
    }
}
