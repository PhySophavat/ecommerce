<?php

namespace App\Support;

use App\Models\AdminMenu;
use App\Models\Category;
use App\Models\Order;
use App\Models\PlatformFeeSetting;
use App\Models\Product;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class AdminDashboardData
{
    public static function accountsIndex(string $screen = 'users'): array
    {
        $screen = in_array($screen, ['users', 'merchants'], true) ? $screen : 'users';
        $role = $screen === 'merchants' ? 'merchant' : 'admin';
        $accounts = User::query()
            ->where('role', $role)
            ->withCount([
                'products',
                'products as pending_products_count' => fn ($query) => $query->where('status', 'pending'),
                'products as approved_products_count' => fn ($query) => $query->where('status', 'approved'),
                'approvedProducts as approved_products_total' => fn ($query) => $query->where('status', 'approved'),
            ])
            ->orderByDesc('created_at')
            ->get();
        $meta = self::metaForScreen($screen);
        $merchantCount = User::query()->where('role', 'merchant')->count();
        $adminCount = User::query()->where('role', 'admin')->count();
        $accountsCount = $accounts->count();
        $merchantProductsCount = (int) $accounts->sum('products_count');
        $pendingProductsCount = (int) $accounts->sum('pending_products_count');
        $approvedProductsCount = $screen === 'merchants'
            ? (int) $accounts->sum('approved_products_count')
            : (int) $accounts->sum('approved_products_total');

        return [
            'screen' => $screen,
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen($screen)),
            'form' => [
                'role' => $role,
                'role_label' => $role === 'merchant' ? 'Merchant' : 'Admin user',
                'submit_label' => $role === 'merchant' ? 'Create merchant' : 'Create admin user',
            ],
            'accounts' => [
                'role' => $role,
                'count' => $accountsCount,
                'summary' => $screen === 'merchants'
                    ? [
                        [
                            'label' => 'Total merchants',
                            'value' => (string) $accountsCount,
                            'detail' => 'Seller accounts with dashboard access',
                        ],
                        [
                            'label' => 'Products listed',
                            'value' => (string) $merchantProductsCount,
                            'detail' => 'Catalog items created by merchants',
                        ],
                        [
                            'label' => 'Pending review',
                            'value' => (string) $pendingProductsCount,
                            'detail' => 'Merchant products waiting for approval',
                        ],
                        [
                            'label' => 'Approved products',
                            'value' => (string) $approvedProductsCount,
                            'detail' => 'Merchant products currently approved',
                        ],
                    ]
                    : [
                        [
                            'label' => 'Admin users',
                            'value' => (string) $accountsCount,
                            'detail' => 'Administrator accounts with backend access',
                        ],
                        [
                            'label' => 'Approved products',
                            'value' => (string) $approvedProductsCount,
                            'detail' => 'Products approved by admin users',
                        ],
                        [
                            'label' => 'Merchant accounts',
                            'value' => (string) $merchantCount,
                            'detail' => 'Total seller accounts on the platform',
                        ],
                        [
                            'label' => 'Admin coverage',
                            'value' => (string) $adminCount,
                            'detail' => 'Current approved admin seats',
                        ],
                    ],
                'items' => $accounts
                    ->map(fn (User $user): array => self::account($user, $screen))
                    ->values()
                    ->all(),
            ],
        ];
    }

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
                'links' => self::sharedLinks(),
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
                'links' => self::sharedLinks(),
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

    public static function platformFeeSettings(): array
    {
        $setting = PlatformFeeSetting::current();
        $meta = self::metaForScreen('platform-fee-settings');

        return [
            'screen' => 'platform-fee-settings',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('platform-fee-settings')),
            'setting' => [
                'is_enabled' => (bool) $setting->is_enabled,
                'fee_type' => $setting->fee_type,
                'fee_value' => number_format((float) $setting->fee_value, 2, '.', ''),
                'apply_stage' => $setting->apply_stage,
                'deduct_from' => $setting->deduct_from,
            ],
        ];
    }

    public static function withdrawalsPage(): array
    {
        $meta = self::metaForScreen('withdrawals');

        return [
            'screen' => 'withdrawals',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('withdrawals')),
        ];
    }

    public static function depositsPage(): array
    {
        $meta = self::metaForScreen('deposits');

        return [
            'screen' => 'deposits',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('deposits')),
        ];
    }

    public static function walletPage(): array
    {
        $meta = self::metaForScreen('wallet');

        return [
            'screen' => 'wallet',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('wallet')),
        ];
    }

    private static function normalizedScreen(string $screen): string
    {
        return in_array($screen, ['dashboard', 'sliders', 'products', 'add-product', 'featured-products', 'users', 'merchants', 'platform-fee-settings', 'withdrawals', 'deposits', 'wallet'], true) ? $screen : 'products';
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
                'kicker' => 'Catalog expansion',
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
            'users' => [
                'page_title' => 'Admin Users',
                'kicker' => 'Admin access',
                'subheadline' => 'Manage administrator accounts, review who can access the backend, and keep access tidy.',
            ],
            'platform-fee-settings' => [
                'page_title' => 'Platform Fee Settings',
                'kicker' => 'Commission control',
                'subheadline' => 'Configure how the platform deducts commission from merchant balances after each qualifying order stage.',
            ],
            'wallet' => [
                'page_title' => 'Wallet',
                'kicker' => 'Finance overview',
                'subheadline' => 'Review merchant deposit and withdrawal activity from a single admin wallet overview.',
            ],
            'withdrawals' => [
                'page_title' => 'Withdrawals',
                'kicker' => 'Merchant payouts',
                'subheadline' => 'Review withdrawal requests, approve valid payouts, and mark completed transfers as paid.',
            ],
            'deposits' => [
                'page_title' => 'Deposits',
                'kicker' => 'Wallet top-ups',
                'subheadline' => 'Review merchant deposit proofs and approve manual KHQR top-ups into merchant wallets.',
            ],
            'merchants' => [
                'page_title' => 'Merchants',
                'kicker' => 'Seller management',
                'subheadline' => 'Create merchant accounts, review their product activity, and manage seller access from one place.',
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
            'users' => ['users-admin-management', 'admin-users'],
            'merchants' => ['users-admin-management', 'merchants'],
            'wallet' => ['wallet'],
            'platform-fee-settings' => ['settings', 'platform-fee-settings'],
            'deposits' => ['payments', 'deposits'],
            'withdrawals' => ['payments', 'withdrawals'],
            default => ['products', 'all-products'],
        };
    }

    /**
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function menuTree(array $activeSlugs): array
    {
        $catalogMenu = self::defaultMenuTree($activeSlugs);

        if (!Schema::hasTable('admin_menus')) {
            return self::normalizeMenuTree($catalogMenu, $activeSlugs);
        }

        $menus = AdminMenu::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        if ($menus->isEmpty()) {
            return self::normalizeMenuTree($catalogMenu, $activeSlugs);
        }

        $databaseMenu = $menus
            ->map(fn (AdminMenu $menu): array => self::menuItem($menu, $activeSlugs))
            ->values()
            ->all();

        return self::normalizeMenuTree(
            self::mergeMenuDefinitions($databaseMenu, $catalogMenu, $activeSlugs),
            $activeSlugs,
        );
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
                    'is_expanded' => !empty($children) && ($childIsActive || ($item['is_expanded'] ?? false) || self::shouldAlwaysExpandMenu($item['slug'] ?? null)),
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
            'is_expanded' => $childIsActive || self::shouldAlwaysExpandMenu($menu->slug),
            'children' => $children,
        ];
    }

    /**
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function defaultMenuTree(array $activeSlugs): array
    {
        return collect(AdminMenuCatalog::items())
            ->map(fn (array $menu): array => self::menuDefinitionItem($menu, $activeSlugs))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $databaseMenu
     * @param  array<int, array<string, mixed>>  $catalogMenu
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function mergeMenuDefinitions(array $databaseMenu, array $catalogMenu, array $activeSlugs): array
    {
        $databaseBySlug = collect($databaseMenu)->keyBy('slug');
        $merged = [];

        foreach ($catalogMenu as $catalogItem) {
            $databaseItem = $databaseBySlug->pull($catalogItem['slug']);

            $merged[] = is_array($databaseItem)
                ? self::mergeMenuItemWithCatalog($databaseItem, $catalogItem, $activeSlugs)
                : $catalogItem;
        }

        foreach ($databaseBySlug->values() as $databaseItem) {
            $merged[] = $databaseItem;
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $databaseItem
     * @param  array<string, mixed>  $catalogItem
     * @param  array<int, string>  $activeSlugs
     * @return array<string, mixed>
     */
    private static function mergeMenuItemWithCatalog(array $databaseItem, array $catalogItem, array $activeSlugs): array
    {
        $databaseChildren = collect($databaseItem['children'] ?? [])->keyBy('slug');
        $children = [];

        foreach ($catalogItem['children'] ?? [] as $catalogChild) {
            $databaseChild = $databaseChildren->pull($catalogChild['slug']);

            $children[] = is_array($databaseChild)
                ? self::mergeMenuItemWithCatalog($databaseChild, $catalogChild, $activeSlugs)
                : self::menuDefinitionItem($catalogChild, $activeSlugs);
        }

        foreach ($databaseChildren->values() as $databaseChild) {
            $children[] = $databaseChild;
        }

        if (($catalogItem['slug'] ?? null) === 'dashboard') {
            $children = [];
        }

        $slug = (string) ($catalogItem['slug'] ?? $databaseItem['slug']);
        $childIsActive = collect($children)->contains(fn (array $child): bool => $child['is_active']);

        return [
            ...$databaseItem,
            'label' => $catalogItem['label'] ?? $databaseItem['label'],
            'slug' => $slug,
            'icon' => $catalogItem['icon'] ?? ($databaseItem['icon'] ?? null),
            'path' => array_key_exists('path', $catalogItem) ? $catalogItem['path'] : ($databaseItem['path'] ?? null),
            'is_enabled' => $catalogItem['is_enabled'] ?? ($databaseItem['is_enabled'] ?? false),
            'is_active' => in_array($slug, $activeSlugs, true) || $childIsActive,
            'is_expanded' => !empty($children) && ($childIsActive || ($databaseItem['is_expanded'] ?? false) || self::shouldAlwaysExpandMenu($slug)),
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
            'is_expanded' => $childIsActive || self::shouldAlwaysExpandMenu($menu['slug']),
            'children' => $children,
        ];
    }

    private static function shouldAlwaysExpandMenu(?string $slug): bool
    {
        return in_array($slug, ['users-admin-management', 'payments'], true);
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

    /**
     * @return array<string, mixed>
     */
    private static function account(User $user, string $screen): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => self::initials($user->name ?: $user->email),
            'email' => $user->email,
            'role' => $user->role,
            'products_count' => (int) ($user->products_count ?? 0),
            'pending_products_count' => (int) ($user->pending_products_count ?? 0),
            'approved_products_count' => $screen === 'merchants'
                ? (int) ($user->approved_products_count ?? 0)
                : (int) ($user->approved_products_total ?? 0),
            'joined_at' => $user->created_at?->format('M d, Y') ?? 'Unknown',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function sharedLinks(): array
    {
        return [
            'frontend' => route('frontend.home'),
            'admin_users' => route('admin.users.index'),
            'admin_merchants' => route('admin.merchants.index'),
            'admin_wallet' => route('admin.wallet.index'),
            'admin_deposits' => route('admin.deposits.index'),
            'admin_withdrawals' => route('admin.withdrawals.index'),
            'logout' => route('auth.logout'),
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
