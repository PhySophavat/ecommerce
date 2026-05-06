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
        $screen = in_array($screen, ['users', 'merchants', 'customers', 'customer-details', 'purchase-history'], true) ? $screen : 'users';
        $role = match ($screen) {
            'merchants' => 'merchant',
            'customers', 'customer-details', 'purchase-history' => 'customer',
            default => 'admin',
        };
        $accounts = User::query()
            ->where('role', $role)
            ->withCount([
                'products',
                'products as pending_products_count' => fn ($query) => $query->where('status', 'pending'),
                'products as approved_products_count' => fn ($query) => $query->where('status', 'approved'),
                'approvedProducts as approved_products_total' => fn ($query) => $query->where('status', 'approved'),
                'orders',
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
                'role_label' => match ($role) {
                    'merchant' => 'Merchant',
                    'customer' => 'Customer',
                    default => 'Admin user',
                },
                'submit_label' => match ($role) {
                    'merchant' => 'Create merchant',
                    'customer' => 'Create customer',
                    default => 'Create admin user',
                },
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
                    : ($role === 'customer'
                    ? [
                        [
                            'label' => 'Total customers',
                            'value' => (string) $accountsCount,
                            'detail' => 'Customer accounts registered on the storefront',
                        ],
                        [
                            'label' => 'Orders placed',
                            'value' => (string) ((int) $accounts->sum('orders_count')),
                            'detail' => 'Total orders created by customer accounts',
                        ],
                        [
                            'label' => 'Active buyers',
                            'value' => (string) ((int) $accounts->where('orders_count', '>', 0)->count()),
                            'detail' => 'Customers who have placed at least one order',
                        ],
                        [
                            'label' => 'New customers',
                            'value' => (string) ((int) $accounts->where('created_at', '>=', now()->subDays(30))->count()),
                            'detail' => 'Registered within the last 30 days',
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
                    ]),
                'items' => $accounts
                    ->map(fn (User $user): array => self::account($user, $screen))
                    ->values()
                    ->all(),
            ],
        ];
    }

    public static function productsIndex(string $screen = 'products'): array
    {
        if (auth()->user()?->role === 'merchant') {
            return self::merchantProductsIndex($screen, auth()->user());
        }

        $screen = self::normalizedScreen($screen);
        $catalogProducts = Product::query()
            ->with('category')
            ->orderByDesc('id')
            ->get();
        $products = $screen === 'featured-products'
            ? $catalogProducts->where('is_featured', true)->values()
            : $catalogProducts;

        $orders = Order::query()
            ->whereNotNull('customer_id')
            ->get();
        $customerCount = User::query()->where('role', 'customer')->count();
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
                    'detail' => $orders->count().' storefront orders recorded',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Total orders',
                    'value' => (string) $orders->count(),
                    'detail' => $orders->where('status', 'pending')->count().' customer orders pending',
                    'tone' => 'slate',
                ],
                [
                    'label' => 'Total customers',
                    'value' => (string) $customerCount,
                    'detail' => 'Registered storefront customer accounts',
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

    public static function merchantBalancePage(): array
    {
        $meta = self::metaForScreen('merchant-balance');

        return [
            'screen' => 'merchant-balance',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('merchant-balance')),
        ];
    }

    public static function bankAccountsPage(): array
    {
        $meta = self::metaForScreen('bank-accounts');

        return [
            'screen' => 'bank-accounts',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('bank-accounts')),
        ];
    }

    private static function normalizedScreen(string $screen): string
    {
        return in_array($screen, ['dashboard', 'sliders', 'products', 'add-product', 'featured-products', 'users', 'merchants', 'platform-fee-settings', 'withdrawals', 'deposits', 'wallet', 'bank-accounts', 'merchant-balance'], true) ? $screen : 'products';
    }

    private static function merchantProductsIndex(string $screen, User $user): array
    {
        $screen = in_array($screen, ['dashboard', 'products', 'add-product', 'merchant-pending-products', 'merchant-approved-products', 'merchant-rejected-products'], true) ? $screen : 'products';
        $products = Product::query()
            ->where('merchant_id', $user->id)
            ->with('category')
            ->orderByDesc('id')
            ->get();
        $filteredProducts = match ($screen) {
            'merchant-pending-products' => $products->where('status', 'pending')->values(),
            'merchant-approved-products' => $products->where('status', 'approved')->values(),
            'merchant-rejected-products' => $products->where('status', 'rejected')->values(),
            default => $products->values(),
        };

        $menu = self::merchantWorkspaceMenu($screen);
        $pendingCount = $products->where('status', 'pending')->count();
        $approvedCount = $products->where('status', 'approved')->count();
        $rejectedCount = $products->where('status', 'rejected')->count();
        $lowStockCount = $products->where('inventory', '<=', 60)->count();

        return [
            'screen' => $screen,
            'meta' => [
                'brand' => $user->merchant?->shop_name ?? 'Merchant workspace',
                'page_title' => match ($screen) {
                    'dashboard' => 'Dashboard',
                    'add-product' => 'Add Product',
                    'merchant-pending-products' => 'Pending Products',
                    'merchant-approved-products' => 'Approved Products',
                    'merchant-rejected-products' => 'Rejected Products',
                    default => 'Products',
                },
                'kicker' => match ($screen) {
                    'dashboard' => 'Merchant workspace',
                    'add-product' => 'Catalog creation',
                    default => 'Merchant catalog',
                },
                'subheadline' => match ($screen) {
                    'dashboard' => 'Track your storefront products, approval pipeline, and inventory from one merchant dashboard.',
                    'add-product' => 'Create a new product submission for admin review.',
                    'merchant-pending-products' => 'Review the products from your store that are waiting for admin approval.',
                    'merchant-approved-products' => 'Review the products from your store that are already approved for storefront visibility.',
                    'merchant-rejected-products' => 'Review the products from your store that were rejected and need updates before resubmission.',
                    default => 'Manage only the products that belong to your approved merchant account.',
                },
                'primary_action_label' => $screen === 'add-product' ? 'My products' : '+ Add product',
                'links' => [
                    'frontend' => route('frontend.home'),
                    'logout' => route('auth.logout'),
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
                    ['label' => 'Pending review', 'value' => 'pending'],
                ],
                'sizes' => ['S', 'M', 'L', 'XL'],
                'colors' => ['Red', 'Black', 'White', 'Blue', 'Green'],
                'variant_presets' => self::variantPresets(),
            ],
            'summary' => [
                [
                    'label' => 'My products',
                    'value' => (string) $products->count(),
                    'detail' => 'Products owned by your merchant account',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Pending review',
                    'value' => (string) $pendingCount,
                    'detail' => 'Products waiting for admin approval',
                    'tone' => 'slate',
                ],
                [
                    'label' => 'Approved',
                    'value' => (string) $approvedCount,
                    'detail' => 'Products currently visible to customers',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Rejected / low stock',
                    'value' => (string) ($rejectedCount + $lowStockCount),
                    'detail' => $rejectedCount.' rejected, '.$lowStockCount.' low stock',
                    'tone' => 'amber',
                ],
            ],
            'highlights' => [
                ['label' => 'Categories', 'value' => (string) $products->pluck('category_id')->filter()->unique()->count()],
                ['label' => 'Approved', 'value' => (string) $approvedCount],
                ['label' => 'Pending', 'value' => (string) $pendingCount],
                ['label' => 'Rejected', 'value' => (string) $rejectedCount],
            ],
            'menu' => $menu,
            'products' => [
                'count' => $filteredProducts->count(),
                'items' => $filteredProducts->map(fn (Product $product): array => self::product($product))->values()->all(),
                'pagination' => [
                    'page' => 1,
                    'pages' => 1,
                    'from' => $filteredProducts->isEmpty() ? 0 : 1,
                    'to' => $filteredProducts->count(),
                    'total' => $filteredProducts->count(),
                ],
            ],
        ];
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
            'customers' => [
                'page_title' => 'Customers',
                'kicker' => 'Customer accounts',
                'subheadline' => 'View registered storefront customers and monitor account activity.',
            ],
            'customer-details' => [
                'page_title' => 'Customer Details',
                'kicker' => 'Customer accounts',
                'subheadline' => 'Inspect storefront customer profiles and account activity in a read-only view.',
            ],
            'purchase-history' => [
                'page_title' => 'Customer Purchase History',
                'kicker' => 'Customer accounts',
                'subheadline' => 'Review customer accounts alongside the number of orders they have placed.',
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
            'merchant-balance' => [
                'page_title' => 'Merchant Balance',
                'kicker' => 'Merchant finance',
                'subheadline' => 'Review merchant balances, total deposits, withdrawals, and pending funds from one place.',
            ],
            'bank-accounts' => [
                'page_title' => 'Bank Accounts',
                'kicker' => 'Merchant payouts',
                'subheadline' => 'Approve, reject, disable, and clean up merchant payout accounts before they can be used for withdrawals.',
            ],
            'withdrawals' => [
                'page_title' => 'Withdrawals',
                'kicker' => 'Merchant payouts',
                'subheadline' => '.',
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
            'customers' => ['customers', 'all-customers'],
            'customer-details' => ['customers', 'customer-details'],
            'purchase-history' => ['customers', 'purchase-history'],
            'users' => ['users-admin-management', 'admin-users'],
            'merchants' => ['users-admin-management', 'merchants'],
            'merchant-balance' => ['merchant-balance', 'payments', 'transaction-history'],
            'payment-records' => ['payments', 'payment-records'],
            'payment-methods' => ['payments', 'payment-methods'],
            'wallet' => ['wallet'],
            'bank-accounts' => ['bank-accounts'],
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
            self::mergeMenuDefinitions(
                self::applyRoleMenuOverrides($databaseMenu),
                self::applyRoleMenuOverrides($catalogMenu),
                $activeSlugs
            ),
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
     * @param  array<int, array<string, mixed>>  $menuItems
     * @return array<int, array<string, mixed>>
     */
    private static function applyRoleMenuOverrides(array $menuItems): array
    {
        if (auth()->user()?->role !== 'merchant') {
            return $menuItems;
        }

        return collect($menuItems)
            ->map(fn (array $item): array => self::applyMerchantMenuOverrides($item))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function applyMerchantMenuOverrides(array $item): array
    {
        $children = collect($item['children'] ?? [])
            ->map(fn (array $child): array => self::applyMerchantMenuOverrides($child))
            ->values()
            ->all();

        if (($item['slug'] ?? null) !== 'orders') {
            return [
                ...$item,
                'children' => $children,
            ];
        }

        $merchantOrderPaths = [
            'all-orders' => '/merchant/orders',
            'pending-orders' => '/merchant/orders/pending',
            'processing-orders' => '/merchant/orders/processing',
            'shipped-orders' => '/merchant/orders/shipped',
            'delivered-orders' => '/merchant/orders/delivered',
            'cancelled-orders' => '/merchant/orders/cancelled',
            'returns-refunds' => '/merchant/orders/refunded',
        ];

        return [
            ...$item,
            'path' => '/merchant/orders',
            'is_enabled' => true,
            'children' => collect($children)
                ->map(function (array $child) use ($merchantOrderPaths): array {
                    $path = $merchantOrderPaths[$child['slug'] ?? ''] ?? ($child['path'] ?? '/merchant/orders');

                    return [
                        ...$child,
                        'path' => $path,
                        'is_enabled' => true,
                    ];
                })
                ->values()
                ->all(),
        ];
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
     * @return array<int, array<string, mixed>>
     */
    private static function merchantWorkspaceMenu(string $screen): array
    {
        $activeSlugs = match ($screen) {
            'dashboard' => ['dashboard'],
            'add-product' => ['products', 'add-product'],
            'merchant-pending-products' => ['products', 'pending-products'],
            'merchant-approved-products' => ['products', 'approved-products'],
            'merchant-rejected-products' => ['products', 'rejected-products'],
            default => ['products', 'all-products'],
        };

        $children = [
            ['slug' => 'all-products', 'label' => 'All products', 'path' => '/merchant/products', 'is_enabled' => true],
            ['slug' => 'add-product', 'label' => 'Add product', 'path' => '/merchant/products/create', 'is_enabled' => true],
            ['slug' => 'pending-products', 'label' => 'Pending products', 'path' => '/merchant/products/pending', 'is_enabled' => true],
            ['slug' => 'approved-products', 'label' => 'Approved products', 'path' => '/merchant/products/approved', 'is_enabled' => true],
            ['slug' => 'rejected-products', 'label' => 'Rejected products', 'path' => '/merchant/products/rejected', 'is_enabled' => true],
        ];

        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'dashboard',
                'path' => '/merchant/dashboard',
                'is_enabled' => true,
                'is_active' => in_array('dashboard', $activeSlugs, true),
                'is_expanded' => false,
                'children' => [],
            ],
            [
                'id' => 'orders',
                'label' => 'Orders',
                'slug' => 'orders',
                'icon' => 'orders',
                'path' => '/merchant/orders',
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => true,
                'children' => [
                    ['id' => 'all-orders', 'label' => 'All orders', 'slug' => 'all-orders', 'path' => '/merchant/orders', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'pending-orders', 'label' => 'Pending orders', 'slug' => 'pending-orders', 'path' => '/merchant/orders/pending', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'processing-orders', 'label' => 'Processing orders', 'slug' => 'processing-orders', 'path' => '/merchant/orders/processing', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'shipped-orders', 'label' => 'Shipped orders', 'slug' => 'shipped-orders', 'path' => '/merchant/orders/shipped', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'delivered-orders', 'label' => 'Delivered orders', 'slug' => 'delivered-orders', 'path' => '/merchant/orders/delivered', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'cancelled-orders', 'label' => 'Cancelled orders', 'slug' => 'cancelled-orders', 'path' => '/merchant/orders/cancelled', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'returns-refunds', 'label' => 'Returns / refunds', 'slug' => 'returns-refunds', 'path' => '/merchant/orders/refunded', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                ],
            ],
            [
                'id' => 'products',
                'label' => 'Products',
                'slug' => 'products',
                'icon' => 'products',
                'path' => '/merchant/products',
                'is_enabled' => true,
                'is_active' => in_array('products', $activeSlugs, true),
                'is_expanded' => true,
                'children' => collect($children)->map(fn (array $child): array => [
                    ...$child,
                    'id' => $child['slug'],
                    'icon' => null,
                    'is_active' => in_array($child['slug'], $activeSlugs, true),
                    'is_expanded' => false,
                    'children' => [],
                ])->values()->all(),
            ],
            [
                'id' => 'wallet',
                'label' => 'Wallet',
                'slug' => 'wallet',
                'icon' => 'wallet',
                'path' => '/merchant/wallet',
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => true,
                'children' => [
                    ['id' => 'deposit', 'label' => 'Deposit', 'slug' => 'deposit', 'path' => '/merchant/deposits', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'withdraw', 'label' => 'Withdraw', 'slug' => 'withdraw', 'path' => '/merchant/withdrawals', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'transactions', 'label' => 'Transactions', 'slug' => 'transactions', 'path' => '/merchant/wallet/transactions', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'bank-accounts', 'label' => 'Bank Accounts', 'slug' => 'bank-accounts', 'path' => '/merchant/bank-accounts', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                ],
            ],
            [
                'id' => 'logout',
                'label' => 'Logout',
                'slug' => 'logout',
                'icon' => 'logout',
                'path' => null,
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => false,
                'children' => [],
            ],
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
            'orders_count' => (int) ($user->orders_count ?? 0),
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
            'admin_customers' => route('admin.customers.index'),
            'admin_wallet' => route('admin.wallet.index'),
            'admin_bank_accounts' => route('admin.bank-accounts.index'),
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
