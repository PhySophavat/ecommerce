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
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'All orders', 'slug' => 'all-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Pending orders', 'slug' => 'pending-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Processing orders', 'slug' => 'processing-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Shipped orders', 'slug' => 'shipped-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Delivered orders', 'slug' => 'delivered-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Cancelled orders', 'slug' => 'cancelled-orders', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Returns / refunds', 'slug' => 'returns-refunds', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Customers',
                'slug' => 'customers',
                'icon' => 'customers',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'All customers', 'slug' => 'all-customers', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Customer details', 'slug' => 'customer-details', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Purchase history', 'slug' => 'purchase-history', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Customer messages', 'slug' => 'customer-messages', 'path' => null, 'is_enabled' => false],
                ],
            ],
            [
                'label' => 'Payments',
                'slug' => 'payments',
                'icon' => 'payments',
                'path' => null,
                'is_enabled' => false,
                'children' => [
                    ['label' => 'Payment records', 'slug' => 'payment-records', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Payment methods', 'slug' => 'payment-methods', 'path' => null, 'is_enabled' => false],
                    ['label' => 'Transaction history', 'slug' => 'transaction-history', 'path' => null, 'is_enabled' => false],
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
                'path' => '/admin/products',
                'is_enabled' => true,
                'children' => [
                    ['label' => 'Admin users', 'slug' => 'admin-users', 'path' => '/admin/products', 'is_enabled' => true],
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
                    ['label' => 'Tax settings', 'slug' => 'tax-settings', 'path' => null, 'is_enabled' => false],
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
