const MENU_ITEMS = [
    {
        label: 'Dashboard',
        slug: 'dashboard',
        icon: 'dashboard',
        path: '/admin/dashboard',
        is_enabled: true,
        children: [],
    },
    {
        label: 'Products',
        slug: 'products',
        icon: 'products',
        path: '/admin/products',
        is_enabled: true,
        children: [
            { label: 'All products', slug: 'all-products', path: '/admin/products', is_enabled: true },
            { label: 'Add product', slug: 'add-product', path: '/admin/products/create', is_enabled: true },
            { label: 'Categories', slug: 'categories', path: '/admin/products#categories', is_enabled: true },
            { label: 'Brands', slug: 'brands', path: null, is_enabled: false },
            { label: 'Product reviews', slug: 'product-reviews', path: null, is_enabled: false },
            { label: 'Inventory / stock', slug: 'inventory-stock', path: '/admin/products#inventory', is_enabled: true },
        ],
    },
    {
        label: 'Orders',
        slug: 'orders',
        icon: 'orders',
        path: null,
        is_enabled: false,
        children: [],
    },
    {
        label: 'Customers',
        slug: 'customers',
        icon: 'customers',
        path: null,
        is_enabled: false,
        children: [],
    },
    {
        label: 'Wallet',
        slug: 'wallet',
        icon: 'wallet',
        path: '/admin/wallet',
        is_enabled: true,
        children: [],
    },
    {
        label: 'Payments',
        slug: 'payments',
        icon: 'payments',
        path: null,
        is_enabled: false,
        children: [
            { label: 'Deposits', slug: 'deposits', path: '/admin/deposits', is_enabled: true },
            { label: 'Withdrawals', slug: 'withdrawals', path: '/admin/withdrawals', is_enabled: true },
        ],
    },
    {
        label: 'Promotions',
        slug: 'promotions',
        icon: 'promotions',
        path: null,
        is_enabled: false,
        children: [],
    },
    {
        label: 'Reports / Analytics',
        slug: 'reports-analytics',
        icon: 'reports',
        path: null,
        is_enabled: false,
        children: [],
    },
    {
        label: 'Users / Admin Management',
        slug: 'users-admin-management',
        icon: 'users',
        path: '/admin/users',
        is_enabled: true,
        children: [
            { label: 'Admin users', slug: 'admin-users', path: '/admin/users', is_enabled: true },
            { label: 'Merchants', slug: 'merchants', path: '/admin/merchants', is_enabled: true },
            { label: 'Roles and permissions', slug: 'roles-and-permissions', path: null, is_enabled: false },
        ],
    },
    {
        label: 'Content Management',
        slug: 'content-management',
        icon: 'content',
        path: null,
        is_enabled: false,
        children: [
            { label: 'Slides', slug: 'sliders', path: '/admin/sliders', is_enabled: true },
            { label: 'Featured products', slug: 'featured-products', path: '/admin/products/featured', is_enabled: true },
        ],
    },
    {
        label: 'Settings',
        slug: 'settings',
        icon: 'settings',
        path: null,
        is_enabled: false,
        children: [
            { label: 'Store settings', slug: 'store-settings', path: null, is_enabled: false },
            { label: 'Shipping settings', slug: 'shipping-settings', path: null, is_enabled: false },
            { label: 'Payment settings', slug: 'payment-settings', path: null, is_enabled: false },
            { label: 'Platform Fee Settings', slug: 'platform-fee-settings', path: '/admin/settings/platform-fee', is_enabled: true },
            { label: 'Email settings', slug: 'email-settings', path: null, is_enabled: false },
        ],
    },
    {
        label: 'Notifications',
        slug: 'notifications',
        icon: 'notifications',
        path: null,
        is_enabled: false,
        children: [],
    },
    {
        label: 'Logout',
        slug: 'logout',
        icon: 'logout',
        path: null,
        is_enabled: false,
        children: [],
    },
];

function activeSlugsForScreen(screen) {
    return {
        dashboard: ['dashboard'],
        sliders: ['sliders'],
        products: ['products', 'all-products'],
        'add-product': ['products', 'add-product'],
        'featured-products': ['content-management', 'featured-products'],
        users: ['users-admin-management', 'admin-users'],
        merchants: ['users-admin-management', 'merchants'],
        wallet: ['wallet'],
        'pending-merchants': ['users-admin-management', 'merchants'],
        'merchant-details': ['users-admin-management', 'merchants'],
        'platform-fee-settings': ['settings', 'platform-fee-settings'],
        deposits: ['payments', 'deposits'],
        withdrawals: ['payments', 'withdrawals'],
    }[screen] ?? ['products', 'all-products'];
}

export function buildFallbackMenu(screen) {
    const activeSlugs = activeSlugsForScreen(screen);

    return MENU_ITEMS.map((item) => {
        const children = (item.children ?? []).map((child) => ({
            ...child,
            children: [],
            is_active: activeSlugs.includes(child.slug),
            is_expanded: false,
        }));
        const childIsActive = children.some((child) => child.is_active);

        return {
            ...item,
            children,
            is_active: activeSlugs.includes(item.slug) || childIsActive,
            is_expanded: ['users-admin-management', 'payments'].includes(item.slug) || childIsActive,
        };
    });
}
