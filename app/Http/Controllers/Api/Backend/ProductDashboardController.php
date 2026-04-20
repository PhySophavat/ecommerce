<?php

namespace App\Http\Controllers\Api\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Support\AdminDashboardData;

class ProductDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(AdminDashboardData::productsIndex((string) $request->query('screen', 'products')));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(['men', 'women'])],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'status' => ['required', Rule::in(['active', 'draft'])],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:4096'],
            'variants' => ['nullable', 'array'],
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color' => ['required', 'string', 'max:40'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ]);

        $product = DB::transaction(function () use ($request, $validated): Product {
            $originalPrice = (float) $validated['price'];
            $discountPrice = $validated['discount_price'] !== null ? (float) $validated['discount_price'] : null;

            $product = Product::query()->create([
                'category_id' => $validated['category_id'],
                'type' => $validated['type'],
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'sku' => $validated['sku'],
                'tagline' => Str::limit(strip_tags($validated['description']), 90, ''),
                'description' => $validated['description'],
                'price' => $discountPrice ?? $originalPrice,
                'compare_at_price' => $discountPrice ? $originalPrice : null,
                'inventory' => $validated['stock_quantity'],
                'status' => $validated['status'],
                'is_featured' => false,
                'theme' => 'cobalt',
                'rating' => 4.80,
                'reviews_count' => 0,
            ]);

            collect($validated['variants'] ?? [])
                ->each(function (array $variant) use ($product): void {
                    $product->variants()->create([
                        'size' => $variant['size'],
                        'color' => $variant['color'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                    ]);
                });

            collect($request->file('images', []))
                ->each(function ($file, int $index) use ($product): void {
                    $path = $file->store("products/{$product->id}", 'public');

                    $product->images()->create([
                        'path' => $path,
                        'sort_order' => $index + 1,
                    ]);
                });

            return $product;
        });

        return response()->json([
            'message' => "{$product->name} was created successfully.",
            'product_id' => $product->id,
        ], 201);
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
