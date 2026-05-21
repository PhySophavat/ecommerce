import { defineStore } from 'pinia';
import { clearCustomerAuth, getCustomerToken, getCustomerUser, setCustomerAuth } from '../../shared/auth';

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

function variantKey(attributes = []) {
    return attributes
        .map((attribute) => `${attribute.name}:${attribute.value}`)
        .join('|');
}

function normalizeProduct(product, index) {
    const variantGroups = Array.isArray(product.variant_groups)
        ? product.variant_groups
            .map((group) => ({
                name: group.name ?? '',
                options: Array.isArray(group.options) ? group.options.filter(Boolean) : [],
            }))
            .filter((group) => group.name && group.options.length)
        : [];
    const variants = Array.isArray(product.variants)
        ? product.variants.map((variant) => {
            const attributes = Array.isArray(variant.attributes)
                ? variant.attributes
                    .map((attribute) => ({
                        name: attribute.name ?? '',
                        value: attribute.value ?? '',
                    }))
                    .filter((attribute) => attribute.name && attribute.value)
                : [];

            return {
                ...variant,
                attributes,
                key: variantKey(attributes),
                price_value: String(variant.price_value ?? product.price_value ?? '0'),
                stock: Number(variant.stock ?? 0),
                image_url: variant.image_url ?? null,
            };
        })
        : [];

    return {
        ...product,
        rating_value: Number(product.rating || 4.8),
        reviews_count: Number(product.reviews_count || (18 + index)),
        variant_groups: variantGroups,
        variants,
        color_options: Array.isArray(product.color_options) ? product.color_options : [],
        size_options: Array.isArray(product.size_options) ? product.size_options : [],
        variant_options: Array.isArray(product.variant_options) ? product.variant_options : [],
        image_url: product.image_url ?? null,
        image_urls: Array.isArray(product.image_urls) && product.image_urls.length
            ? product.image_urls.filter(Boolean)
            : [product.image_url].filter(Boolean),
    };
}

function defaultVariantForProduct(product) {
    return Array.isArray(product?.variants) && product.variants.length
        ? product.variants[0]
        : null;
}

function findVariantById(product, variantId) {
    if (!variantId) {
        return null;
    }

    return (product?.variants ?? []).find((variant) => String(variant.id) === String(variantId)) ?? null;
}

function resolveCartVariant(product, selection = null) {
    if (!product) {
        return null;
    }

    if (selection && typeof selection === 'object') {
        if (selection.variant_id) {
            return findVariantById(product, selection.variant_id);
        }

        if (selection.variant_key) {
            return (product.variants ?? []).find((variant) => variant.key === selection.variant_key) ?? null;
        }
    }

    return defaultVariantForProduct(product);
}

