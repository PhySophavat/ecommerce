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
        path: '/admin/orders',
        is_enabled: true,
        children: [
            { label: 'All orders', slug: 'all-orders', path: '/admin/orders', is_enabled: true },
            { label: 'Pending orders', slug: 'pending-orders', path: '/admin/orders/pending', is_enabled: true },
            { label: 'Processing orders', slug: 'processing-orders', path: '/admin/orders/processing', is_enabled: true },
            { label: 'Shipped orders', slug: 'shipped-orders', path: '/admin/orders/shipped', is_enabled: true },
            { label: 'Delivered orders', slug: 'delivered-orders', path: '/admin/orders/delivered', is_enabled: true },
            { label: 'Cancelled orders', slug: 'cancelled-orders', path: '/admin/orders/cancelled', is_enabled: true },
            { label: 'Returns / refunds', slug: 'returns-refunds', path: '/admin/orders/refunded', is_enabled: true },
        ],
    },
    {
        label: 'Customers',
        slug: 'customers',
        icon: 'customers',
        path: '/admin/customers',
        is_enabled: true,
        children: [
            { label: 'All customers', slug: 'all-customers', path: '/admin/customers', is_enabled: true },
            { label: 'Customer details', slug: 'customer-details', path: '/admin/customers/details', is_enabled: true },
            { label: 'Purchase history', slug: 'purchase-history', path: '/admin/customers/purchase-history', is_enabled: true },
            { label: 'Customer messages', slug: 'customer-messages', path: null, is_enabled: false },
        ],
    },
    {
        label: 'Merchant Balance',
        slug: 'merchant-balance',
        icon: 'wallet',
        path: '/admin/merchant-balance',
        is_enabled: true,
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
        label: 'Bank Accounts',
        slug: 'bank-accounts',
        icon: 'bank-accounts',
        path: '/admin/bank-accounts',
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
            { label: 'Payment records', slug: 'payment-records', path: '/admin/payment-records', is_enabled: true },
            { label: 'Payment methods', slug: 'payment-methods', path: '/admin/payment-methods', is_enabled: true },
            { label: 'Deposits', slug: 'deposits', path: '/admin/deposits', is_enabled: true },
            { label: 'Transaction history', slug: 'transaction-history', path: '/admin/merchant-balance', is_enabled: true },
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

function currentRole() {
    return window.__APP_CONTEXT__?.currentUser?.role ?? 'admin';
}

function merchantMenu(screen) {
    const activeSlugs = {
        dashboard: ['dashboard'],
        products: ['products', 'all-products'],
        'add-product': ['products', 'add-product'],
        'merchant-pending-products': ['products', 'pending-products'],
        'merchant-approved-products': ['products', 'approved-products'],
        'merchant-rejected-products': ['products', 'rejected-products'],
        'merchant-orders': ['orders', 'all-orders'],
        'merchant-pending-orders': ['orders', 'pending-orders'],
        'merchant-processing-orders': ['orders', 'processing-orders'],
        'merchant-shipped-orders': ['orders', 'shipped-orders'],
        'merchant-delivered-orders': ['orders', 'delivered-orders'],
        'merchant-cancelled-orders': ['orders', 'cancelled-orders'],
        'merchant-refunded-orders': ['orders', 'returns-refunds'],
        wallet: ['wallet', 'wallet-home'],
        deposit: ['wallet', 'deposit'],
        withdraw: ['wallet', 'withdraw'],
        transactions: ['wallet', 'transactions'],
        'bank-accounts': ['wallet', 'bank-accounts'],
    }[screen] ?? ['dashboard'];

    const items = [
        { slug: 'dashboard', label: 'Dashboard', icon: 'dashboard', path: '/merchant/dashboard', is_enabled: true, children: [] },
        {
            slug: 'products',
            label: 'Products',
            icon: 'products',
            path: '/merchant/products',
            is_enabled: true,
            children: [
                { slug: 'all-products', label: 'All products', path: '/merchant/products', is_enabled: true },
                { slug: 'add-product', label: 'Add product', path: '/merchant/products/create', is_enabled: true },
                { slug: 'pending-products', label: 'Pending products', path: '/merchant/products/pending', is_enabled: true },
                { slug: 'approved-products', label: 'Approved products', path: '/merchant/products/approved', is_enabled: true },
                { slug: 'rejected-products', label: 'Rejected products', path: '/merchant/products/rejected', is_enabled: true },
            ],
        },
        {
            slug: 'orders',
            label: 'Orders',
            icon: 'orders',
            path: '/merchant/orders',
            is_enabled: true,
            children: [
                { slug: 'all-orders', label: 'All orders', path: '/merchant/orders', is_enabled: true },
                { slug: 'pending-orders', label: 'Pending orders', path: '/merchant/orders/pending', is_enabled: true },
                { slug: 'processing-orders', label: 'Processing orders', path: '/merchant/orders/processing', is_enabled: true },
                { slug: 'shipped-orders', label: 'Shipped orders', path: '/merchant/orders/shipped', is_enabled: true },
                { slug: 'delivered-orders', label: 'Delivered orders', path: '/merchant/orders/delivered', is_enabled: true },
                { slug: 'cancelled-orders', label: 'Cancelled orders', path: '/merchant/orders/cancelled', is_enabled: true },
                { slug: 'returns-refunds', label: 'Returns / refunds', path: '/merchant/orders/refunded', is_enabled: true },
            ],
        },
        {
            slug: 'wallet',
            label: 'Wallet',
            icon: 'wallet',
            path: '/merchant/wallet',
            is_enabled: true,
            children: [
                { slug: 'deposit', label: 'Deposit', path: '/merchant/deposits', is_enabled: true },
                { slug: 'withdraw', label: 'Withdraw', path: '/merchant/withdrawals', is_enabled: true },
                { slug: 'transactions', label: 'Transactions', path: '/merchant/wallet/transactions', is_enabled: true },
                { slug: 'bank-accounts', label: 'Bank Accounts', path: '/merchant/bank-accounts', is_enabled: true },
            ],
        },
        { slug: 'logout', label: 'Logout', icon: 'logout', path: null, is_enabled: true, children: [] },
    ];

    return items.map((item) => {
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
            is_expanded: (item.children ?? []).length > 0,
        };
    });
}

