<?php

namespace App\Support;

use App\Models\AdminMenu;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\MerchantTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PlatformFeeSetting;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawRequest;
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
            ->with(['items.product.category', 'customer'])
            ->get();
        $customerCount = User::query()->where('role', 'customer')->count();
        $lowStockCount = $catalogProducts->where('inventory', '<', 10)->count();
        $platformFeeTransactions = MerchantTransaction::query()
            ->where('type', 'platform_fee')
            ->latest('created_at')
            ->get();
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
                    'detail' => 'Inventory below 10',
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
            'dashboard_workspace' => self::commerceDashboardWorkspacePayload(
                orders: $orders,
                products: $catalogProducts,
                customerCount: $customerCount,
                options: [
                    'scope' => 'admin',
                    'title' => 'Admin dashboard',
                    'description' => 'Track platform revenue, order flow, customer growth, and inventory risk from one ecommerce control surface.',
                    'health_items' => [
                        ['label' => 'Categories', 'value' => (string) Category::query()->count(), 'description' => 'Live storefront categories'],
                        ['label' => 'Active products', 'value' => (string) $catalogProducts->where('status', 'active')->count(), 'description' => 'Listings ready for visibility'],
                        ['label' => 'Featured products', 'value' => (string) $catalogProducts->where('is_featured', true)->count(), 'description' => 'Products pinned for promotion'],
                        ['label' => 'Draft products', 'value' => (string) $catalogProducts->where('status', 'draft')->count(), 'description' => 'Catalog items still in progress'],
                    ],
                    'fee_transactions' => $platformFeeTransactions,
                    'orders_path' => '/admin/orders',
                    'products_path' => '/admin/products',
                ],
            ),
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

    public static function qrCodesPage(): array
    {
        $meta = self::metaForScreen('qr-codes');

        return [
            'screen' => 'qr-codes',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('qr-codes')),
        ];
    }

    public static function paymentMethodsPage(): array
    {
        $meta = self::metaForScreen('payment-methods');

        return [
            'screen' => 'payment-methods',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('payment-methods')),
        ];
    }

    public static function paymentFeesPage(): array
    {
        $meta = self::metaForScreen('payment-fees');

        return [
            'screen' => 'payment-fees',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('payment-fees')),
        ];
    }

    public static function financeOverviewPage(): array
    {
        $meta = self::metaForScreen('finance-overview');

        return [
            'screen' => 'finance-overview',
            'meta' => [
                'brand' => 'E-commerce',
                'page_title' => $meta['page_title'],
                'kicker' => $meta['kicker'],
                'subheadline' => $meta['subheadline'],
                'links' => self::sharedLinks(),
            ],
            'menu' => self::menuTree(self::activeSlugsForScreen('finance-overview')),
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
        return in_array($screen, ['dashboard', 'sliders', 'products', 'add-product', 'featured-products', 'users', 'merchants', 'platform-fee-settings', 'withdrawals', 'deposits', 'wallet', 'qr-codes', 'bank-accounts', 'merchant-balance', 'finance-overview', 'payment-methods', 'payment-fees'], true) ? $screen : 'products';
    }

    private static function merchantProductsIndex(string $screen, User $user): array
    {
        $screen = in_array($screen, ['dashboard', 'products', 'add-product', 'merchant-pending-products', 'merchant-approved-products', 'merchant-rejected-products'], true) ? $screen : 'products';
        $merchant = $user->merchant()->first();
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

        $menu = self::menuTree(self::activeSlugsForScreen($screen));
        $pendingCount = $products->where('status', 'pending')->count();
        $approvedCount = $products->where('status', 'approved')->count();
        $rejectedCount = $products->where('status', 'rejected')->count();
        $lowStockCount = $products->where('inventory', '<', 10)->count();
        $balanceUsd = (float) ($merchant?->balance_total ?? 0);
        $balanceKhr = $balanceUsd * 4100;
        $walletTransactions = $merchant?->walletTransactions()->get() ?? collect();
        $transactionIn = (float) $walletTransactions
            ->where('direction', 'credit')
            ->sum(fn ($transaction) => (float) $transaction->amount);
        $transactionOut = (float) $walletTransactions
            ->where('direction', 'debit')
            ->sum(fn ($transaction) => (float) $transaction->amount);
        $availableUsd = (float) ($merchant?->available_balance ?? 0);
        $pendingUsd = (float) ($merchant?->pending_balance ?? 0);
        $recentMovement = $walletTransactions->take(5)->count();
        $merchantOrderIds = $merchant
            ? OrderItem::query()
                ->where('merchant_id', $merchant->id)
                ->distinct()
                ->pluck('order_id')
            : collect();
        $merchantOrders = $merchantOrderIds->isNotEmpty()
            ? Order::query()->whereIn('id', $merchantOrderIds)->with(['items.product.category', 'customer'])->get()
            : collect();
        $bankPaymentCount = $merchantOrders
            ->whereIn('payment_method', ['aba_qr', 'wing', 'card'])
            ->count();
        $cashPaymentCount = $merchantOrders
            ->where('payment_method', 'cash')
            ->count();
        $totalPaymentOrders = $merchantOrders->count();
        $bankPaymentPercent = $totalPaymentOrders > 0
            ? (int) round(($bankPaymentCount / $totalPaymentOrders) * 100)
            : 0;
        $orderAnalytics = self::merchantOrderAnalytics($merchantOrders);
        $merchantDashboard = self::merchantDashboardPayload(
            $merchant,
            $products,
            $merchantOrders,
            $walletTransactions,
            $availableUsd,
            $pendingUsd,
            $balanceUsd,
            $balanceKhr,
            $transactionIn,
            $transactionOut,
        );

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
                    'label' => 'Total balance USD',
                    'value' => self::currency($balanceUsd),
                    'detail' => 'Current merchant wallet total balance',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Balance KHR',
                    'value' => self::khrCurrency($balanceKhr),
                    'detail' => 'Estimated using 4,100 KHR per USD',
                    'tone' => 'slate',
                ],
                [
                    'label' => 'Transaction in',
                    'value' => self::currency($transactionIn),
                    'detail' => 'Total credited into merchant wallet',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Transaction out',
                    'value' => self::currency($transactionOut),
                    'detail' => 'Total debited from merchant wallet',
                    'tone' => 'amber',
                ],
            ],
            'highlights' => [
                ['label' => 'Available USD', 'value' => self::currency($availableUsd)],
                ['label' => 'Pending USD', 'value' => self::currency($pendingUsd)],
                ['label' => 'Products', 'value' => (string) $products->count()],
                ['label' => 'Recent txns', 'value' => (string) $recentMovement],
                ['label' => 'Approved', 'value' => (string) $approvedCount],
                ['label' => 'Pending', 'value' => (string) $pendingCount],
                ['label' => 'Rejected', 'value' => (string) $rejectedCount],
                ['label' => 'Low stock', 'value' => (string) $lowStockCount],
            ],
            'payment_metrics' => [
                'bank_percent' => $bankPaymentPercent,
                'bank_orders' => $bankPaymentCount,
                'cash_orders' => $cashPaymentCount,
                'total_orders' => $totalPaymentOrders,
                'label' => 'Customer pay with bank',
                'bank_methods' => 'ABA / Wing / Card',
            ],
            'order_analytics' => $orderAnalytics,
            'dashboard_workspace' => self::commerceDashboardWorkspacePayload(
                orders: $merchantOrders,
                products: $products,
                customerCount: (int) $merchantOrders
                    ->pluck('customer_id')
                    ->filter()
                    ->unique()
                    ->count(),
                options: [
                    'scope' => 'merchant',
                    'title' => $user->merchant?->shop_name ? $user->merchant->shop_name.' dashboard' : 'Merchant dashboard',
                    'description' => 'Monitor revenue, customer orders, and low-stock pressure from the same commerce workspace used across the admin side.',
                    'health_items' => [
                        ['label' => 'Available balance', 'value' => self::currency($availableUsd), 'description' => 'Ready for payout'],
                        ['label' => 'Pending balance', 'value' => self::currency($pendingUsd), 'description' => 'Awaiting release'],
                        ['label' => 'Approved products', 'value' => (string) $approvedCount, 'description' => 'Listings visible on storefront'],
                        ['label' => 'Pending review', 'value' => (string) $pendingCount, 'description' => 'Awaiting admin approval'],
                    ],
                    'orders_path' => '/merchant/orders',
                    'products_path' => '/merchant/products',
                ],
            ),
            'merchant_dashboard' => $merchantDashboard,
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

    private static function merchantDashboardPayload(
        ?Merchant $merchant,
        Collection $products,
        Collection $orders,
        Collection $walletTransactions,
        float $availableUsd,
        float $pendingUsd,
        float $balanceUsd,
        float $balanceKhr,
        float $transactionIn,
        float $transactionOut,
    ): array {
        $merchantId = $merchant?->id;
        $financeTransactions = $merchantId && Schema::hasTable('transactions')
            ? Transaction::query()->where('merchant_id', $merchantId)->latest('created_at')->get()
            : collect();
        $payments = $merchantId && Schema::hasTable('payments')
            ? Payment::query()->where('merchant_id', $merchantId)->latest('created_at')->get()
            : collect();
        $withdrawRequests = $merchantId && Schema::hasTable('withdraw_requests')
            ? WithdrawRequest::query()->where('merchant_id', $merchantId)->latest('created_at')->get()
            : collect();

        $successfulOrders = $orders->whereIn('status', ['paid', 'processing', 'completed', 'shipped', 'delivered'])->count();
        $failedOrders = $orders->whereIn('status', ['failed', 'payment_failed'])->count();
        $successfulPayments = $payments->where('status', 'success')->count();
        $failedPayments = $payments->where('status', 'failed')->count();
        $totalTransactions = $financeTransactions->count() ?: $walletTransactions->count();
        $recentOrdersCount = $orders->filter(fn (Order $order): bool => ($order->placed_at ?? $order->created_at)?->gte(now()->subDays(7)) ?? false)->count();
        $recentFailedPayments = $payments->filter(fn (Payment $payment): bool => $payment->status === 'failed' && (($payment->created_at)?->gte(now()->subDays(7)) ?? false))->count();
        $pendingWithdrawRequests = $withdrawRequests->where('status', 'pending')->count();

        $summaryCards = [
            [
                'label' => 'Total Balance',
                'value' => self::currency($balanceUsd),
                'description' => 'Current merchant wallet total balance',
                'icon' => 'wallet',
                'tone' => 'primary',
            ],
            [
                'label' => 'Balance KHR',
                'value' => self::khrCurrency($balanceKhr),
                'description' => 'Estimated using 4,100 KHR per USD',
                'icon' => 'bank',
                'tone' => 'slate',
            ],
            [
                'label' => 'Transaction In',
                'value' => self::currency($transactionIn),
                'description' => 'Total credited into merchant wallet',
                'icon' => 'arrow-down',
                'tone' => 'success',
            ],
            [
                'label' => 'Transaction Out',
                'value' => self::currency($transactionOut),
                'description' => 'Total debited from merchant wallet',
                'icon' => 'arrow-up',
                'tone' => 'warning',
            ],
            [
                'label' => 'Total Transactions',
                'value' => (string) $totalTransactions,
                'description' => 'Combined finance ledger entries',
                'icon' => 'activity',
                'tone' => 'info',
            ],
            [
                'label' => 'Successful Orders',
                'value' => (string) $successfulOrders,
                'description' => 'Paid and fulfilled order volume',
                'icon' => 'check',
                'tone' => 'success',
            ],
            [
                'label' => 'Failed Orders',
                'value' => (string) $failedOrders,
                'description' => 'Failed or payment-failed orders',
                'icon' => 'x',
                'tone' => 'danger',
            ],
            [
                'label' => 'Successful Payments',
                'value' => (string) $successfulPayments,
                'description' => 'Completed customer payments',
                'icon' => 'credit-card',
                'tone' => 'success',
            ],
            [
                'label' => 'Failed Payments',
                'value' => (string) $failedPayments,
                'description' => 'Payments that did not clear',
                'icon' => 'alert',
                'tone' => 'danger',
            ],
        ];

        return [
            'hero' => [
                'eyebrow' => 'MERCHANT FINANCE',
                'title' => 'Quick balance access',
                'description' => 'Open your wallet balance, scan commerce performance, and jump into product work from one merchant dashboard.',
            ],
            'date_ranges' => [
                ['label' => 'Today', 'value' => 'today'],
                ['label' => '7 days', 'value' => '7days'],
                ['label' => '30 days', 'value' => '30days'],
                ['label' => 'Custom range', 'value' => 'custom'],
            ],
            'selected_range' => '30days',
            'summary_cards' => $summaryCards,
            'datasets' => [
                'today' => self::merchantDashboardDataset($orders, $products, $payments, $financeTransactions, $walletTransactions, $withdrawRequests, $availableUsd, $pendingUsd, 1),
                '7days' => self::merchantDashboardDataset($orders, $products, $payments, $financeTransactions, $walletTransactions, $withdrawRequests, $availableUsd, $pendingUsd, 7),
                '30days' => self::merchantDashboardDataset($orders, $products, $payments, $financeTransactions, $walletTransactions, $withdrawRequests, $availableUsd, $pendingUsd, 30),
                'custom' => self::merchantDashboardDataset($orders, $products, $payments, $financeTransactions, $walletTransactions, $withdrawRequests, $availableUsd, $pendingUsd, 30),
            ],
            'links' => [
                'balance' => '/merchant/qr-codes',
                'products' => '/merchant/products',
            ],
        ];
    }

    private static function merchantDashboardDataset(
        Collection $orders,
        Collection $products,
        Collection $payments,
        Collection $financeTransactions,
        Collection $walletTransactions,
        Collection $withdrawRequests,
        float $availableUsd,
        float $pendingUsd,
        int $days,
    ): array {
        $start = now()->copy()->subDays(max($days - 1, 0))->startOfDay();
        $end = now()->copy()->endOfDay();
        $periodDays = max($days, 1);
        $labels = collect(range(0, $periodDays - 1))
            ->map(fn (int $offset): string => $start->copy()->addDays($offset)->format('j M'))
            ->values();

        $ordersInRange = $orders->filter(function (Order $order) use ($start, $end): bool {
            $date = $order->placed_at ?? $order->created_at;

            return $date?->between($start, $end) ?? false;
        })->values();

        $paymentsInRange = $payments->filter(fn (Payment $payment): bool => $payment->created_at?->between($start, $end) ?? false)->values();
        $financeTransactionsInRange = $financeTransactions->filter(fn (Transaction $transaction): bool => $transaction->created_at?->between($start, $end) ?? false)->values();
        $fallbackTransactions = $walletTransactions->filter(fn (WalletTransaction $transaction): bool => $transaction->created_at?->between($start, $end) ?? false)->values();

        $salesOverTime = $labels->map(function (string $label, int $offset) use ($start, $ordersInRange): array {
            $day = $start->copy()->addDays($offset);
            $dayOrders = $ordersInRange->filter(fn (Order $order): bool => (($order->placed_at ?? $order->created_at)?->isSameDay($day)) ?? false);

            return [
                'label' => $label,
                'sales' => round((float) $dayOrders->sum('total_amount'), 2),
                'orders' => $dayOrders->count(),
            ];
        })->values()->all();

        $runningTotal = 0;
        $cumulativeSales = collect($salesOverTime)->map(function (array $point) use (&$runningTotal): array {
            $runningTotal += (float) $point['sales'];

            return [
                'label' => $point['label'],
                'sales' => round($runningTotal, 2),
            ];
        })->values()->all();

        $orderStatusSummary = [
            ['label' => 'Completed', 'value' => $ordersInRange->whereIn('status', ['completed', 'delivered'])->count(), 'color' => '#10B981'],
            ['label' => 'Cancelled', 'value' => $ordersInRange->where('status', 'cancelled')->count(), 'color' => '#94A3B8'],
            ['label' => 'Refunded', 'value' => $ordersInRange->whereIn('status', ['refunded'])->count(), 'color' => '#60A5FA'],
            ['label' => 'Failed', 'value' => $ordersInRange->whereIn('status', ['failed', 'payment_failed'])->count(), 'color' => '#EF4444'],
        ];

        $paymentCountByBank = collect([
            'ABA' => ['aba_qr', 'ABA'],
            'ACLEDA' => ['acleda', 'ACLEDA'],
            'Wing' => ['wing', 'Wing'],
            'Cash' => ['cash', 'Cash'],
            'Card' => ['card', 'Card'],
        ])->map(function (array $aliases, string $label) use ($paymentsInRange, $ordersInRange): array {
            $paymentCount = $paymentsInRange->whereIn('payment_method', $aliases)->count();
            $fallbackCount = $ordersInRange->whereIn('payment_method', $aliases)->count();

            return [
                'label' => $label,
                'value' => $paymentCount > 0 ? $paymentCount : $fallbackCount,
                'color' => [
                    'ABA' => '#A25F88',
                    'ACLEDA' => '#3B82F6',
                    'Wing' => '#F59E0B',
                    'Cash' => '#10B981',
                    'Card' => '#8B5CF6',
                ][$label],
            ];
        })->values()->all();

        $transactionsForRange = $financeTransactionsInRange->isNotEmpty()
            ? $financeTransactionsInRange
            : $fallbackTransactions;

        $transactionFlow = [
            ['label' => 'IN', 'value' => round((float) $transactionsForRange->where('type', 'IN')->sum('amount') ?: (float) $transactionsForRange->where('direction', 'credit')->sum('amount'), 2), 'color' => '#10B981'],
            ['label' => 'OUT', 'value' => round((float) $transactionsForRange->where('type', 'OUT')->sum('amount') ?: (float) $transactionsForRange->where('direction', 'debit')->sum('amount'), 2), 'color' => '#F59E0B'],
        ];

        $topProductSales = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as sold_quantity, SUM(line_total) as total_sales')
            ->whereIn('order_id', $ordersInRange->pluck('id'))
            ->groupBy('product_name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get()
            ->map(fn ($item): array => [
                'product_name' => $item->product_name,
                'sold_quantity' => (int) $item->sold_quantity,
                'total_sales' => self::currency((float) $item->total_sales),
            ])
            ->values()
            ->all();

        if ($topProductSales === []) {
            $topProductSales = $products->take(5)->map(fn (Product $product, int $index): array => [
                'product_name' => $product->name,
                'sold_quantity' => max(0, 18 - ($index * 3)),
                'total_sales' => self::currency((18 - ($index * 3)) * (float) $product->price),
            ])->values()->all();
        }

        $recentTransactions = ($financeTransactionsInRange->isNotEmpty() ? $financeTransactionsInRange : $fallbackTransactions)
            ->sortByDesc('created_at')
            ->take(8)
            ->map(function ($transaction): array {
                $status = $transaction->status ?? (isset($transaction->direction) ? 'success' : 'pending');
                $type = $transaction->type ?? (($transaction->direction ?? 'credit') === 'credit' ? 'IN' : 'OUT');

                return [
                    'transaction_id' => $transaction->transaction_code ?? ('WTX-'.$transaction->id),
                    'order_id' => $transaction->order_id ? 'ORD-'.$transaction->order_id : '-',
                    'type' => $type,
                    'amount' => self::currency((float) abs((float) $transaction->amount)),
                    'currency' => $transaction->currency ?? 'USD',
                    'payment_method' => $transaction->method ?? 'Wallet',
                    'status' => Str::headline((string) $status),
                    'date' => $transaction->created_at?->format('d M Y') ?? '-',
                ];
            })
            ->values()
            ->all();

        $extraCards = [
            ['label' => 'Available USD', 'value' => self::currency($availableUsd)],
            ['label' => 'Pending USD', 'value' => self::currency($pendingUsd)],
            ['label' => 'Recent Orders Count', 'value' => (string) $ordersInRange->count()],
            ['label' => 'Recent Failed Payments', 'value' => (string) $paymentsInRange->where('status', 'failed')->count()],
            ['label' => 'Pending Withdraw Requests', 'value' => (string) $withdrawRequests->where('status', 'pending')->count()],
        ];

        return [
            'sales_over_time' => $salesOverTime,
            'order_status_summary' => $orderStatusSummary,
            'payment_count_by_bank' => $paymentCountByBank,
            'transaction_flow' => $transactionFlow,
            'top_product_sales' => $topProductSales,
            'cumulative_sales' => $cumulativeSales,
            'recent_transactions' => $recentTransactions,
            'extra_cards' => $extraCards,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function commerceDashboardWorkspacePayload(
        Collection $orders,
        Collection $products,
        int $customerCount,
        array $options = [],
    ): array {
        $scope = (string) ($options['scope'] ?? 'admin');
        $title = (string) ($options['title'] ?? ($scope === 'merchant' ? 'Merchant dashboard' : 'E-commerce Overview'));
        $description = (string) ($options['description'] ?? 'Track sales, orders, customers, and stock health.');
        $feeTransactions = collect($options['fee_transactions'] ?? []);
        $ordersPath = (string) ($options['orders_path'] ?? ($scope === 'merchant' ? '/merchant/orders' : '/admin/orders'));
        $productsPath = (string) ($options['products_path'] ?? ($scope === 'merchant' ? '/merchant/products' : '/admin/products'));
        $lowStockItems = $products
            ->filter(fn (Product $product): bool => (int) $product->inventory < 10)
            ->sortBy('inventory')
            ->values();
        $totalProducts = $products->count();
        $pendingOrders = $orders->where('status', 'pending')->count();
        $adminDirectSales = $scope === 'admin' ? self::sumAdminOwnedSales($orders) : 0.0;

        $salesCurrent = $scope === 'admin'
            ? self::sumMerchantTransactionsInRange($feeTransactions, now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay())
                + self::sumAdminOwnedSalesInRange($orders, now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay())
            : self::sumOrdersInRange($orders, now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay());
        $salesPrevious = $scope === 'admin'
            ? self::sumMerchantTransactionsInRange($feeTransactions, now()->copy()->subDays(59)->startOfDay(), now()->copy()->subDays(30)->endOfDay())
                + self::sumAdminOwnedSalesInRange($orders, now()->copy()->subDays(59)->startOfDay(), now()->copy()->subDays(30)->endOfDay())
            : self::sumOrdersInRange($orders, now()->copy()->subDays(59)->startOfDay(), now()->copy()->subDays(30)->endOfDay());
        $ordersCurrent = self::countOrdersInRange($orders, now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay());
        $ordersPrevious = self::countOrdersInRange($orders, now()->copy()->subDays(59)->startOfDay(), now()->copy()->subDays(30)->endOfDay());
        $customersCurrent = self::countCustomersInRange($orders, now()->copy()->subDays(29)->startOfDay(), now()->copy()->endOfDay());
        $customersPrevious = self::countCustomersInRange($orders, now()->copy()->subDays(59)->startOfDay(), now()->copy()->subDays(30)->endOfDay());
        $lowStockCurrent = $lowStockItems->count();
        $datasetRanges = [
            'today' => self::commerceDashboardDataset($orders, $products, 'today', [
                'scope' => $scope,
                'fee_transactions' => $feeTransactions,
            ]),
            '7days' => self::commerceDashboardDataset($orders, $products, '7days', [
                'scope' => $scope,
                'fee_transactions' => $feeTransactions,
            ]),
            '30days' => self::commerceDashboardDataset($orders, $products, '30days', [
                'scope' => $scope,
                'fee_transactions' => $feeTransactions,
            ]),
            'thisyear' => self::commerceDashboardDataset($orders, $products, 'thisyear', [
                'scope' => $scope,
                'fee_transactions' => $feeTransactions,
            ]),
        ];

        return [
            'hero' => [
                'eyebrow' => $scope === 'merchant' ? 'Merchant analytics' : 'Admin analytics',
                'title' => $title,
                'description' => $description,
            ],
            'analytics' => [
                'primary_chart' => [
                    'title' => 'Customer Purchases Over Time',
                    'eyebrow' => 'Sales analytics',
                ],
                'secondary_chart' => $scope === 'admin'
                    ? [
                        'title' => 'Platform Revenue Trend',
                        'eyebrow' => 'Fee + admin product sales',
                    ]
                    : [
                        'title' => 'Order Volume Trend',
                        'eyebrow' => 'Order movement',
                    ],
            ],
            'summary_cards' => [
                self::dashboardSummaryCard(
                    label: $scope === 'admin' ? 'Total Revenue' : 'Total Sales',
                    value: $scope === 'admin'
                        ? self::currency(self::absoluteSum($feeTransactions->pluck('amount')) + $adminDirectSales)
                        : self::currency((float) $orders->sum('total_amount')),
                    description: $scope === 'admin'
                        ? 'Platform fees plus customer purchases for admin-owned products.'
                        : 'Gross revenue from customer product purchases.',
                    theme: 'sales',
                    trend: self::trendMeta($salesCurrent, $salesPrevious)
                ),
                self::dashboardSummaryCard(
                    label: 'Total Orders',
                    value: (string) $orders->count(),
                    description: 'Orders recorded and ready for review.',
                    theme: 'orders',
                    trend: self::trendMeta($ordersCurrent, $ordersPrevious)
                ),
                self::dashboardSummaryCard(
                    label: 'Total Customers',
                    value: (string) $customerCount,
                    description: 'Unique buyers connected to this dashboard scope.',
                    theme: 'customers',
                    trend: self::trendMeta($customersCurrent, $customersPrevious)
                ),
                self::dashboardSummaryCard(
                    label: 'Total Products',
                    value: (string) $totalProducts,
                    description: 'Products currently managed in this workspace.',
                    theme: 'products',
                    trend: self::neutralTrendMeta()
                ),
                self::dashboardSummaryCard(
                    label: 'Pending Orders',
                    value: (string) $pendingOrders,
                    description: 'Orders waiting for action or confirmation.',
                    theme: 'pending',
                    trend: self::neutralTrendMeta()
                ),
                self::dashboardSummaryCard(
                    label: 'Low Stock Products',
                    value: (string) $lowStockCurrent,
                    description: 'Inventory lines at or below the watch threshold.',
                    theme: 'stock',
                    trend: self::neutralTrendMeta()
                ),
            ],
            'range_options' => [
                ['value' => 'today', 'label' => 'Today'],
                ['value' => '7days', 'label' => '7 Days'],
                ['value' => '30days', 'label' => '30 Days'],
                ['value' => 'thisyear', 'label' => 'This Year'],
            ],
            'selected_range' => '30days',
            'datasets' => $datasetRanges,
            'store_health' => array_values($options['health_items'] ?? []),
            'recent_orders' => $orders
                ->sortByDesc(fn (Order $order) => self::orderDate($order)?->getTimestamp() ?? 0)
                ->take(6)
                ->map(fn (Order $order): array => [
                    'id' => $order->number ?: 'ORD-'.$order->id,
                    'customer' => $order->customer_name ?: ($order->customer?->name ?: 'Walk-in customer'),
                    'product' => collect($order->items ?? [])
                        ->pluck('product_name')
                        ->filter()
                        ->take(2)
                        ->implode(', ') ?: 'Mixed items',
                    'amount' => self::currency((float) $order->total_amount),
                    'status' => self::dashboardOrderStatusLabel($order->status),
                    'payment' => self::dashboardPaymentLabel($order->payment_method),
                    'date' => self::orderDate($order)?->format('M d, Y') ?? '-',
                ])
                ->values()
                ->all(),
            'low_stock_products' => $lowStockItems
                ->take(6)
                ->map(fn (Product $product): array => [
                    'name' => $product->name,
                    'category' => $product->category?->name ?? 'Uncategorized',
                    'stock' => (int) $product->inventory,
                    'status' => (int) $product->inventory <= 15 ? 'Critical' : 'Low',
                ])
                ->values()
                ->all(),
            'actions' => [
                'orders_path' => $ordersPath,
                'products_path' => $productsPath,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function commerceDashboardDataset(Collection $orders, Collection $products, string $rangeKey, array $options = []): array
    {
        $scope = (string) ($options['scope'] ?? 'admin');
        $feeTransactions = collect($options['fee_transactions'] ?? []);
        [$start, $end, $points] = self::dashboardRangePoints($rangeKey);
        $ordersInRange = $orders->filter(fn (Order $order): bool => (self::orderDate($order)?->between($start, $end)) ?? false)->values();
        $feesInRange = $feeTransactions->filter(fn (MerchantTransaction $transaction): bool => ($transaction->created_at?->between($start, $end)) ?? false)->values();
        $previousRange = self::previousDashboardRange($start, $end, $rangeKey);
        $previousOrders = $orders->filter(fn (Order $order): bool => (self::orderDate($order)?->between($previousRange['start'], $previousRange['end'])) ?? false)->values();
        $previousFees = $feeTransactions->filter(fn (MerchantTransaction $transaction): bool => ($transaction->created_at?->between($previousRange['start'], $previousRange['end'])) ?? false)->values();

        $salesBars = collect($points)->map(function (array $point) use ($ordersInRange): array {
            $bucketOrders = $ordersInRange->filter(function (Order $order) use ($point): bool {
                $date = self::orderDate($order);

                return $date ? ($date->gte($point['start']) && $date->lte($point['end'])) : false;
            });

            return [
                'label' => $point['label'],
                'value' => round((float) $bucketOrders->sum('total_amount'), 2),
                'orders' => $bucketOrders->count(),
            ];
        })->values()->all();

        $secondarySeries = $scope === 'admin'
            ? collect($points)->map(function (array $point) use ($feesInRange): array {
                $bucketFees = $feesInRange->filter(function (MerchantTransaction $transaction) use ($point): bool {
                    $date = $transaction->created_at;

                    return $date ? ($date->gte($point['start']) && $date->lte($point['end'])) : false;
                });

                return [
                    'label' => $point['label'],
                    'value' => self::absoluteSum($bucketFees->pluck('amount')),
                    'orders' => $bucketFees->count(),
                ];
            })->values()->all()
            : collect($points)->map(function (array $point) use ($ordersInRange): array {
                $bucketOrders = $ordersInRange->filter(function (Order $order) use ($point): bool {
                    $date = self::orderDate($order);

                    return $date ? ($date->gte($point['start']) && $date->lte($point['end'])) : false;
                });

                return [
                    'label' => $point['label'],
                    'value' => $bucketOrders->count(),
                    'orders' => $bucketOrders->count(),
                ];
            })->values()->all();

        $currentRevenue = round((float) $ordersInRange->sum('total_amount'), 2);
        $previousRevenue = round((float) $previousOrders->sum('total_amount'), 2);
        $currentSecondary = $scope === 'admin'
            ? self::absoluteSum($feesInRange->pluck('amount')) + self::sumAdminOwnedSales($ordersInRange)
            : (float) $ordersInRange->count();
        $previousSecondary = $scope === 'admin'
            ? self::absoluteSum($previousFees->pluck('amount')) + self::sumAdminOwnedSales($previousOrders)
            : (float) $previousOrders->count();
        $salesByCategory = self::dashboardSalesByCategory($ordersInRange, $products);
        $topProducts = self::dashboardTopProducts($ordersInRange, $products);
        $paymentMethods = self::dashboardPaymentMethodSummary($ordersInRange);

        return [
            'sales_bars' => $salesBars,
            'revenue_trend' => $secondarySeries,
            'order_status' => self::dashboardOrderStatusSummary($ordersInRange),
            'sales_by_category' => $salesByCategory,
            'top_products' => $topProducts,
            'payment_methods' => $paymentMethods,
            'trend' => self::trendMeta($currentRevenue, $previousRevenue),
            'secondary_trend' => self::trendMeta($currentSecondary, $previousSecondary),
        ];
    }

    /**
     * @return array{0:\Illuminate\Support\Carbon,1:\Illuminate\Support\Carbon,2:array<int, array{label:string,start:\Illuminate\Support\Carbon,end:\Illuminate\Support\Carbon}>}
     */
    private static function dashboardRangePoints(string $rangeKey): array
    {
        return match ($rangeKey) {
            'today' => self::todayRangePoints(),
            '7days' => self::daysRangePoints(7),
            'thisyear' => self::yearRangePoints(),
            default => self::daysRangePoints(30),
        };
    }

    /**
     * @return array{0:\Illuminate\Support\Carbon,1:\Illuminate\Support\Carbon,2:array<int, array{label:string,start:\Illuminate\Support\Carbon,end:\Illuminate\Support\Carbon}>}
     */
    private static function todayRangePoints(): array
    {
        $start = now()->copy()->startOfDay();
        $end = now()->copy()->endOfDay();
        $offsets = [0, 4, 8, 12, 16, 20];

        $points = collect($offsets)->map(function (int $offset, int $index) use ($start, $offsets): array {
            $pointStart = $start->copy()->addHours($offset);
            $pointEnd = $index === count($offsets) - 1
                ? $start->copy()->endOfDay()
                : $start->copy()->addHours($offsets[$index + 1])->subSecond();

            return [
                'label' => $pointStart->format('g A'),
                'start' => $pointStart,
                'end' => $pointEnd,
            ];
        })->values()->all();

        return [$start, $end, $points];
    }

    /**
     * @return array{0:\Illuminate\Support\Carbon,1:\Illuminate\Support\Carbon,2:array<int, array{label:string,start:\Illuminate\Support\Carbon,end:\Illuminate\Support\Carbon}>}
     */
    private static function daysRangePoints(int $days): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();
        $points = collect(range(0, $days - 1))
            ->map(function (int $offset) use ($start): array {
                $day = $start->copy()->addDays($offset);

                return [
                    'label' => $day->format('j M'),
                    'start' => $day->copy()->startOfDay(),
                    'end' => $day->copy()->endOfDay(),
                ];
            })
            ->values()
            ->all();

        return [$start, $end, $points];
    }

    /**
     * @return array{0:\Illuminate\Support\Carbon,1:\Illuminate\Support\Carbon,2:array<int, array{label:string,start:\Illuminate\Support\Carbon,end:\Illuminate\Support\Carbon}>}
     */
    private static function yearRangePoints(): array
    {
        $start = now()->copy()->startOfYear();
        $end = now()->copy()->endOfYear();
        $points = collect(range(1, 12))
            ->map(function (int $month) use ($start): array {
                $date = $start->copy()->month($month);

                return [
                    'label' => $date->format('M'),
                    'start' => $date->copy()->startOfMonth(),
                    'end' => $date->copy()->endOfMonth(),
                ];
            })
            ->values()
            ->all();

        return [$start, $end, $points];
    }

    /**
     * @param  \Illuminate\Support\Carbon  $start
     * @param  \Illuminate\Support\Carbon  $end
     * @return array{start:\Illuminate\Support\Carbon,end:\Illuminate\Support\Carbon}
     */
    private static function previousDashboardRange($start, $end, string $rangeKey): array
    {
        return match ($rangeKey) {
            'today' => [
                'start' => $start->copy()->subDay()->startOfDay(),
                'end' => $end->copy()->subDay()->endOfDay(),
            ],
            'thisyear' => [
                'start' => $start->copy()->subYear()->startOfYear(),
                'end' => $end->copy()->subYear()->endOfYear(),
            ],
            default => [
                'start' => $start->copy()->subDays($start->diffInDays($end) + 1)->startOfDay(),
                'end' => $start->copy()->subDay()->endOfDay(),
            ],
        };
    }

    /**
     * @param  array<int, Order>|\Illuminate\Support\Collection<int, Order>  $orders
     * @return array<int, array{label:string,value:int,color:string}>
     */
    private static function dashboardOrderStatusSummary(Collection $orders): array
    {
        return [
            ['label' => 'Completed', 'value' => $orders->whereIn('status', ['completed', 'delivered'])->count(), 'color' => '#10B981'],
            ['label' => 'Pending', 'value' => $orders->where('status', 'pending')->count(), 'color' => '#F59E0B'],
            ['label' => 'Cancelled', 'value' => $orders->whereIn('status', ['cancelled', 'failed', 'payment_failed', 'refunded'])->count(), 'color' => '#EF4444'],
            ['label' => 'Processing', 'value' => $orders->whereIn('status', ['paid', 'processing', 'shipped'])->count(), 'color' => '#3B82F6'],
        ];
    }

    /**
     * @return array<int, array{label:string,value:float}>
     */
    private static function dashboardSalesByCategory(Collection $orders, Collection $products): array
    {
        $salesTotals = collect($orders)
            ->flatMap(fn (Order $order) => collect($order->items ?? []))
            ->groupBy(fn ($item): string => $item->product?->category?->slug ?? 'uncategorized')
            ->map(function (Collection $items, string $slug): array {
                $first = $items->first();

                return [
                    'label' => $first?->product?->category?->name ?? 'Uncategorized',
                    'slug' => $slug,
                    'value' => round((float) $items->sum('line_total'), 2),
                ];
            })
            ->filter(fn (array $item): bool => $item['value'] > 0)
            ->sortBy(fn (array $item): array => [self::categoryOrder($item['slug']), $item['label']])
            ->values()
            ->all();

        return $salesTotals;
    }

    /**
     * @return array<int, array{label:string,value:int}>
     */
    private static function dashboardTopProducts(Collection $orders, Collection $products): array
    {
        $rows = collect($orders)
            ->flatMap(fn (Order $order) => collect($order->items ?? []))
            ->groupBy(fn ($item): string => (string) ($item->product_name ?? 'Unknown'))
            ->map(fn (Collection $items, string $label): array => [
                'label' => $label,
                'value' => (int) $items->sum('quantity'),
            ])
            ->sortByDesc('value')
            ->take(5)
            ->values()
            ->all();

        return $rows;
    }

    /**
     * @return array<int, array{label:string,value:int,color:string}>
     */
    private static function dashboardPaymentMethodSummary(Collection $orders): array
    {
        $map = [
            'ABA' => ['aba_qr', 'ABA'],
            'ACLEDA' => ['acleda', 'ACLEDA'],
            'Wing' => ['wing', 'Wing'],
            'Cash' => ['cash', 'Cash'],
            'QR Payment' => ['card', 'Card'],
        ];

        return collect($map)->map(function (array $aliases, string $label) use ($orders): array {
            return [
                'label' => $label,
                'value' => $orders->whereIn('payment_method', $aliases)->count(),
                'color' => [
                    'ABA' => '#3550A4',
                    'ACLEDA' => '#27B5D0',
                    'Wing' => '#FB923C',
                    'Cash' => '#8B5CF6',
                    'QR Payment' => '#F2C94C',
                ][$label],
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function dashboardSummaryCard(
        string $label,
        string $value,
        string $description,
        string $theme,
        array $trend,
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'description' => $description,
            'theme' => $theme,
            'trend' => $trend,
            'icon' => match ($theme) {
                'sales' => 'M3 10.5 12 4l9 6.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8.5ZM9 14h6',
                'orders' => 'M4 7h16M7 12h10M9 17h6',
                'customers' => 'M16 19a4 4 0 0 0-8 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
                'products' => 'M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9ZM12 12v9M4 7.5l8 4.5 8-4.5',
                'pending' => 'M12 8v5l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                'stock' => 'M20 7 9 18l-5-5',
                default => 'M12 5v14M5 12h14',
            },
        ];
    }

    /**
     * @return array{direction:string,label:string,delta:string}
     */
    private static function trendMeta(float|int $current, float|int $previous): array
    {
        $currentValue = (float) $current;
        $previousValue = (float) $previous;
        $direction = $currentValue >= $previousValue ? 'up' : 'down';
        $delta = $previousValue === 0.0
            ? ($currentValue === 0.0 ? 0.0 : 100.0)
            : round((($currentValue - $previousValue) / abs($previousValue)) * 100, 1);

        return [
            'direction' => $direction,
            'label' => sprintf('%s%s%%', $delta >= 0 ? '+' : '', number_format($delta, 1)),
            'delta' => sprintf('%s%s%% vs previous period', $delta >= 0 ? '+' : '', number_format($delta, 1)),
        ];
    }

    /**
     * @return array{direction:string,label:string,delta:string}
     */
    private static function neutralTrendMeta(): array
    {
        return [
            'direction' => 'up',
            'label' => '0.0%',
            'delta' => 'No previous period comparison',
        ];
    }

    private static function sumOrdersInRange(Collection $orders, $start, $end): float
    {
        return round((float) $orders
            ->filter(fn (Order $order): bool => (self::orderDate($order)?->between($start, $end)) ?? false)
            ->sum('total_amount'), 2);
    }

    private static function sumMerchantTransactionsInRange(Collection $transactions, $start, $end): float
    {
        return self::absoluteSum(
            $transactions
                ->filter(fn (MerchantTransaction $transaction): bool => ($transaction->created_at?->between($start, $end)) ?? false)
                ->pluck('amount')
        );
    }

    private static function sumAdminOwnedSales(Collection $orders): float
    {
        return round((float) $orders->sum(function (Order $order): float {
            return (float) collect($order->items ?? [])
                ->filter(fn ($item): bool => $item->merchant_id === null)
                ->sum(fn ($item): float => (float) $item->line_total);
        }), 2);
    }

    private static function sumAdminOwnedSalesInRange(Collection $orders, $start, $end): float
    {
        return self::sumAdminOwnedSales(
            $orders->filter(fn (Order $order): bool => (self::orderDate($order)?->between($start, $end)) ?? false)->values()
        );
    }

    private static function absoluteSum(Collection $values): float
    {
        return round((float) $values->sum(fn ($value): float => abs((float) $value)), 2);
    }

    private static function countOrdersInRange(Collection $orders, $start, $end): int
    {
        return (int) $orders
            ->filter(fn (Order $order): bool => (self::orderDate($order)?->between($start, $end)) ?? false)
            ->count();
    }

    private static function countCustomersInRange(Collection $orders, $start, $end): int
    {
        return (int) $orders
            ->filter(fn (Order $order): bool => (self::orderDate($order)?->between($start, $end)) ?? false)
            ->pluck('customer_id')
            ->filter()
            ->unique()
            ->count();
    }

    private static function orderDate(Order $order)
    {
        return $order->placed_at ?? $order->created_at;
    }

    private static function dashboardOrderStatusLabel(?string $status): string
    {
        return match ($status) {
            'completed', 'delivered' => 'Completed',
            'pending' => 'Pending',
            'paid', 'processing', 'shipped' => 'Processing',
            default => 'Cancelled',
        };
    }

    private static function dashboardPaymentLabel(?string $method): string
    {
        return match ($method) {
            'aba_qr' => 'ABA QR',
            'acleda' => 'ACLEDA',
            'wing' => 'Wing',
            'card' => 'Card',
            'cash' => 'Cash',
            default => Str::headline((string) $method),
        };
    }

    private static function merchantOrderAnalytics(Collection $orders): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return [
            'label' => 'Customer order trend',
            'options' => [
                ['value' => 'today', 'label' => 'Today'],
                ['value' => 'week', 'label' => 'Week'],
                ['value' => 'month', 'label' => 'Month'],
            ],
            'selected' => 'today',
            'datasets' => [
                'today' => [
                    'total' => (int) self::ordersInRange($orders, $todayStart, $todayEnd)->count(),
                    'bars' => self::buildTodayOrderBars($orders, $todayStart, $todayEnd),
                ],
                'week' => [
                    'total' => (int) self::ordersInRange($orders, $weekStart, $weekEnd)->count(),
                    'bars' => self::buildWeekOrderBars($orders, $weekStart, $weekEnd),
                ],
                'month' => [
                    'total' => (int) self::ordersInRange($orders, $monthStart, $monthEnd)->count(),
                    'bars' => self::buildMonthOrderBars($orders, $monthStart, $monthEnd),
                ],
            ],
        ];
    }

    private static function ordersInRange(Collection $orders, $start, $end): Collection
    {
        return $orders->filter(function (Order $order) use ($start, $end): bool {
            $placedAt = $order->placed_at ?? $order->created_at;

            if (!$placedAt) {
                return false;
            }

            return $placedAt->between($start, $end);
        })->values();
    }

    private static function buildTodayOrderBars(Collection $orders, $start, $end): array
    {
        $filtered = self::ordersInRange($orders, $start, $end);
        $buckets = [
            ['label' => '00-05', 'start' => 0, 'end' => 5],
            ['label' => '06-11', 'start' => 6, 'end' => 11],
            ['label' => '12-17', 'start' => 12, 'end' => 17],
            ['label' => '18-23', 'start' => 18, 'end' => 23],
        ];

        return collect($buckets)->map(function (array $bucket) use ($filtered): array {
            $count = $filtered->filter(function (Order $order) use ($bucket): bool {
                $hour = (int) ($order->placed_at ?? $order->created_at)?->format('G');

                return $hour >= $bucket['start'] && $hour <= $bucket['end'];
            })->count();

            return ['label' => $bucket['label'], 'value' => $count];
        })->all();
    }

    private static function buildWeekOrderBars(Collection $orders, $start, $end): array
    {
        $filtered = self::ordersInRange($orders, $start, $end);
        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        return collect(range(0, 6))->map(function (int $offset) use ($filtered, $start, $labels): array {
            $day = $start->copy()->addDays($offset);
            $count = $filtered->filter(function (Order $order) use ($day): bool {
                $placedAt = $order->placed_at ?? $order->created_at;

                return $placedAt?->isSameDay($day) ?? false;
            })->count();

            return ['label' => $labels[$offset], 'value' => $count];
        })->all();
    }

    private static function buildMonthOrderBars(Collection $orders, $start, $end): array
    {
        $filtered = self::ordersInRange($orders, $start, $end);
        $segments = [
            ['label' => 'Week 1', 'start' => 1, 'end' => 7],
            ['label' => 'Week 2', 'start' => 8, 'end' => 14],
            ['label' => 'Week 3', 'start' => 15, 'end' => 21],
            ['label' => 'Week 4+', 'start' => 22, 'end' => 31],
        ];

        return collect($segments)->map(function (array $segment) use ($filtered): array {
            $count = $filtered->filter(function (Order $order) use ($segment): bool {
                $day = (int) ($order->placed_at ?? $order->created_at)?->format('j');

                return $day >= $segment['start'] && $day <= $segment['end'];
            })->count();

            return ['label' => $segment['label'], 'value' => $count];
        })->all();
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
            'payment-methods' => [
                'page_title' => 'Payment Methods',
                'kicker' => 'Frontend payments',
                'subheadline' => 'Review the payment methods available during storefront checkout and how customers use them.',
            ],
            'payment-fees' => [
                'page_title' => 'Platform Fee',
                'kicker' => 'Commission ledger',
                'subheadline' => 'Review platform fee deductions collected from merchant payouts across all orders.',
            ],
            'qr-codes' => [
                'page_title' => 'QR Codes',
                'kicker' => 'Payment collection',
                'subheadline' => 'Preview admin payment QR codes and collection details used during manual transfer checkout flows.',
            ],
            'wallet' => [
                'page_title' => 'Wallet',
                'kicker' => 'Platform wallet',
                'subheadline' => 'Track total platform fee balance collected from merchants and review the latest fee deductions.',
            ],
            'finance-overview' => [
                'page_title' => 'Finance Overview',
                'kicker' => 'Finance analytics',
                'subheadline' => 'Review balances, payment mix, transaction flow, and order outcomes across the marketplace.',
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
        return DashboardAccess::activeSlugsForScreen($screen);
    }

    /**
     * @param  array<int, string>  $activeSlugs
     * @return array<int, array<string, mixed>>
     */
    private static function menuTree(array $activeSlugs): array
    {
        return DashboardAccess::menuTreeForRole(auth()->user()?->role ?? 'admin', $activeSlugs);
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
                'label' => 'QR Codes',
                'slug' => 'wallet',
                'icon' => 'wallet',
                'path' => '/merchant/qr-codes',
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => false,
                'children' => [],
            ],
            [
                'id' => 'bank-accounts',
                'label' => 'Bank Accounts',
                'slug' => 'bank-accounts',
                'icon' => 'bank-accounts',
                'path' => '/merchant/bank-accounts',
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => false,
                'children' => [],
            ],
            [
                'id' => 'payments',
                'label' => 'Payments',
                'slug' => 'payments',
                'icon' => 'payments',
                'path' => '/merchant/deposits',
                'is_enabled' => true,
                'is_active' => false,
                'is_expanded' => true,
                'children' => [
                    ['id' => 'deposits', 'label' => 'Deposits', 'slug' => 'deposits', 'path' => '/merchant/deposits', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'withdrawals', 'label' => 'Withdrawals', 'slug' => 'withdrawals', 'path' => '/merchant/withdrawals', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
                    ['id' => 'transactions', 'label' => 'Transactions', 'slug' => 'transactions', 'path' => '/merchant/wallet/transactions', 'is_enabled' => true, 'icon' => null, 'is_active' => false, 'is_expanded' => false, 'children' => []],
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

    private static function khrCurrency(float $value): string
    {
        return 'KHR '.number_format($value, 0);
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