function stockLimitFor(product, variant, fallbackQuantity = 1) {
    if (variant) {
        return Number(variant.stock ?? 0);
    }

    if (Number.isFinite(Number(product?.inventory))) {
        return Number(product.inventory);
    }

    return fallbackQuantity;
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
        user: getCustomerUser(),
    }),

    getters: {
        isAuthenticated: (state) => Boolean(getCustomerToken() && state.user),
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

                    const variant = resolveCartVariant(product, entry);
                    const stockLimit = stockLimitFor(product, variant, entry.quantity);

                    if (stockLimit < 1) {
                        return null;
                    }

                    const quantity = Math.max(1, Math.min(entry.quantity, stockLimit));
                    const unitPrice = Number.parseFloat(variant?.price_value || product.price_value || '0');

                    return {
                        line_id: entry.line_id,
                        ...product,
                        image_url: variant?.image_url || product.image_url,
                        quantity,
                        variant: variant?.label || entry.variant || 'Default',
                        variant_id: variant?.id ?? entry.variant_id ?? null,
                        price: money(unitPrice),
                        price_value: unitPrice.toFixed(2),
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
                this.products = (data.products?.items ?? []).map((product, index) => normalizeProduct(product, index));

                this.normalizeCart();
            } finally {
                this.loading = false;
            }
        },

        async fetchSession() {
            this.authLoading = true;

            try {
                const currentUser = window.__APP_CONTEXT__?.currentUser ?? null;

                if (!getCustomerToken() && currentUser?.role !== 'customer') {
                    this.user = null;
                    return;
                }

                const response = await window.axios.get('/api/frontend/session');
                this.user = response.data?.user ?? null;

                if (response.data?.token && this.user) {
                    setCustomerAuth(response.data.token, this.user);
                }

                if (!this.user) {
                    clearCustomerAuth();
                }
            } finally {
                this.authLoading = false;
            }
        },

        async login(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/frontend/login', payload);
            setCustomerAuth(response.data.token, response.data.user);
            this.user = response.data.user;
            await this.fetchOrders();
            return response.data;
        },

        async register(payload) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/frontend/register', payload);
            setCustomerAuth(response.data.token, response.data.user);
            this.user = response.data.user;
            this.orders = [];
            return response.data;
        },

        async logout() {
            await ensureCsrfToken(true);
            await window.axios.post('/api/frontend/logout').catch(() => {});
            clearCustomerAuth();
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

        async createPayment(orderId) {
            await ensureCsrfToken(true);
            const response = await window.axios.post('/api/payments/create', {
                order_id: orderId,
            });

            return response.data;
        },

        async fetchPaymentStatus(orderId) {
            const response = await window.axios.get(`/api/orders/${orderId}/payment-status`);
            return response.data;
        },

        async submitPaymentProof(orderId, payload) {
            await ensureCsrfToken(true);

            const formData = new FormData();
            formData.append('transaction_ref', payload.transaction_ref || '');
            formData.append('payment_note', payload.payment_note || '');
            formData.append('screenshot', payload.screenshot);

            const response = await window.axios.post(`/api/frontend/orders/${orderId}/payment-proof`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            return response.data;
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

        addToCart(productId, quantity = 1, variantSelection = null) {
            const product = this.productById(productId);

            if (!product || !isApprovedProduct(product)) {
                return false;
            }

            const nextQuantity = Math.max(1, quantity);
            const variant = resolveCartVariant(product, variantSelection);
            const stockLimit = stockLimitFor(product, variant, nextQuantity);

            if (stockLimit < 1) {
                return false;
            }

            this.cart.push({
                line_id: createLineId(),
                product_id: productId,
                quantity: Math.min(nextQuantity, stockLimit),
                variant: variant?.label || (typeof variantSelection === 'string' ? variantSelection : 'Default'),
                variant_id: variant?.id ?? variantSelection?.variant_id ?? null,
                variant_key: variant?.key ?? variantSelection?.variant_key ?? null,
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
            const variant = resolveCartVariant(product, item);
            item.quantity = Math.min(quantity, stockLimitFor(product, variant, quantity));
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

                    if (!product || !isApprovedProduct(product)) {
                        return null;
                    }

                    const variant = resolveCartVariant(product, entry);
                    const stockLimit = stockLimitFor(product, variant, entry.quantity);

                    if (stockLimit < 1) {
                        return null;
                    }

                    return {
                        line_id: entry.line_id || createLineId(),
                        ...entry,
                        quantity: Math.min(Math.max(entry.quantity, 1), stockLimit),
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
            const items = this.cartItems.map((item) => ({
                product_id: item.id,
                variant_id: item.variant_id,
                quantity: item.quantity,
            }));
            let requestPayload = {
                ...payload,
                items,
            };
            let headers = {};

            if (payload?.payment_screenshot instanceof File) {
                const formData = new FormData();

                Object.entries({
                    customer_name: payload.customer_name,
                    email: payload.email,
                    phone: payload.phone,
                    address_line1: payload.address_line1,
                    address_line2: payload.address_line2 || '',
                    city: payload.city,
                    postal_code: payload.postal_code,
                    notes: payload.notes || '',
                    payment_method: payload.payment_method,
                    payment_reference: payload.payment_reference || '',
                    transaction_reference: payload.transaction_reference || '',
                    payment_notes: payload.payment_notes || '',
                }).forEach(([key, value]) => {
                    formData.append(key, value ?? '');
                });

                items.forEach((item, index) => {
                    formData.append(`items[${index}][product_id]`, String(item.product_id));
                    formData.append(`items[${index}][variant_id]`, item.variant_id ? String(item.variant_id) : '');
                    formData.append(`items[${index}][quantity]`, String(item.quantity));
                });

                formData.append('payment_screenshot', payload.payment_screenshot);
                requestPayload = formData;
                headers['Content-Type'] = 'multipart/form-data';
            }

            const response = await window.axios.post('/api/frontend/checkout', requestPayload, { headers });

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
