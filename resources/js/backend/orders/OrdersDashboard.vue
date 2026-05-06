<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] w-full max-w-[1800px] overflow-x-clip rounded-[36px]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="loadOrders"
                    @refresh="loadOrders"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="mb-5 rounded-xl px-5 py-3.5 text-sm"
                        :class="notice.type === 'error' ? 'border border-rose-200 bg-rose-50 text-rose-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <section class="mb-5 grid gap-3 grid-cols-2 sm:grid-cols-4">
                        <article v-for="card in summaryCards" :key="card.label" class="rounded-2xl border border-slate-200 bg-white px-5 py-4">
                            <p class="text-[11px] uppercase tracking-[0.08em] text-slate-400">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-bold" :class="card.valueClass">{{ card.value }}</p>
                        </article>
                    </section>

                    <section class="grid gap-5 2xl:grid-cols-[minmax(0,1.18fr)_minmax(380px,0.82fr)]">
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-4">
                                    <input v-model.trim="filters.search" type="text" placeholder="Search order, customer, merchant..." class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                    <select v-model="filters.status" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All order statuses</option>
                                        <option v-for="status in allOrderStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                    <select v-model="filters.paymentStatus" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All payment statuses</option>
                                        <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                    <select v-model="filters.paymentMethod" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All payment methods</option>
                                        <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="loading" class="px-6 py-12 text-center text-sm text-slate-400">Loading orders...</div>
                            <div v-else-if="filteredOrders.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">No orders matched the current filters.</div>
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-[860px] w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="min-w-[220px] px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Order</th>
                                            <th class="min-w-[120px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Customer</th>
                                            <th class="min-w-[120px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Merchants</th>
                                            <th class="min-w-[140px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Order status</th>
                                            <th class="min-w-[150px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Payment</th>
                                            <th class="min-w-[90px] px-4 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="order in filteredOrders"
                                            :key="order.id"
                                            class="cursor-pointer border-t border-slate-100 transition hover:bg-slate-50"
                                            :class="selectedOrder?.id === order.id ? 'bg-rose-50/40' : ''"
                                            @click="selectOrder(order)"
                                        >
                                            <td class="px-4 py-4">
                                                <p class="truncate font-semibold text-slate-900">{{ order.number }}</p>
                                                <p class="text-xs text-slate-400">{{ formatDate(order.placed_at) }}</p>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">
                                                <p class="truncate">{{ order.customer_name }}</p>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">
                                                <p class="truncate">{{ merchantNames(order).join(', ') }}</p>
                                            </td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase text-rose-700">{{ order.status }}</span>
                                            </td>
                                            <td class="px-3 py-4">
                                                <div class="flex flex-col gap-1">
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{{ order.payment_method_label }}</span>
                                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">{{ order.payment_status }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-right font-semibold text-slate-900">${{ Number(order.total_amount).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <aside class="rounded-2xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <p class="text-[11px] uppercase tracking-[0.08em] text-slate-400">Order detail</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ selectedOrderDetail?.number || 'Select an order' }}</h2>
                            </div>

                            <div v-if="detailLoading" class="px-6 py-12 text-center text-sm text-slate-400">Loading detail...</div>
                            <div v-else-if="!selectedOrderDetail" class="px-6 py-12 text-center text-sm text-slate-400">Choose an order to review items and update statuses.</div>
                            <div v-else class="space-y-6 px-6 py-5">
                                <div class="grid gap-3" :class="roleScope === 'admin' ? 'xl:grid-cols-2' : 'xl:grid-cols-1'">
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <label class="text-xs uppercase tracking-[0.08em] text-slate-400">Order status</label>
                                        <select v-model="statusForm.status" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                            <option v-for="status in editableOrderStatuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                        <button class="mt-3 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white" :disabled="updatingStatus" @click="saveOrderStatus">
                                            {{ updatingStatus ? 'Saving...' : 'Update status' }}
                                        </button>
                                    </div>

                                    <div v-if="roleScope === 'admin'" class="rounded-2xl border border-slate-200 p-4">
                                        <label class="text-xs uppercase tracking-[0.08em] text-slate-400">Payment status</label>
                                        <select v-model="statusForm.payment_status" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                            <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                        <button class="mt-3 rounded-xl bg-[#A25F88] px-4 py-2.5 text-sm font-semibold text-white" :disabled="updatingPaymentStatus" @click="savePaymentStatus">
                                            {{ updatingPaymentStatus ? 'Saving...' : 'Update payment' }}
                                        </button>
                                    </div>
                                </div>

                                <div class="grid gap-3">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Customer</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedOrderDetail.customer_name }}</p>
                                        <p class="text-sm text-slate-500">{{ selectedOrderDetail.email }}</p>
                                        <p class="text-sm text-slate-500">{{ selectedOrderDetail.phone }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Shipping</p>
                                        <p class="mt-2 text-sm text-slate-700">{{ selectedOrderDetail.address_line1 }}</p>
                                        <p v-if="selectedOrderDetail.address_line2" class="text-sm text-slate-700">{{ selectedOrderDetail.address_line2 }}</p>
                                        <p class="text-sm text-slate-700">{{ selectedOrderDetail.city }}, {{ selectedOrderDetail.postal_code }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Payment</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedOrderDetail.payment_method_label }}</p>
                                        <p class="text-sm text-slate-500">Status: {{ selectedOrderDetail.payment_status }}</p>
                                        <p v-if="selectedOrderDetail.payment_reference" class="text-sm text-slate-500">Reference: {{ selectedOrderDetail.payment_reference }}</p>
                                        <p v-if="selectedOrderDetail.paid_at" class="text-sm text-slate-500">Paid at: {{ formatDateTime(selectedOrderDetail.paid_at) }}</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ selectedOrderDetail.payment_instructions }}</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div v-for="group in selectedOrderDetail.merchant_groups" :key="group.merchant_id || group.merchant_name" class="rounded-2xl border border-slate-200 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <h3 class="font-semibold text-slate-900">{{ group.merchant_name }}</h3>
                                            <span class="text-sm font-semibold text-slate-500">${{ Number(group.subtotal).toFixed(2) }}</span>
                                        </div>
                                        <div class="mt-4 space-y-3">
                                            <div v-for="item in group.items" :key="item.id" class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="h-12 w-12 rounded-xl object-cover">
                                                    <div v-else class="h-12 w-12 rounded-xl bg-slate-200"></div>
                                                    <div>
                                                        <p class="font-semibold text-slate-900">{{ item.product_name }}</p>
                                                        <p class="text-xs text-slate-500">${{ Number(item.price).toFixed(2) }} × {{ item.quantity }}</p>
                                                    </div>
                                                </div>
                                                <span class="font-semibold text-slate-900">${{ Number(item.total).toFixed(2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
import { buildFallbackMenu } from '../layout/adminMenuFallback.js';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/orders';
const screen = window.__APP_CONTEXT__?.screen ?? 'orders';
const roleScope = window.__APP_CONTEXT__?.role_scope ?? 'admin';
const initialStatus = window.__APP_CONTEXT__?.initial_status ?? defaultStatusForScreen(screen);
const openMenus = ref({});
const dashboard = ref(initialDashboard());
const loading = ref(false);
const detailLoading = ref(false);
const updatingStatus = ref(false);
const updatingPaymentStatus = ref(false);
const notice = ref(null);
const orders = ref([]);
const selectedOrder = ref(null);
const selectedOrderDetail = ref(null);
const filters = reactive({
    search: '',
    status: initialStatus,
    paymentStatus: '',
    paymentMethod: '',
});
const statusForm = reactive({
    status: 'pending',
    payment_status: 'unpaid',
});

const paymentStatuses = ['unpaid', 'paid', 'failed', 'refunded'];
const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'aba_qr', label: 'ABA QR' },
    { value: 'wing', label: 'Wing' },
    { value: 'card', label: 'Card' },
];
const editableOrderStatuses = computed(() => roleScope === 'admin'
    ? ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']
    : ['pending', 'processing', 'shipped', 'delivered', 'cancelled']);
const allOrderStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

const filteredOrders = computed(() => orders.value.filter((order) => {
    const query = filters.search.trim().toLowerCase();
    const merchantText = merchantNames(order).join(' ').toLowerCase();

    if (filters.status && order.status !== filters.status) {
        return false;
    }

    if (filters.paymentStatus && order.payment_status !== filters.paymentStatus) {
        return false;
    }

    if (filters.paymentMethod && order.payment_method !== filters.paymentMethod) {
        return false;
    }

    if (!query) {
        return true;
    }

    return [
        order.number,
        order.customer_name,
        merchantText,
    ].join(' ').toLowerCase().includes(query);
}));

const summaryCards = computed(() => [
    { label: 'Orders', value: String(orders.value.length), valueClass: 'text-slate-900' },
    { label: 'Pending', value: String(orders.value.filter((order) => order.status === 'pending').length), valueClass: 'text-amber-600' },
    { label: 'Paid', value: String(orders.value.filter((order) => order.payment_status === 'paid').length), valueClass: 'text-emerald-600' },
    { label: 'Revenue', value: `$${orders.value.reduce((sum, order) => sum + Number(order.total_amount || 0), 0).toFixed(2)}`, valueClass: 'text-rose-600' },
]);

onMounted(loadOrders);

watch(
    () => [filters.status, filters.paymentStatus, filters.paymentMethod],
    async ([nextStatus, nextPaymentStatus, nextPaymentMethod], [previousStatus, previousPaymentStatus, previousPaymentMethod]) => {
        if (nextStatus === previousStatus && nextPaymentStatus === previousPaymentStatus && nextPaymentMethod === previousPaymentMethod) {
            return;
        }

        await loadOrders();
    },
);

async function loadOrders() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            params: {
                status: filters.status || undefined,
                payment_status: filters.paymentStatus || undefined,
                payment_method: filters.paymentMethod || undefined,
            },
        });
        orders.value = response.data.orders ?? [];

        if (orders.value.length > 0) {
            const nextSelected = selectedOrder.value
                ? orders.value.find((order) => order.id === selectedOrder.value.id) ?? orders.value[0]
                : orders.value[0];

            await selectOrder(nextSelected);
        } else {
            selectedOrder.value = null;
            selectedOrderDetail.value = null;
        }

        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load orders right now.') };
    } finally {
        loading.value = false;
    }
}

