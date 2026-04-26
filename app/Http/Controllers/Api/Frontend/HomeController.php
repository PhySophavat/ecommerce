<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Support\StorefrontData;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $products = Product::query()
            ->with('category')
            ->whereIn('status', ['active', 'scheduled'])
            ->orderByDesc('is_featured')
            ->orderByDesc('updated_at')
            ->get();

        $categories = Category::query()
            ->withCount([
                'products as products_count' => fn ($query) => $query->whereIn('status', ['active', 'scheduled']),
            ])
            ->get()
            ->sortBy(fn (Category $category): array => [StorefrontData::categoryOrder($category->slug), $category->name])
            ->values();

        $featuredProducts = $products
            ->where('is_featured', true)
            ->take(3)
            ->values();
        $featuredCount = $products->where('is_featured', true)->count();
        $slides = Slide::query()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'meta' => [
                'brand' => 'Northstar Goodsdfgh',
                'eyebrow' => 'Category-led storefront',
                'headline' => 'Shop the catalog by category.',
                'subheadline' => 'The website header now pulls its navigation directly from product categories managed in the admin dashboard.',
                'stats' => [
                    ['value' => (string) $categories->count(), 'label' => 'catalog categories'],
                    ['value' => (string) $products->count(), 'label' => 'storefront products'],
                    ['value' => (string) $featuredCount, 'label' => 'featured picks'],
                ],
            ],
            'links' => [
                'frontend' => route('frontend.home'),
                'admin_sliders' => route('admin.sliders.index'),
                'admin_products' => route('admin.products.index'),
            ],
            'categories' => $categories
                ->map(fn (Category $category): array => StorefrontData::category($category))
                ->values()
                ->all(),
            'slides' => $slides
                ->map(fn (Slide $slide): array => StorefrontData::slide($slide))
                ->values()
                ->all(),
            'products' => [
                'count' => $products->count(),
                'featured' => $featuredProducts
                    ->map(fn (Product $product): array => StorefrontData::product($product))
                    ->values()
                    ->all(),
                'items' => $products
                    ->map(fn (Product $product): array => StorefrontData::product($product))
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
