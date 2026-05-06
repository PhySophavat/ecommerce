import { createRouter, createWebHashHistory } from 'vue-router';

import AppShell from '../shared/AppShell.vue';
import CartPage from '../pages/CartPage.vue';
import CheckoutPage from '../pages/CheckoutPage.vue';
import HomePage from '../pages/HomePage.vue';
import LoginPage from '../pages/LoginPage.vue';
import OrderDetailPage from '../pages/OrderDetailPage.vue';
import OrderHistoryPage from '../pages/OrderHistoryPage.vue';
import OrderSuccessPage from '../pages/OrderSuccessPage.vue';
import ProductDetailPage from '../pages/ProductDetailPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import RegisterPage from '../pages/RegisterPage.vue';
import ShopPage from '../pages/ShopPage.vue';
import WishlistPage from '../pages/WishlistPage.vue';
import { useStorefrontStore } from '../stores/storefront';

const routes = [
    {
        path: '/',
        component: AppShell,
        children: [
            { path: '', name: 'home', component: HomePage },
            { path: 'shop', name: 'shop', component: ShopPage },
            { path: 'product/:id', name: 'product-detail', component: ProductDetailPage, props: true },
            { path: 'cart', name: 'cart', component: CartPage },
            { path: 'checkout', name: 'checkout', component: CheckoutPage, meta: { requiresAuth: true } },
            { path: 'wishlist', name: 'wishlist', component: WishlistPage },
            { path: 'profile', name: 'profile', component: ProfilePage, meta: { requiresAuth: true } },
            { path: 'orders', name: 'orders', component: OrderHistoryPage, meta: { requiresAuth: true } },
            { path: 'orders/:id', name: 'order-detail', component: OrderDetailPage, props: true, meta: { requiresAuth: true } },
            { path: 'order-success/:id', name: 'order-success', component: OrderSuccessPage, props: true, meta: { requiresAuth: true } },
        ],
    },
    { path: '/login', name: 'login', component: LoginPage, meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { guestOnly: true } },
];

const router = createRouter({
    history: createWebHashHistory(),
    routes,
    scrollBehavior() {
        return { top: 0, behavior: 'smooth' };
    },
});

router.beforeEach(async (to) => {
    const store = useStorefrontStore();

    if (!store.initialized) {
        await store.initialize();
    } else if (store.user === null && (to.meta.requiresAuth || to.meta.guestOnly)) {
        await store.fetchSession();
    }

    if (to.meta.requiresAuth && !store.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && store.isAuthenticated) {
        return { name: 'home' };
    }

    return true;
});

export default router;