async function selectOrder(order) {
    selectedOrder.value = order;
    detailLoading.value = true;

    try {
        const response = await window.axios.get(`${endpoint}/${order.id}`);
        selectedOrderDetail.value = response.data.order;
        statusForm.status = response.data.order.status;
        statusForm.payment_status = response.data.order.payment_status;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load the selected order.') };
    } finally {
        detailLoading.value = false;
    }
}

async function saveOrderStatus() {
    if (!selectedOrder.value) {
        return;
    }

    updatingStatus.value = true;

    try {
        await window.axios.put(`${endpoint}/${selectedOrder.value.id}/status`, { status: statusForm.status });
        notice.value = { type: 'success', text: 'Order status updated successfully.' };
        await loadOrders();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to update order status.') };
    } finally {
        updatingStatus.value = false;
    }
}

async function savePaymentStatus() {
    if (!selectedOrder.value || roleScope !== 'admin') {
        return;
    }

    updatingPaymentStatus.value = true;

    try {
        await window.axios.put(`${endpoint}/${selectedOrder.value.id}/payment-status`, { payment_status: statusForm.payment_status });
        notice.value = { type: 'success', text: 'Payment status updated successfully.' };
        await loadOrders();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to update payment status.') };
    } finally {
        updatingPaymentStatus.value = false;
    }
}

