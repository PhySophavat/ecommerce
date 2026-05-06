<?php

namespace App\Support;

class AdminMenuCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'dashboard',
                'path' => '/admin/dashboard',
                'is_enabled' => true,
                'children' => [],
            ],
            [
                'label' => 'Products',
                'slug' => 'products',
                'icon' => 'products',
                'path' => '/admin/products',
                'is_enabled' => true,
                'children' => [
                    ['label' => 'All products', 'slug' => 'all-products', 'path' => '/admin/products', 'is_enabled' => true],
                    ['label' => 'Add product', 'slug' => 'add-product', 'path' => '/admin/products/create', 'is_enabled' => true],
                    ['label' => 'Categories', 'slug' => 'categories', 'path' => '/admin/products#categories', 'is_enabled' => true],
                    ['label' => 'Brands', 'slug' => 'brands', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Product reviews', 'slug' => 'product-reviews', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Inventory / stock', 'slug' => 'inventory-stock', 'path' => '/admin/products#inventory', 'is_enabled' => true],
                ],
            ],
            [
                'label' => 'Orders',
                'slug' => 'orders',
                'icon' => 'orders',
                'path' => '/admin/orders',
                'is_enabled' => true,
                'children' => [
                    ['label' => 'All orders', 'slug' => 'all-orders', 'path' => '/admin/orders', 'is_enabled' => true],
                    ['label' => 'Pending orders', 'slug' => 'pending-orders', 'path' => '/admin/orders/pending', 'is_enabled' => true],
                    ['label' => 'Processing orders', 'slug' => 'processing-orders', 'path' => '/admin/orders/processing', 'is_enabled' => true],
                    ['label' => 'Shipped orders', 'slug' => 'shipped-orders', 'path' => '/admin/orders/shipped', 'is_enabled' => true],
                    ['label' => 'Delivered orders', 'slug' => 'delivered-orders', 'path' => '/admin/orders/delivered', 'is_enabled' => true],
                    ['label' => 'Cancelled orders', 'slug' => 'cancelled-orders', 'path' => '/admin/orders/cancelled', 'is_enabled' => true],
                    ['label' => 'Returns / refunds', 'slug' => 'returns-refunds', 'path' => '/admin/orders/refunded', 'is_enabled' => true],
                ],
            ],
            [
                'label' => 'Customers',
                'slug' => 'customers',
                'icon' => 'customers',
                'path' => '/admin/customers',
                'is_enabled' => true,
                'children' => [
                    ['label' => 'All customers', 'slug' => 'all-customers', 'path' => '/admin/customers', 'is_enabled' => true],
                    ['label' => 'Customer details', 'slug' => 'customer-details', 'path' => '/admin/customers/details', 'is_enabled' => true],
                    ['label' => 'Purchase history', 'slug' => 'purchase-history', 'path' => '/admin/customers/purchase-history', 'is_enabled' => true],
                    ['label' => 'Customer messages', 'slug' => 'customer-messages', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Merchant Balance',
                'slug' => 'merchant-balance',
                'icon' => 'wallet',
                'path' => '/admin/merchant-balance',
                'is_enabled' => true,
                'children' => [],
            ],
            [
                'label' => 'Wallet',
                'slug' => 'wallet',
                'icon' => 'wallet',
                'path' => '/admin/wallet',
                'is_enabled' => true,
                'children' => [],
            ],
            [
                'label' => 'Bank Accounts',
                'slug' => 'bank-accounts',
                'icon' => 'bank-accounts',
                'path' => '/admin/bank-accounts',
                'is_enabled' => true,
                'children' => [],
            ],
            [
                'label' => 'Payments',
                'slug' => 'payments',
                'icon' => 'payments',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Payment records', 'slug' => 'payment-records', 'path' => '/admin/payment-records', 'is_enabled' => true],
                    ['label' => 'Payment methods', 'slug' => 'payment-methods', 'path' => '/admin/payment-methods', 'is_enabled' => true],
                    ['label' => 'Deposits', 'slug' => 'deposits', 'path' => '/admin/deposits', 'is_enabled' => true],
                    ['label' => 'Transaction history', 'slug' => 'transaction-history', 'path' => '/admin/merchant-balance', 'is_enabled' => true],
                    ['label' => 'Withdrawals', 'slug' => 'withdrawals', 'path' => '/admin/withdrawals', 'is_enabled' => true],
                ],
            ],
            [
                'label' => 'Promotions',
                'slug' => 'promotions',
                'icon' => 'promotions',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Discount codes', 'slug' => 'discount-codes', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Coupons', 'slug' => 'coupons', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Flash sales', 'slug' => 'flash-sales', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Banners', 'slug' => 'banners', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Reports / Analytics',
                'slug' => 'reports-analytics',
                'icon' => 'reports',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Sales reports', 'slug' => 'sales-reports', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Order reports', 'slug' => 'order-reports', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Product performance', 'slug' => 'product-performance', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Customer reports', 'slug' => 'customer-reports', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Users / Admin Management',
                'slug' => 'users-admin-management',
                'icon' => 'users',
                'path' => '/admin/users',
                'is_enabled' => true,
                'children' => [
                    ['label' => 'Admin users', 'slug' => 'admin-users', 'path' => '/admin/users', 'is_enabled' => true],
                    ['label' => 'Merchants', 'slug' => 'merchants', 'path' => '/admin/merchants', 'is_enabled' => true],
                    ['label' => 'Roles and permissions', 'slug' => 'roles-and-permissions', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Content Management',
                'slug' => 'content-management',
                'icon' => 'content',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Homepage banners', 'slug' => 'homepage-banners', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Slides', 'slug' => 'sliders', 'path' => '/admin/sliders', 'is_enabled' => true],
                    ['label' => 'Featured products', 'slug' => 'featured-products', 'path' => '/admin/products/featured', 'is_enabled' => true],
                    ['label' => 'Blog / news', 'slug' => 'blog-news', 'path' => null, 'is_enabled' => false],
                    ['label' => 'FAQ', 'slug' => 'faq', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Settings',
                'slug' => 'settings',
                'icon' => 'settings',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Store settings', 'slug' => 'store-settings', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Shipping settings', 'slug' => 'shipping-settings', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Payment settings', 'slug' => 'payment-settings', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Platform Fee Settings', 'slug' => 'platform-fee-settings', 'path' => '/admin/settings/platform-fee', 'is_enabled' => true],
                    ['label' => 'Email settings', 'slug' => 'email-settings', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Notifications',
                'slug' => 'notifications',
                'icon' => 'notifications',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Customer notifications', 'slug' => 'customer-notifications', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Order alerts', 'slug' => 'order-alerts', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Stock alerts', 'slug' => 'stock-alerts', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Logout',
                'slug' => 'logout',
                'icon' => 'logout',
                'path' => null,
                'is_enabled' => false,
                'children' => [],
            ],
        ];
    }
}
