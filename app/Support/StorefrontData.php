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
        $imageUrls = $product->images
            ->sortBy('sort_order')
            ->pluck('path')
            ->map(fn (mixed $path): ?string => self::imageUrl($path))
            ->filter()
            ->values();
        $fallbackImage = self::productFallbackImage($product);
        $primaryImageUrl = $imageUrls->first() ?? $fallbackImage;
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
            'image_url' => $primaryImageUrl,
            'image_urls' => $imageUrls->isNotEmpty() ? $imageUrls->all() : [$fallbackImage],
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

    private static function imageUrl(?string $value): ?string
    {
        $path = trim((string) $value);

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return '/'.ltrim($path, '/');
        }

        return '/storage/'.ltrim($path, '/');
    }

    private static function productFallbackImage(Product $product): string
    {
        $palette = match ($product->category?->slug) {
            'beauty' => ['#FCE7F3', '#F9A8D4', '#9D174D'],
            'fashion' => ['#EDE9FE', '#C4B5FD', '#5B21B6'],
            'sport' => ['#DCFCE7', '#86EFAC', '#166534'],
            'electronic' => ['#DBEAFE', '#93C5FD', '#1D4ED8'],
            'home' => ['#FFEDD5', '#FDBA74', '#C2410C'],
            default => ['#F3F4F6', '#D1D5DB', '#374151'],
        };

        $category = htmlspecialchars(Str::upper($product->category?->name ?? 'Product'), ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars(Str::upper(Str::limit(strip_tags((string) $product->name), 28, '')), ENT_QUOTES, 'UTF-8');
        $ariaLabel = htmlspecialchars((string) $product->name, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900" role="img" aria-label="{$ariaLabel}">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$palette[0]}"/>
      <stop offset="55%" stop-color="#FFFFFF"/>
      <stop offset="100%" stop-color="{$palette[1]}"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="900" fill="url(#bg)"/>
  <circle cx="180" cy="160" r="120" fill="{$palette[1]}" fill-opacity="0.24"/>
  <circle cx="1030" cy="210" r="170" fill="{$palette[1]}" fill-opacity="0.18"/>
  <circle cx="980" cy="720" r="210" fill="{$palette[0]}" fill-opacity="0.7"/>
  <text x="96" y="128" fill="{$palette[2]}" font-size="42" font-family="Arial, sans-serif" font-weight="700" letter-spacing="12">{$category}</text>
  <text x="96" y="675" fill="#111827" font-size="74" font-family="Arial, sans-serif" font-weight="700">{$name}</text>
  <text x="96" y="750" fill="#6B7280" font-size="30" font-family="Arial, sans-serif">STORE DEMO IMAGE</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
