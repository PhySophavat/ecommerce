<?php

namespace App\Support;

class DashboardAccess
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function permissionsByRole(): array
    {
        return [
            'admin' => [
                'dashboard.view',
                'products.view',
                'products.manage',
                'products.approve',
                'orders.view',
                'orders.manage',
                'customers.manage',
                'merchants.manage',
                'wallet.view',
                'wallet.manage',
                'payments.view',
                'withdrawals.review',
                'reports.view',
                'settings.manage',
                'content.manage',
                'users.manage',
            ],
            'merchant' => [
                'dashboard.view',
                'products.view',
                'products.manage',
                'orders.view',
                'orders.manage',
                'wallet.view',
                'wallet.manage',
                'payments.view',
                'withdrawals.request',
                'reports.view',
                'shop-settings.manage',
            ],
        ];
    }

    public static function hasPermission(?string $role, ?string $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        return in_array($permission, self::permissionsByRole()[$role ?? ''] ?? [], true);
    }

    /**
     * @return array<int, string>
     */
    public static function activeSlugsForScreen(string $screen): array
    {
        return [
            'dashboard' => ['dashboard'],
            'sliders' => ['content-management', 'sliders'],
            'products' => ['products', 'all-products'],
            'add-product' => ['products', 'add-product'],
            'featured-products' => ['content-management', 'featured-products'],
            'merchant-pending-products' => ['products', 'pending-products'],
            'merchant-approved-products' => ['products', 'approved-products'],
            'merchant-rejected-products' => ['products', 'rejected-products'],
            'orders' => ['orders', 'all-orders'],
            'pending-orders' => ['orders', 'pending-orders'],
            'processing-orders' => ['orders', 'processing-orders'],
            'shipped-orders' => ['orders', 'shipped-orders'],
            'delivered-orders' => ['orders', 'delivered-orders'],
            'cancelled-orders' => ['orders', 'cancelled-orders'],
            'returns-refunds' => ['orders', 'returns-refunds'],
            'merchant-orders' => ['orders', 'all-orders'],
            'merchant-pending-orders' => ['orders', 'pending-orders'],
            'merchant-processing-orders' => ['orders', 'processing-orders'],
            'merchant-shipped-orders' => ['orders', 'shipped-orders'],
            'merchant-delivered-orders' => ['orders', 'delivered-orders'],
            'merchant-cancelled-orders' => ['orders', 'cancelled-orders'],
            'merchant-refunded-orders' => ['orders', 'returns-refunds'],
            'customers' => ['customers', 'all-customers'],
            'customer-details' => ['customers', 'customer-details'],
            'purchase-history' => ['customers', 'purchase-history'],
            'users' => ['users-admin-management', 'admin-users'],
            'merchants' => ['users-admin-management', 'merchants'],
            'pending-merchants' => ['users-admin-management', 'merchants'],
            'merchant-details' => ['users-admin-management', 'merchants'],
            'finance-overview' => ['finance-overview'],
            'merchant-balance' => ['merchant-balance'],
            'qr-codes' => ['qr-codes'],
            'wallet' => ['wallet'],
            'payment-records' => ['payments', 'payment-records'],
            'payment-methods' => ['payments', 'payment-methods'],
            'payment-fees' => ['payments', 'payment-fees'],
            'deposits' => ['payments', 'deposits'],
            'withdrawals' => ['payments', 'withdrawals'],
            'deposit' => ['payments', 'deposits'],
            'withdraw' => ['payments', 'withdrawals'],
            'transactions' => ['payments', 'transactions'],
            'bank-accounts' => ['bank-accounts'],
            'platform-fee-settings' => ['settings', 'platform-fee-settings'],
            'merchant-status' => ['shop-settings', 'profile-user'],
        ][$screen] ?? ['dashboard'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function menuTreeForRole(string $role, array $activeSlugs): array
    {
        return collect(self::sharedMenu())
            ->map(fn (array $item): ?array => self::resolveMenuItem($item, $role, $activeSlugs))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function sharedMenu(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'dashboard',
                'permission' => 'dashboard.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/dashboard', 'merchant' => '/merchant/dashboard'],
                'children' => [],
            ],
            [
                'label' => 'Products',
                'slug' => 'products',
                'icon' => 'products',
                'permission' => 'products.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/products', 'merchant' => '/merchant/products'],
                'children' => [
                    [
                        'label' => 'All products',
                        'slug' => 'all-products',
                        'permission' => 'products.view',
                        'roles' => ['admin', 'merchant'],
                        'paths' => ['admin' => '/admin/products', 'merchant' => '/merchant/products'],
                    ],
                    [
                        'label' => 'Add product',
                        'slug' => 'add-product',
                        'permission' => 'products.manage',
                        'roles' => ['admin', 'merchant'],
                        'paths' => ['admin' => '/admin/products/create', 'merchant' => '/merchant/products/create'],
                    ],
                    [
                        'label' => 'Pending products',
                        'slug' => 'pending-products',
                        'permission' => 'products.view',
                        'roles' => ['merchant'],
                        'paths' => ['merchant' => '/merchant/products/pending'],
                    ],
                    [
                        'label' => 'Approved products',
                        'slug' => 'approved-products',
                        'permission' => 'products.view',
                        'roles' => ['merchant'],
                        'paths' => ['merchant' => '/merchant/products/approved'],
                    ],
                    [
                        'label' => 'Rejected products',
                        'slug' => 'rejected-products',
                        'permission' => 'products.view',
                        'roles' => ['merchant'],
                        'paths' => ['merchant' => '/merchant/products/rejected'],
                    ],
                    [
                        'label' => 'Categories',
                        'slug' => 'categories',
                        'permission' => 'products.manage',
                        'roles' => ['admin'],
                        'paths' => ['admin' => '/admin/products#categories'],
                    ],
                    [
                        'label' => 'Inventory / stock',
                        'slug' => 'inventory-stock',
                        'permission' => 'products.manage',
                        'roles' => ['admin'],
                        'paths' => ['admin' => '/admin/products#inventory'],
                    ],
                ],
            ],
            [
                'label' => 'Orders',
                'slug' => 'orders',
                'icon' => 'orders',
                'permission' => 'orders.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/orders', 'merchant' => '/merchant/orders'],
                'children' => [
                    ['label' => 'All orders', 'slug' => 'all-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders', 'merchant' => '/merchant/orders']],
                    ['label' => 'Pending orders', 'slug' => 'pending-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/pending', 'merchant' => '/merchant/orders/pending']],
                    ['label' => 'Processing orders', 'slug' => 'processing-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/processing', 'merchant' => '/merchant/orders/processing']],
                    ['label' => 'Shipped orders', 'slug' => 'shipped-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/shipped', 'merchant' => '/merchant/orders/shipped']],
                    ['label' => 'Delivered orders', 'slug' => 'delivered-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/delivered', 'merchant' => '/merchant/orders/delivered']],
                    ['label' => 'Cancelled orders', 'slug' => 'cancelled-orders', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/cancelled', 'merchant' => '/merchant/orders/cancelled']],
                    ['label' => 'Returns / refunds', 'slug' => 'returns-refunds', 'permission' => 'orders.view', 'roles' => ['admin', 'merchant'], 'paths' => ['admin' => '/admin/orders/refunded', 'merchant' => '/merchant/orders/refunded']],
                ],
            ],
            [
                'label' => 'Customers',
                'slug' => 'customers',
                'icon' => 'customers',
                'permission' => 'customers.manage',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/customers'],
                'children' => [
                    ['label' => 'All customers', 'slug' => 'all-customers', 'permission' => 'customers.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/customers']],
                    ['label' => 'Customer details', 'slug' => 'customer-details', 'permission' => 'customers.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/customers/details']],
                    ['label' => 'Purchase history', 'slug' => 'purchase-history', 'permission' => 'customers.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/customers/purchase-history']],
                ],
            ],
            [
                'label' => 'Finance Overview',
                'slug' => 'finance-overview',
                'icon' => 'wallet',
                'permission' => 'reports.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/finance-overview', 'merchant' => '/merchant/finance-overview'],
                'children' => [],
            ],
            [
                'label' => 'Merchant Balance',
                'slug' => 'merchant-balance',
                'icon' => 'wallet',
                'permission' => 'wallet.view',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/merchant-balance'],
                'children' => [],
            ],
            [
                'label' => 'QR Codes',
                'slug' => 'qr-codes',
                'icon' => 'qr-codes',
                'permission' => 'wallet.view',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/qr-codes'],
                'children' => [],
            ],
            [
                'label' => 'Wallet',
                'labels' => ['admin' => 'Wallet', 'merchant' => 'QR Codes'],
                'slug' => 'wallet',
                'icon' => 'wallet',
                'permission' => 'wallet.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/wallet', 'merchant' => '/merchant/qr-codes'],
                'children' => [],
            ],
            [
                'label' => 'Bank Accounts',
                'slug' => 'bank-accounts',
                'icon' => 'bank-accounts',
                'permission' => 'wallet.manage',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/bank-accounts', 'merchant' => '/merchant/bank-accounts'],
                'children' => [],
            ],
            [
                'label' => 'Payments',
                'slug' => 'payments',
                'icon' => 'payments',
                'permission' => 'payments.view',
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => '/admin/payment-records', 'merchant' => '/merchant/finance-overview'],
                'children' => [
                    ['label' => 'Payment records', 'slug' => 'payment-records', 'permission' => 'payments.view', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/payment-records']],
                    ['label' => 'Payment methods', 'slug' => 'payment-methods', 'permission' => 'payments.view', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/payment-methods']],
                    ['label' => 'Platform Fee', 'slug' => 'payment-fees', 'permission' => 'payments.view', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/payment-fees']],
                    ['label' => 'Deposits', 'slug' => 'deposits', 'permission' => 'payments.view', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/deposits']],
                    ['label' => 'Withdrawals', 'slug' => 'withdrawals', 'permission' => 'withdrawals.review', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/withdrawals']],
                    ['label' => 'Deposits', 'slug' => 'deposits', 'permission' => 'wallet.manage', 'roles' => ['merchant'], 'paths' => ['merchant' => '/merchant/deposits']],
                    ['label' => 'Withdrawals', 'slug' => 'withdrawals', 'permission' => 'withdrawals.request', 'roles' => ['merchant'], 'paths' => ['merchant' => '/merchant/withdrawals']],
                    ['label' => 'Transactions', 'slug' => 'transactions', 'permission' => 'wallet.view', 'roles' => ['merchant'], 'paths' => ['merchant' => '/merchant/wallet/transactions']],
                ],
            ],
            [
                'label' => 'Shop Settings',
                'slug' => 'shop-settings',
                'icon' => 'settings',
                'permission' => 'shop-settings.manage',
                'roles' => ['merchant'],
                'paths' => ['merchant' => '/merchant/status'],
                'children' => [
                    ['label' => 'Profile', 'slug' => 'profile-user', 'permission' => 'shop-settings.manage', 'roles' => ['merchant'], 'paths' => ['merchant' => '/merchant/status']],
                ],
            ],
            [
                'label' => 'Users / Admin Management',
                'slug' => 'users-admin-management',
                'icon' => 'users',
                'permission' => 'users.manage',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/users'],
                'children' => [
                    ['label' => 'Admin users', 'slug' => 'admin-users', 'permission' => 'users.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/users']],
                    ['label' => 'Merchants', 'slug' => 'merchants', 'permission' => 'merchants.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/merchants']],
                ],
            ],
            [
                'label' => 'Content Management',
                'slug' => 'content-management',
                'icon' => 'content',
                'permission' => 'content.manage',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/sliders'],
                'children' => [
                    ['label' => 'Slides', 'slug' => 'sliders', 'permission' => 'content.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/sliders']],
                    ['label' => 'Featured products', 'slug' => 'featured-products', 'permission' => 'content.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/products/featured']],
                ],
            ],
            [
                'label' => 'Settings',
                'slug' => 'settings',
                'icon' => 'settings',
                'permission' => 'settings.manage',
                'roles' => ['admin'],
                'paths' => ['admin' => '/admin/settings/platform-fee'],
                'children' => [
                    ['label' => 'Platform Fee Settings', 'slug' => 'platform-fee-settings', 'permission' => 'settings.manage', 'roles' => ['admin'], 'paths' => ['admin' => '/admin/settings/platform-fee']],
                ],
            ],
            [
                'label' => 'Logout',
                'slug' => 'logout',
                'icon' => 'logout',
                'permission' => null,
                'roles' => ['admin', 'merchant'],
                'paths' => ['admin' => null, 'merchant' => null],
                'children' => [],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $activeSlugs
     * @return array<string, mixed>|null
     */
    private static function resolveMenuItem(array $item, string $role, array $activeSlugs): ?array
    {
        if (!in_array($role, $item['roles'] ?? [], true)) {
            return null;
        }

        if (!self::hasPermission($role, $item['permission'] ?? null)) {
            return null;
        }

        $children = collect($item['children'] ?? [])
            ->map(fn (array $child): ?array => self::resolveMenuItem($child, $role, $activeSlugs))
            ->filter()
            ->values()
            ->all();

        $childIsActive = collect($children)->contains(fn (array $child): bool => $child['is_active']);
        $slug = (string) $item['slug'];
        $path = ($item['paths'][$role] ?? null);
        $isActive = in_array($slug, $activeSlugs, true) || $childIsActive;

        return [
            'id' => $slug,
            'label' => $item['labels'][$role] ?? $item['label'],
            'slug' => $slug,
            'icon' => $item['icon'] ?? null,
            'path' => $path,
            'permission' => $item['permission'] ?? null,
            'is_enabled' => $path !== null || !empty($children) || $slug === 'logout',
            'is_active' => $isActive,
            'is_expanded' => $childIsActive || self::shouldAlwaysExpandMenu($slug),
            'children' => $children,
        ];
    }

    private static function shouldAlwaysExpandMenu(string $slug): bool
    {
        return in_array($slug, ['users-admin-management', 'payments', 'wallet', 'content-management'], true);
    }
}