function merchantNames(order) {
    return (order.merchant_groups ?? []).map((group) => group.merchant_name);
}

function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        window.axios.post('/auth/logout').finally(() => {
            window.location.assign('/login');
        });
        return;
    }

    if (!item.path || item.is_enabled === false) {
        return;
    }

    if (item.slug === 'all-orders' && isCurrentPath(item.path)) {
        resetFiltersForAllOrders();
        window.history.replaceState({}, '', item.path);
        loadOrders();
        return;
    }

    window.location.href = item.path;
}

function isCurrentPath(path) {
    try {
        return new URL(path, window.location.origin).pathname === window.location.pathname;
    } catch {
        return false;
    }
}

function resetFiltersForAllOrders() {
    filters.search = '';
    filters.status = '';
    filters.paymentStatus = '';
    filters.paymentMethod = '';
    notice.value = null;
}

function syncOpenMenus(menuItems) {
    openMenus.value = menuItems.reduce((state, item) => {
        state[item.slug] = Boolean(item.is_expanded);
        return state;
    }, {});
}

function toggleMenu(slug) {
    openMenus.value = { ...openMenus.value, [slug]: !openMenus.value[slug] };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

function formatDate(value) {
    if (!value) {
        return 'Recent';
    }

    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function formatDateTime(value) {
    if (!value) {
        return 'Pending';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function initialDashboard() {
    const menu = buildFallbackMenu(screen);

    syncOpenMenus(menu);

    return {
        meta: {
            brand: roleScope === 'admin' ? 'E-commerce' : 'Merchant orders',
            page_title: roleScope === 'admin' ? titleForScreen(screen) : 'My Orders',
            kicker: roleScope === 'admin' ? 'Order management' : 'Merchant fulfillment',
            subheadline: roleScope === 'admin'
                ? subtitleForScreen(screen)
                : 'View only the orders that include your products and move them through the fulfillment flow.',
            primary_action_label: 'Refresh orders',
        },
        menu,
    };
}

function defaultStatusForScreen(currentScreen) {
    return {
        'pending-orders': 'pending',
        'processing-orders': 'processing',
        'shipped-orders': 'shipped',
        'delivered-orders': 'delivered',
        'cancelled-orders': 'cancelled',
        'returns-refunds': 'refunded',
        'merchant-pending-orders': 'pending',
        'merchant-processing-orders': 'processing',
        'merchant-shipped-orders': 'shipped',
        'merchant-delivered-orders': 'delivered',
        'merchant-cancelled-orders': 'cancelled',
        'merchant-refunded-orders': 'refunded',
    }[currentScreen] ?? '';
}

function titleForScreen(currentScreen) {
    return {
        'pending-orders': 'Pending Orders',
        'processing-orders': 'Processing Orders',
        'shipped-orders': 'Shipped Orders',
        'delivered-orders': 'Delivered Orders',
        'cancelled-orders': 'Cancelled Orders',
        'returns-refunds': 'Returns / Refunds',
        'merchant-pending-orders': 'Pending Orders',
        'merchant-processing-orders': 'Processing Orders',
        'merchant-shipped-orders': 'Shipped Orders',
        'merchant-delivered-orders': 'Delivered Orders',
        'merchant-cancelled-orders': 'Cancelled Orders',
        'merchant-refunded-orders': 'Returns / Refunds',
    }[currentScreen] ?? 'Orders';
}

function subtitleForScreen(currentScreen) {
    return {
        'pending-orders': 'Review new customer orders created from the storefront checkout and move them into processing.',
        'processing-orders': 'Track paid and prepared orders that are currently being fulfilled by merchants.',
        'shipped-orders': 'Review orders that have left the warehouse and are moving through delivery.',
        'delivered-orders': 'Check the completed storefront orders that customers have already received.',
        'cancelled-orders': 'Review storefront orders that were cancelled before completion.',
        'returns-refunds': 'Review refunded storefront orders and confirm payment reversals when required.',
        'merchant-pending-orders': 'Review new customer orders for your products and move them into processing.',
        'merchant-processing-orders': 'Track the customer orders your shop is currently preparing.',
        'merchant-shipped-orders': 'Review the orders from your shop that have already been shipped.',
        'merchant-delivered-orders': 'Review the customer orders your shop has completed.',
        'merchant-cancelled-orders': 'Review the customer orders for your shop that were cancelled.',
        'merchant-refunded-orders': 'Review refunded customer orders that included your products.',
    }[currentScreen] ?? 'Review all customer orders, track merchant breakdowns, and update both order and payment statuses.';
}

function extractMessage(error, fallback) {
    const data = error?.response?.data;

    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) {
            return first;
        }
    }

    return data?.message ?? fallback;
}
</script>