function activeSlugsForScreen(screen) {
    return {
        dashboard: ['dashboard'],
        sliders: ['sliders'],
        products: ['products', 'all-products'],
        'add-product': ['products', 'add-product'],
        'featured-products': ['content-management', 'featured-products'],
        orders: ['orders', 'all-orders'],
        'pending-orders': ['orders', 'pending-orders'],
        'processing-orders': ['orders', 'processing-orders'],
        'shipped-orders': ['orders', 'shipped-orders'],
        'delivered-orders': ['orders', 'delivered-orders'],
        'cancelled-orders': ['orders', 'cancelled-orders'],
        'returns-refunds': ['orders', 'returns-refunds'],
        'merchant-orders': ['orders', 'all-orders'],
        'merchant-pending-orders': ['orders', 'pending-orders'],
        'merchant-processing-orders': ['orders', 'processing-orders'],
        'merchant-shipped-orders': ['orders', 'shipped-orders'],
        'merchant-delivered-orders': ['orders', 'delivered-orders'],
        'merchant-cancelled-orders': ['orders', 'cancelled-orders'],
        'merchant-refunded-orders': ['orders', 'returns-refunds'],
        customers: ['customers', 'all-customers'],
        'customer-details': ['customers', 'customer-details'],
        'purchase-history': ['customers', 'purchase-history'],
        users: ['users-admin-management', 'admin-users'],
        merchants: ['users-admin-management', 'merchants'],
        'merchant-balance': ['merchant-balance', 'payments', 'transaction-history'],
        'payment-records': ['payments', 'payment-records'],
        wallet: ['wallet'],
        'payment-methods': ['payments', 'payment-methods'],
        'bank-accounts': ['bank-accounts'],
        'pending-merchants': ['users-admin-management', 'merchants'],
        'merchant-details': ['users-admin-management', 'merchants'],
        'platform-fee-settings': ['settings', 'platform-fee-settings'],
        deposits: ['payments', 'deposits'],
        withdrawals: ['payments', 'withdrawals'],
    }[screen] ?? ['products', 'all-products'];
}

export function buildFallbackMenu(screen) {
    const activeSlugs = activeSlugsForScreen(screen);
    const isMerchant = currentRole() === 'merchant';

    if (isMerchant) {
        return merchantMenu(screen);
    }

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
