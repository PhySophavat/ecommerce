<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\CartManager;
use App\Support\StorefrontData;
use Illuminate\Http\JsonResponse;

class StorefrontController extends Controller
{
    public function __invoke(CartManager $cart): JsonResponse
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('category')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        return response()->json([
            'meta' => [
                'brand' => 'Northstar Goods',
                'headline' => 'A tactile ecommerce starter for curated everyday gear.',
                'subheadline' => 'Laravel handles the catalog, session cart, and orders. Vue 3 drives the storefront interactions and checkout flow.',
                'stack' => ['Laravel 12 backend', 'Vue 3 storefront', 'SQLite demo seed'],
                'stats' => [
                    ['value' => (string) $products->count(), 'label' => 'seeded products'],
                    ['value' => '48h', 'label' => 'dispatch window'],
                    ['value' => '3', 'label' => 'curated categories'],
                ],
            ],
            'categories' => $categories
                ->map(fn (Category $category): array => StorefrontData::category($category))
                ->values()
                ->all(),
            'featured' => $products
                ->filter(fn (Product $product): bool => $product->is_featured)
                ->take(3)
                ->map(fn (Product $product): array => StorefrontData::product($product))
                ->values()
                ->all(),
            'products' => $products
                ->map(fn (Product $product): array => StorefrontData::product($product))
                ->values()
                ->all(),
            'cart' => $cart->summary(),
        ]);
    }
}
