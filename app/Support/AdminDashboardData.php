<?php

namespace App\Support;

use App\Models\AdminMenu;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AdminDashboardData
{
    public static function productsIndex(string $screen = 'products'): array
    {
        $screen = self::normalizedScreen($screen);
        $catalogProducts = Product::query()
            ->with('category')
            ->orderByDesc('id')
            ->get();
        $products = $screen === 'featured-products'
            ? $catalogProducts->where('is_featured', true)->values()
            : $catalogProducts;

        $orders = Order::query()->get();
        $customerCount = User::query()->count();
        $lowStockCount = $catalogProducts->where('inventory', '<=', 60)->count();
        $meta = self::metaForScreen($screen);

        return [
            'screen' => $screen,
            'meta' => [
                'brand' => 'Spodut',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => [
                    'frontend' => route('frontend.home'),
                    'admin_users' => route('admin.products.index'),
                ],
            ],
            'form' => [
                'categories' => Category::query()
                    ->get(['id', 'name', 'slug'])
                    ->sortBy(fn (Category $category): array => [self::categoryOrder($category->slug), $category->name])
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
                    ->values()
                    ->all(),
                'types' => [
                    ['label' => 'Men', 'value' => 'men'],
                    ['label' => 'Women', 'value' => 'women'],
                ],
                'statuses' => [
                    ['label' => 'Active', 'value' => 'active'],
                    ['label' => 'Scheduled', 'value' => 'scheduled'],
                    ['label' => 'Draft', 'value' => 'draft'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['Red', 'Black', 'White', 'Blue', 'Green'],
                'variant_presets' => self::variantPresets(),
            ],
            'summary' => [
                [
                    'label' => 'Total sales',
                    'value' => self::currency((float) $orders->sum('total_amount')),
                    'detail' => $orders->count().' orders recorded',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Total orders',
                    'value' => (string) $orders->count(),
                    'detail' => $orders->where('status', 'pending')->count().' pending',
                    'tone' => 'slate',
                ],
                [
                    'label' => 'Total customers',
                    'value' => (string) $customerCount,
                    'detail' => 'Seeded demo customers',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Low stock products',
                    'value' => (string) $lowStockCount,
                    'detail' => 'Inventory at or below 60',
                    'tone' => 'amber',
                ],
            ],
            'highlights' => [
                [
                    'label' => 'Categories',
                    'value' => (string) Category::query()->count(),
                ],
                [
                    'label' => 'Active',
                    'value' => (string) $catalogProducts->where('status', 'active')->count(),
                ],
                [
                    'label' => 'Scheduled',
                    'value' => (string) $catalogProducts->where('status', 'scheduled')->count(),
                ],
                [
                    'label' => 'Draft',
                    'value' => (string) $catalogProducts->where('status', 'draft')->count(),
                ],
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen($screen)),
            'products' => [
                'count' => $products->count(),
                'items' => $products
                    ->map(fn (Product $product): array => self::product($product))
                    ->values()
                    ->all(),
                'pagination' => [
                    'page' => 1,
                    'pages' => 1,
                    'from' => $products->isEmpty() ? 0 : 1,
                    'to' => $products->count(),
                    'total' => $products->count(),
                ],
            ],
        ];
    }

    public static function slidesIndex(): array
    {
        $slides = Slide::query()
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
        $meta = self::metaForScreen('sliders');

        return [
            'screen' => 'sliders',
            'meta' => [
                'brand' => 'Spodut',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => [
                    'frontend' => route('frontend.home'),
                    'admin_users' => route('admin.products.index'),
                ],
            ],
            'form' => [
                'categories' => Category::query()
                    ->get(['id', 'name', 'slug'])
                    ->sortBy(fn (Category $category): array => [self::categoryOrder($category->slug), $category->name])
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])
                    ->values()
                    ->all(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('sliders')),
            'slides' => [
                'count' => $slides->count(),
                'active_count' => $slides->where('is_active', true)->count(),
                'next_sort_order' => ((int) $slides->max('sort_order')) + 1,
                'items' => $slides
                    ->map(fn (Slide $slide): array => self::slide($slide))
                    ->values()
                    ->all(),
            ],
        ];
    }

    private static function normalizedScreen(string $screen): string
    {
        return in_array($screen, ['dashboard', 'sliders', 'products', 'add-product', 'featured-products'], true) ? $screen : 'products';
    }

    /**
     * @return array{page_title: string, kicker: string, subheadline: string}
     */
    private static function metaForScreen(string $screen): array
    {
        return match ($screen) {
            'dashboard' => [
                'page_title' => 'Dashboard',
                'kicker' => 'Store overview',
                'subheadline' => 'Track sales, customers, orders, and inventory from a focused admin summary.',
            ],
            'add-product' => [
                'page_title' => '',
                'kicker' => '',
                'subheadline' => '',
            ],
            'sliders' => [
                'page_title' => 'Slides',
                'kicker' => 'Hero sliders',
                'subheadline' => '',
            ],
            'featured-products' => [
                'page_title' => 'Featured Products',
                'kicker' => 'Storefront highlights',
                'subheadline' => 'Manage the products promoted in the storefront featured collection.',
            ],
            default => [
                'page_title' => 'Products',
                'kicker' => 'Catalog control center',
                'subheadline' => '',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    private static function activeSlugsForScreen(string $screen): array
    {
        return match ($screen) {
            'dashboard' => ['dashboard'],
            'sliders' => ['sliders'],
            'add-product' => ['products', 'add-product'],
            'featured-products' => ['content-management', 'featured-products'],
            default => ['products', 'all-products'],
        };
    }

    /**
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function menuTree(array $activeSlugs): array
    {
        if (!Schema::hasTable('admin_menus')) {
            return self::normalizeMenuTree(collect(AdminMenuCatalog::items())
                ->map(fn (array $menu): array => self::menuDefinitionItem($menu, $activeSlugs))
                ->values()
                ->all(), $activeSlugs);
        }

        $menus = AdminMenu::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        if ($menus->isEmpty()) {
            return self::normalizeMenuTree(collect(AdminMenuCatalog::items())
                ->map(fn (array $menu): array => self::menuDefinitionItem($menu, $activeSlugs))
                ->values()
                ->all(), $activeSlugs);
        }

        return self::normalizeMenuTree($menus
            ->map(fn (AdminMenu $menu): array => self::menuItem($menu, $activeSlugs))
            ->values()
            ->all(), $activeSlugs);
    }

    /**
     * @param  array<int, array<string, mixed>>  $menuItems
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeMenuTree(array $menuItems, array $activeSlugs): array
    {
        $sliderItem = null;

        $normalized = collect($menuItems)
            ->map(function (array $item) use (&$sliderItem, $activeSlugs): ?array {
                if (self::isSliderSlug($item['slug'] ?? null)) {
                    $sliderItem ??= self::standaloneSliderMenuItem($item, $activeSlugs);

                    return null;
                }

                $children = collect($item['children'] ?? [])
                    ->filter(function (array $child) use (&$sliderItem, $activeSlugs): bool {
                        if (!self::isSliderSlug($child['slug'] ?? null)) {
                            return true;
                        }

                        $sliderItem ??= self::standaloneSliderMenuItem($child, $activeSlugs);

                        return false;
                    })
                    ->values()
                    ->all();

                $childIsActive = collect($children)->contains(fn (array $child): bool => $child['is_active']);

                return [
                    ...$item,
                    'children' => $children,
                    'is_active' => in_array($item['slug'], $activeSlugs, true) || $childIsActive,
                    'is_expanded' => !empty($children) && ($childIsActive || ($item['is_expanded'] ?? false)),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (!$sliderItem || collect($normalized)->contains(fn (array $item): bool => $item['slug'] === 'sliders')) {
            return $normalized;
        }

        $dashboardIndex = collect($normalized)->search(fn (array $item): bool => $item['slug'] === 'dashboard');
        $insertIndex = $dashboardIndex === false ? 0 : $dashboardIndex + 1;

        array_splice($normalized, $insertIndex, 0, [$sliderItem]);

        return array_values($normalized);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $activeSlugs
     * @return array<string, mixed>
     */
    private static function standaloneSliderMenuItem(array $item, array $activeSlugs): array
    {
        return [
            ...$item,
            'label' => 'Slides',
            'slug' => 'sliders',
            'icon' => 'sliders',
            'path' => $item['path'] ?? '/admin/sliders',
            'is_enabled' => true,
            'is_active' => in_array('sliders', $activeSlugs, true),
            'is_expanded' => false,
            'children' => [],
        ];
    }

    private static function isSliderSlug(?string $slug): bool
    {
        return in_array($slug, ['slider', 'sliders'], true);
    }

    /**
     * @param  array<int, string>  $activeSlugs
     * @return array<string, mixed>
     */
    private static function menuItem(AdminMenu $menu, array $activeSlugs): array
    {
        $children = $menu->children
            ->map(fn (AdminMenu $child): array => self::menuItem($child, $activeSlugs))
            ->values()
            ->all();

        if ($menu->slug === 'dashboard') {
            $children = [];
        }

        $childIsActive = collect($children)->contains(fn (array $child): bool => $child['is_active']);
        $isActive = in_array($menu->slug, $activeSlugs, true) || $childIsActive;

        return [
            'id' => $menu->id,
            'label' => $menu->label,
            'slug' => $menu->slug,
            'icon' => $menu->icon,
            'path' => $menu->path,
            'is_enabled' => $menu->is_enabled,
            'is_active' => $isActive,
                    'is_expanded' => $childIsActive,
            'children' => $children,
        ];
    }

    /**
     * @param  array<string, mixed>  $menu
     * @param  array<int, string>  $activeSlugs
     * @return array<string, mixed>
     */
    private static function menuDefinitionItem(array $menu, array $activeSlugs): array
    {
        $children = collect($menu['children'] ?? [])
            ->map(fn (array $child): array => self::menuDefinitionItem($child, $activeSlugs))
            ->values()
            ->all();

        if ($menu['slug'] === 'dashboard') {
            $children = [];
        }

        $childIsActive = collect($children)->contains(fn (array $child): bool => $child['is_active']);
        $isActive = in_array($menu['slug'], $activeSlugs, true) || $childIsActive;

        return [
            'id' => $menu['slug'],
            'label' => $menu['label'],
            'slug' => $menu['slug'],
            'icon' => $menu['icon'] ?? null,
            'path' => $menu['path'],
            'is_enabled' => $menu['is_enabled'],
            'is_active' => $isActive,
            'is_expanded' => $childIsActive,
            'children' => $children,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function product(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'initials' => self::initials($product->name),
            'category_id' => $product->category_id ? (string) $product->category_id : '',
            'category' => $product->category?->name ?? 'Uncategorized',
            'category_slug' => $product->category?->slug,
            'price' => self::currency((float) $product->price),
            'base_price' => $product->compare_at_price ? self::currency((float) $product->compare_at_price) : null,
            'stock' => $product->inventory,
            'status' => $product->status,
            'sku' => $product->sku,
            'type' => $product->type,
            'tagline' => $product->tagline,
            'is_featured' => (bool) $product->is_featured,
            'theme' => $product->theme,
            'updated_at' => $product->updated_at?->format('M d, Y'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function slide(Slide $slide): array
    {
        return [
            'id' => $slide->id,
            'category_id' => $slide->category_id ? (string) $slide->category_id : '',
            'category' => $slide->category?->name ?? 'All categories',
            'category_slug' => $slide->category?->slug,
            'eyebrow' => $slide->eyebrow ?? '',
            'title' => $slide->title,
            'highlight' => $slide->highlight ?? '',
            'description' => $slide->description ?? '',
            'button_text' => $slide->button_text ?? '',
            'button_url' => $slide->button_url ?? '',
            'badge_text' => $slide->badge_text ?? '',
            'image_url' => $slide->resolvedImageUrl(),
            'image_name' => $slide->resolvedImageName(),
            'is_active' => (bool) $slide->is_active,
            'status' => $slide->is_active ? 'active' : 'draft',
            'sort_order' => (string) $slide->sort_order,
            'updated_at' => $slide->updated_at?->format('M d, Y'),
        ];
    }

    private static function currency(float $value): string
    {
        return '$'.number_format($value, 2);
    }

    private static function categoryOrder(string $slug): int
    {
        return [
            'beauty' => 1,
            'fashion' => 2,
            'sport' => 3,
            'electronic' => 4,
            'home' => 5,
        ][$slug] ?? 99;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function variantPresets(): array
    {
        return [
            'beauty' => [
                [
                    'name' => 'Size',
                    'suggested_options' => ['30ml', '50ml', '100ml'],
                ],
                [
                    'name' => 'Color',
                    'suggested_options' => ['Red', 'Pink', 'Nude', 'Orange'],
                ],
                [
                    'name' => 'Skin Type',
                    'suggested_options' => ['Oily', 'Dry', 'Sensitive'],
                ],
                [
                    'name' => 'Scent',
                    'suggested_options' => ['Rose', 'Lemon', 'Unscented'],
                ],
            ],
            'fashion' => [
                [
                    'name' => 'Size',
                    'suggested_options' => ['S', 'M', 'L', 'XL'],
                ],
                [
                    'name' => 'Color',
                    'suggested_options' => ['Black', 'White', 'Blue'],
                ],
                [
                    'name' => 'Material',
                    'suggested_options' => ['Cotton', 'Jeans'],
                ],
                [
                    'name' => 'Style',
                    'suggested_options' => ['Slim Fit', 'Oversize'],
                ],
                [
                    'name' => 'Gender',
                    'suggested_options' => ['Men', 'Women', 'Unisex'],
                ],
            ],
            'sport' => [
                [
                    'name' => 'Size',
                    'suggested_options' => ['Small', 'Medium', 'Large'],
                ],
                [
                    'name' => 'Weight',
                    'suggested_options' => ['5kg', '10kg', '15kg'],
                ],
                [
                    'name' => 'Color',
                    'suggested_options' => ['Blue', 'Pink', 'Black'],
                ],
                [
                    'name' => 'Material',
                    'suggested_options' => ['Rubber', 'Foam', 'Steel'],
                ],
                [
                    'name' => 'Thickness',
                    'suggested_options' => ['5mm', '10mm'],
                ],
                [
                    'name' => 'Resistance Level',
                    'suggested_options' => ['Light', 'Medium', 'Heavy'],
                ],
                [
                    'name' => 'Usage Type',
                    'suggested_options' => ['Indoor', 'Outdoor'],
                ],
            ],
            'electronic' => [
                [
                    'name' => 'Model',
                    'suggested_options' => ['Base', 'Pro', 'Ultra'],
                ],
                [
                    'name' => 'RAM',
                    'suggested_options' => ['8GB', '16GB'],
                ],
                [
                    'name' => 'Storage',
                    'suggested_options' => ['256GB', '512GB'],
                ],
                [
                    'name' => 'Color',
                    'suggested_options' => ['Silver', 'Black'],
                ],
                [
                    'name' => 'Plug Type',
                    'suggested_options' => ['EU', 'US', 'UK'],
                ],
                [
                    'name' => 'Voltage',
                    'suggested_options' => ['110V', '220V'],
                ],
                [
                    'name' => 'Power',
                    'suggested_options' => ['20W', '30W'],
                ],
                [
                    'name' => 'Region Version',
                    'suggested_options' => ['Global', 'US', 'EU', 'Asia'],
                ],
            ],
            'home' => [
                [
                    'name' => 'Size',
                    'suggested_options' => ['Small', 'Medium', 'Large', '20L', '40L', '60L'],
                ],
                [
                    'name' => 'Color',
                    'suggested_options' => ['Black', 'Brown', 'White'],
                ],
                [
                    'name' => 'Material',
                    'suggested_options' => ['Wood', 'Steel', 'Plastic', 'Metal'],
                ],
                [
                    'name' => 'Style',
                    'suggested_options' => ['Modern', 'Classic'],
                ],
                [
                    'name' => 'Dimensions',
                    'suggested_options' => ['40x40cm', '60x60cm'],
                ],
                [
                    'name' => 'Pattern / Design',
                    'suggested_options' => ['Plain', 'Striped', 'Floral'],
                ],
            ],
        ];
    }

    private static function initials(string $name): string
    {
        /** @var Collection<int, string> $segments */
        $segments = Str::of($name)
            ->replace('-', ' ')
            ->explode(' ')
            ->filter()
            ->take(2)
            ->values();

        return $segments
            ->map(fn (string $segment): string => Str::upper(Str::substr($segment, 0, 1)))
            ->implode('');
    }
}
