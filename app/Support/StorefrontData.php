<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class StorefrontData
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name ?: Str::before($user->email, '@'),
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'joined_at' => $user->created_at?->toDateString(),
        ];
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description ?? '',
            'accent' => $category->accent ?: '#f97316',
            'products_count' => (int) ($category->products_count ?? 0),
        ];
    }

    public static function product(Product $product): array
    {
        $primaryImage = $product->images->sortBy('sort_order')->first();
        $merchantUser = $product->merchant;
        $merchantProfile = $merchantUser?->merchant;
        $isStorefrontVisible = in_array($product->status, ['approved', 'active'], true);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'category' => $product->category?->name ?? 'Uncategorized',
            'category_slug' => $product->category?->slug,
            'tagline' => $product->tagline ?: Str::limit(strip_tags((string) $product->description), 90),
            'description' => Str::limit(strip_tags((string) $product->description), 180),
            'price' => self::currency((float) $product->price),
            'price_value' => number_format((float) $product->price, 2, '.', ''),
            'compare_at_price' => $product->compare_at_price ? self::currency((float) $product->compare_at_price) : null,
            'compare_at_price_value' => $product->compare_at_price ? number_format((float) $product->compare_at_price, 2, '.', '') : null,
            'status' => $product->status,
            'theme' => $product->theme,
            'is_featured' => (bool) $product->is_featured,
            'is_admin_owned' => $product->merchant_id === null,
            'rating' => $product->rating ? number_format((float) $product->rating, 2) : null,
            'reviews_count' => (int) $product->reviews_count,
            'inventory' => (int) $product->inventory,
            'is_orderable' => $isStorefrontVisible && (int) $product->inventory > 0,
            'merchant_id' => $merchantProfile?->id,
            'merchant_name' => $merchantProfile?->shop_name ?: ($merchantUser?->name ?: 'Admin Store'),
            'merchant_owner' => $merchantUser?->name ?: Str::before((string) $merchantUser?->email, '@'),
            'image_url' => $primaryImage?->path ? Storage::disk('public')->url($primaryImage->path) : null,
        ];
    }

    public static function slide(Slide $slide): array
    {
        return [
            'id' => $slide->id,
            'category' => $slide->category?->name ?? 'All categories',
            'category_slug' => $slide->category?->slug,
            'eyebrow' => $slide->eyebrow ?? '',
            'title' => $slide->title,
            'highlight' => $slide->highlight ?? '',
            'description' => $slide->description ?? '',
            'button_text' => $slide->button_text ?? '',
            'button_url' => $slide->button_url ?: '/frontend',
            'badge_text' => $slide->badge_text ?? '',
            'image_url' => $slide->resolvedImageUrl(),
            'accent' => $slide->category?->accent ?: '#f97316',
            'sort_order' => (int) $slide->sort_order,
        ];
    }

    public static function categoryOrder(string $slug): int
    {
        return [
            'beauty' => 1,
            'fashion' => 2,
            'sport' => 3,
            'electronic' => 4,
            'home' => 5,
        ][$slug] ?? 99;
    }

    private static function currency(float $value): string
    {
        return '$'.number_format($value, 2);
    }

}
