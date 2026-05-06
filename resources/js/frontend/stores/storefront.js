import { defineStore } from 'pinia';

const STORAGE_CART = 'frontend-cart-v3';
const STORAGE_WISHLIST = 'frontend-wishlist-v3';

function parseStorage(key, fallback = []) {
    try {
        const raw = window.localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
}

function money(value) {
    return `$${Number(value || 0).toFixed(2)}`;
}

function createLineId() {
    return `line-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

function isApprovedProduct(product) {
    return Boolean(product?.is_orderable);
}

async function ensureCsrfToken(force = false) {
    if (!force && window.__storefrontCsrfReady) {
        return;
    }

    const response = await window.axios.get('/api/frontend/csrf-token');
    const csrfToken = response.data?.csrf_token;

    if (csrfToken) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

        const tokenMeta = document.head.querySelector('meta[name="csrf-token"]');
        if (tokenMeta) {
            tokenMeta.setAttribute('content', csrfToken);
        }
    }

    window.__storefrontCsrfReady = true;
}

export const useStorefrontStore = defineStore('storefront', {
    state: () => ({
        initialized: false,
        loading: false,
        authLoading: false,
        orderLoading: false,
        meta: null,
        categories: [],
        slides: [],
        products: [],
        cart: parseStorage(STORAGE_CART, []),
        wishlist: parseStorage(STORAGE_WISHLIST, []),
        orders: [],
        latestOrder: null,
        user: null,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.user),
        featuredProducts: (state) => state.products.filter((product) => product.is_featured).slice(0, 8),
        newArrivals: (state) => [...state.products].slice(0, 8),
        merchants: (state) => [...new Set(state.products.map((product) => product.merchant_name).filter(Boolean))],
        cartItems(state) {
            return state.cart
                .map((entry) => {
                    const product = state.products.find((item) => item.id === entry.product_id);

                    if (!product || !isApprovedProduct(product)) {
                        return null;
                    }

                    const quantity = Math.max(1, Math.min(entry.quantity, product.inventory || entry.quantity));
                    const unitPrice = Number.parseFloat(product.price_value || '0');

                    return {
                        line_id: entry.line_id,
                        ...product,
                        quantity,
                        variant: entry.variant || 'Default',
                        line_total: unitPrice * quantity,
                    };
                })
                .filter(Boolean);
        },
        cartCount() {
            return this.cartItems.reduce((sum, item) => sum + item.quantity, 0);
        },
        subtotal() {
            return this.cartItems.reduce((sum, item) => sum + item.line_total, 0);
        },
        shippingFee() {
            return 0;
        },
        taxAmount() {
            return 0;
        },
        totalAmount() {
            return this.subtotal + this.shippingFee + this.taxAmount;
        },
        wishlistProducts(state) {
            return state.products.filter((product) => state.wishlist.includes(product.id));
        },
        profile(state) {
            return state.user ?? {
                name: '',
                email: '',
                phone: '',
            };
        },
    },

    actions: {
        async initialize(force = false) {
            if (this.initialized && !force) {
                return;
            }

            await Promise.all([
                this.fetchStorefront(),
                this.fetchSession(),
            ]);

            if (this.isAuthenticated) {
                await this.fetchOrders();
            }

            this.initialized = true;
        },

        async fetchStorefront() {
            this.loading = true;

            try {
                const response = await window.axios.get('/api/frontend/home');
                const data = response.data;

                this.meta = data.meta ?? {};
                this.categories = data.categories ?? [];
                this.slides = data.slides ?? [];
                this.products = (data.products?.items ?? []).map((product, index) => ({
                    ...product,
                    rating_value: Number(product.rating || 4.8),
                    reviews_count: Number(product.reviews_count || (18 + index)),
                    color_options: product.color_options ?? ['Rose', 'Ivory', 'Slate'],
                    size_options: product.size_options ?? ['S', 'M', 'L'],
                    variant_options: product.variant_options ?? ['Standard', 'Premium', 'Gift ready'],
                    image_url: product.image_url ?? null,
                }));

                this.normalizeCart();
            } finally {
                this.loading = false;
            }
        },

        async fetchSession() {
            this.authLoading = true;

            try {
                const response = await window.axios.get('/api/frontend/session');
                this.user = response.data?.user ?? null;
            } finally {
                this.authLoading = false;
            }
        },

        async login(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/frontend/login', payload);
            this.user = response.data.user;
            await this.fetchOrders();
            return response.data;
        },

        async register(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/frontend/register', payload);
            this.user = response.data.user;
            this.orders = [];
            return response.data;
        },

        async logout() {
            await ensureCsrfToken(true);
            await window.axios.post('/api/frontend/logout');
            this.user = null;
            this.orders = [];
            this.latestOrder = null;
        },

        async updateProfile(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.put('/api/frontend/profile', payload);
            this.user = response.data.user;
            return response.data;
        },

        async fetchOrders() {
            if (!this.isAuthenticated) {
                this.orders = [];
                return;
            }

            this.orderLoading = true;

            try {
                const response = await window.axios.get('/api/frontend/orders');
                this.orders = response.data.orders ?? [];
            } finally {
                this.orderLoading = false;
            }
        },

        async fetchOrder(orderId) {
            const response = await window.axios.get(`/api/frontend/orders/${orderId}`);
            return response.data.order;
        },

        productById(id) {
            return this.products.find((product) => String(product.id) === String(id)) || null;
        },

        relatedProducts(productId) {
            const current = this.productById(productId);

            if (!current) {
                return [];
            }

            return this.products
                .filter((product) => product.id !== current.id && product.category_slug === current.category_slug)
                .slice(0, 4);
        },

        addToCart(productId, quantity = 1, variant = 'Default') {
            const product = this.productById(productId);

            if (!product || !isApprovedProduct(product)) {
                return false;
            }

            const nextQuantity = Math.max(1, quantity);
            this.cart.push({
                line_id: createLineId(),
                product_id: productId,
                quantity: Math.min(nextQuantity, product.inventory || nextQuantity),
                variant,
            });

            this.persistCart();
            return true;
        },

        updateCartItem(lineId, quantity) {
            const item = this.cart.find((entry) => entry.line_id === lineId);

            if (!item) {
                return;
            }

            if (quantity <= 0) {
                this.removeFromCart(lineId);
                return;
            }

            const product = this.productById(item.product_id);
            item.quantity = Math.min(quantity, product?.inventory || quantity);
            this.persistCart();
        },

        removeFromCart(lineId) {
            this.cart = this.cart.filter((entry) => entry.line_id !== lineId);
            this.persistCart();
        },

        clearCart() {
            this.cart = [];
            this.persistCart();
        },

        toggleWishlist(productId) {
            if (this.wishlist.includes(productId)) {
                this.wishlist = this.wishlist.filter((id) => id !== productId);
            } else {
                this.wishlist.push(productId);
            }

            window.localStorage.setItem(STORAGE_WISHLIST, JSON.stringify(this.wishlist));
        },

        normalizeCart() {
            this.cart = this.cart
                .map((entry) => {
                    const product = this.productById(entry.product_id);

                    if (!product || !isApprovedProduct(product) || product.inventory < 1) {
                        return null;
                    }

                    return {
                        line_id: entry.line_id || createLineId(),
                        ...entry,
                        quantity: Math.min(Math.max(entry.quantity, 1), product.inventory),
                    };
                })
                .filter(Boolean);

            this.persistCart();
        },

        persistCart() {
            window.localStorage.setItem(STORAGE_CART, JSON.stringify(this.cart));
        },

        async checkout(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/frontend/checkout', {
                ...payload,
                items: this.cartItems.map((item) => ({
                    product_id: item.id,
                    quantity: item.quantity,
                })),
            });

            this.latestOrder = response.data.order;
            this.clearCart();
            await Promise.all([
                this.fetchStorefront(),
                this.fetchOrders(),
            ]);

            return response.data;
        },

        orderSummaryLines() {
            return {
                subtotal: money(this.subtotal),
                shipping: money(this.shippingFee),
                tax: money(this.taxAmount),
                total: money(this.totalAmount),
            };
        },
    },
});
