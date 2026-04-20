<?php

namespace App\Support;

use App\Models\AdminMenu;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AdminDashboardData
{
    public static function productsIndex(string $screen = 'products'): array
    {
        $screen = self::normalizedScreen($screen);
        $products = Product::query()
            ->with('category')
            ->orderByDesc('id')
            ->get();

        $orders = Order::query()->get();
        $customerCount = User::query()->count();
        $lowStockCount = $products->where('inventory', '<=', 60)->count();
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
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])
                    ->values()
                    ->all(),
                'types' => [
                    ['label' => 'Men', 'value' => 'men'],
                    ['label' => 'Women', 'value' => 'women'],
                ],
                'statuses' => [
                    ['label' => 'Active', 'value' => 'active'],
                    ['label' => 'Draft', 'value' => 'draft'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['Red', 'Black', 'White', 'Blue', 'Green'],
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
                    'value' => (string) $products->where('status', 'active')->count(),
                ],
                [
                    'label' => 'Scheduled',
                    'value' => (string) $products->where('status', 'scheduled')->count(),
                ],
                [
                    'label' => 'Draft',
                    'value' => (string) $products->where('status', 'draft')->count(),
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

    private static function normalizedScreen(string $screen): string
    {
        return in_array($screen, ['dashboard', 'products', 'add-product'], true) ? $screen : 'products';
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
                'page_title' => 'Add Product',
                'kicker' => 'Catalog creation',
                'subheadline' => 'Create a new catalog item, upload images, and configure stock, pricing, and variants.',
            ],
            default => [
                'page_title' => 'Products',
                'kicker' => 'Catalog control center',
                'subheadline' => 'Review the product catalog, check stock, and move into product creation when needed.',
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
            'add-product' => ['products', 'add-product'],
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
            return collect(AdminMenuCatalog::items())
                ->map(fn (array $menu): array => self::menuDefinitionItem($menu, $activeSlugs))
                ->values()
                ->all();
        }

        $menus = AdminMenu::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        if ($menus->isEmpty()) {
            return collect(AdminMenuCatalog::items())
                ->map(fn (array $menu): array => self::menuDefinitionItem($menu, $activeSlugs))
                ->values()
                ->all();
        }

        return $menus
            ->map(fn (AdminMenu $menu): array => self::menuItem($menu, $activeSlugs))
            ->values()
            ->all();
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
            'is_expanded' => $childIsActive || in_array($menu->slug, ['products'], true),
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
            'is_expanded' => $childIsActive || in_array($menu['slug'], ['products'], true),
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
            'category' => $product->category?->name ?? 'Uncategorized',
            'price' => self::currency((float) $product->price),
            'base_price' => $product->compare_at_price ? self::currency((float) $product->compare_at_price) : null,
            'stock' => $product->inventory,
            'status' => $product->status,
            'sku' => $product->sku,
            'type' => $product->type,
            'tagline' => $product->tagline,
            'theme' => $product->theme,
            'updated_at' => $product->updated_at?->format('M d, Y'),
        ];
    }

    private static function currency(float $value): string
    {
        return '$'.number_format($value, 2);
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
