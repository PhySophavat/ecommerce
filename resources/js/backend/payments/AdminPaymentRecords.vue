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
                    @primary-action="loadRecords"
                    @refresh="loadRecords"
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
                                    <input v-model.trim="filters.search" type="text" placeholder="Search order, customer, reference..." class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                    <select v-model="filters.paymentStatus" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All payment statuses</option>
                                        <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                    <select v-model="filters.paymentMethod" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All payment methods</option>
                                        <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                                    </select>
                                    <select v-model="filters.orderStatus" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All order statuses</option>
                                        <option v-for="status in orderStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="loading" class="px-6 py-12 text-center text-sm text-slate-400">Loading payment records...</div>
                            <div v-else-if="filteredRecords.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">No payment records matched the current filters.</div>
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-[920px] w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="min-w-[220px] px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Order</th>
                                            <th class="min-w-[140px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Customer</th>
                                            <th class="min-w-[140px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Method</th>
                                            <th class="min-w-[150px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Reference</th>
                                            <th class="min-w-[130px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Payment status</th>
                                            <th class="min-w-[110px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Order status</th>
                                            <th class="min-w-[90px] px-4 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="record in filteredRecords"
                                            :key="record.id"
                                            class="cursor-pointer border-t border-slate-100 transition hover:bg-slate-50"
                                            :class="selectedRecord?.id === record.id ? 'bg-rose-50/40' : ''"
                                            @click="selectRecord(record)"
                                        >
                                            <td class="px-4 py-4">
                                                <p class="truncate font-semibold text-slate-900">{{ record.number }}</p>
                                                <p class="text-xs text-slate-400">{{ formatDate(record.placed_at) }}</p>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">{{ record.customer_name }}</td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{{ record.payment_method_label }}</span>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">{{ record.payment_reference || 'No reference' }}</td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">{{ record.payment_status }}</span>
                                            </td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase text-rose-700">{{ record.status }}</span>
                                            </td>
                                            <td class="px-4 py-4 text-right font-semibold text-slate-900">${{ Number(record.total_amount).toFixed(2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <aside class="rounded-2xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <p class="text-[11px] uppercase tracking-[0.08em] text-slate-400">Payment detail</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ selectedRecordDetail?.number || 'Select a payment record' }}</h2>
                            </div>

                            <div v-if="detailLoading" class="px-6 py-12 text-center text-sm text-slate-400">Loading detail...</div>
                            <div v-else-if="!selectedRecordDetail" class="px-6 py-12 text-center text-sm text-slate-400">Choose a payment record to review customer payment information.</div>
                            <div v-else class="space-y-6 px-6 py-5">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <label class="text-xs uppercase tracking-[0.08em] text-slate-400">Payment status</label>
                                    <select v-model="statusForm.payment_status" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option v-for="status in paymentStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                    <button class="mt-3 rounded-xl bg-[#A25F88] px-4 py-2.5 text-sm font-semibold text-white" :disabled="updatingPaymentStatus" @click="savePaymentStatus">
                                        {{ updatingPaymentStatus ? 'Saving...' : 'Update payment' }}
                                    </button>
                                </div>

                                <div class="grid gap-3">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Customer</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedRecordDetail.customer_name }}</p>
                                        <p class="text-sm text-slate-500">{{ selectedRecordDetail.email }}</p>
                                        <p class="text-sm text-slate-500">{{ selectedRecordDetail.phone }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Payment</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedRecordDetail.payment_method_label }}</p>
                                        <p class="text-sm text-slate-500">Status: {{ selectedRecordDetail.payment_status }}</p>
                                        <p v-if="selectedRecordDetail.payment_reference" class="text-sm text-slate-500">Reference: {{ selectedRecordDetail.payment_reference }}</p>
                                        <p v-if="selectedRecordDetail.paid_at" class="text-sm text-slate-500">Paid at: {{ formatDateTime(selectedRecordDetail.paid_at) }}</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ selectedRecordDetail.payment_instructions }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Order total</p>
                                        <p class="mt-2 text-xl font-bold text-slate-900">${{ Number(selectedRecordDetail.total_amount).toFixed(2) }}</p>
                                        <p class="text-sm text-slate-500">Order status: {{ selectedRecordDetail.status }}</p>
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
const screen = window.__APP_CONTEXT__?.screen ?? 'payment-records';
const roleScope = window.__APP_CONTEXT__?.role_scope ?? 'admin';
const openMenus = ref({});
const dashboard = ref(initialDashboard());
const loading = ref(false);
const detailLoading = ref(false);
const updatingPaymentStatus = ref(false);
const notice = ref(null);
const records = ref([]);
const selectedRecord = ref(null);
const selectedRecordDetail = ref(null);
const filters = reactive({
    search: '',
    paymentStatus: '',
    paymentMethod: '',
    orderStatus: '',
});
const statusForm = reactive({
    payment_status: 'unpaid',
});

const paymentStatuses = ['unpaid', 'paid', 'failed', 'refunded'];
const orderStatuses = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
const paymentMethods = [
    { value: 'aba_qr', label: 'ABA QR' },
    { value: 'acleda', label: 'ACLEDA' },
    { value: 'wing', label: 'Wing' },
    { value: 'card', label: 'Card' },
    { value: 'cash', label: 'Cash' },
];

const paymentRecords = computed(() => records.value.filter((record) => record.payment_method !== 'cash' || record.payment_reference));

const filteredRecords = computed(() => paymentRecords.value.filter((record) => {
    const query = filters.search.trim().toLowerCase();

    if (filters.paymentStatus && record.payment_status !== filters.paymentStatus) {
        return false;
    }

    if (filters.paymentMethod && record.payment_method !== filters.paymentMethod) {
        return false;
    }

    if (filters.orderStatus && record.status !== filters.orderStatus) {
        return false;
    }

    if (!query) {
        return true;
    }

    return [
        record.number,
        record.customer_name,
        record.payment_reference,
        record.payment_method_label,
    ].join(' ').toLowerCase().includes(query);
}));

const summaryCards = computed(() => [
    { label: 'Records', value: String(paymentRecords.value.length), valueClass: 'text-slate-900' },
    { label: 'Paid', value: String(paymentRecords.value.filter((record) => record.payment_status === 'paid').length), valueClass: 'text-emerald-600' },
    { label: 'Unpaid', value: String(paymentRecords.value.filter((record) => record.payment_status === 'unpaid').length), valueClass: 'text-amber-600' },
    { label: 'Revenue', value: `$${paymentRecords.value.reduce((sum, record) => sum + Number(record.total_amount || 0), 0).toFixed(2)}`, valueClass: 'text-rose-600' },
]);

onMounted(loadRecords);

watch(
    () => [filters.paymentStatus, filters.paymentMethod, filters.orderStatus],
    async ([nextPaymentStatus, nextPaymentMethod, nextOrderStatus], [previousPaymentStatus, previousPaymentMethod, previousOrderStatus]) => {
        if (nextPaymentStatus === previousPaymentStatus && nextPaymentMethod === previousPaymentMethod && nextOrderStatus === previousOrderStatus) {
            return;
        }

        await loadRecords();
    },
);

async function loadRecords() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            params: {
                payment_status: filters.paymentStatus || undefined,
                payment_method: filters.paymentMethod || undefined,
                status: filters.orderStatus || undefined,
            },
        });
        records.value = response.data.orders ?? [];

        if (filteredRecords.value.length > 0) {
            const nextSelected = selectedRecord.value
                ? filteredRecords.value.find((record) => record.id === selectedRecord.value.id) ?? filteredRecords.value[0]
                : filteredRecords.value[0];

            await selectRecord(nextSelected);
        } else {
            selectedRecord.value = null;
            selectedRecordDetail.value = null;
        }

        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load payment records right now.') };
    } finally {
        loading.value = false;
    }
}

async function selectRecord(record) {
    if (!record) {
        return;
    }

    selectedRecord.value = record;
    detailLoading.value = true;

    try {
        const response = await window.axios.get(`${endpoint}/${record.id}`);
        selectedRecordDetail.value = response.data.order;
        statusForm.payment_status = response.data.order.payment_status;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load the selected payment record.') };
    } finally {
        detailLoading.value = false;
    }
}

async function savePaymentStatus() {
    if (!selectedRecord.value) {
        return;
    }

    updatingPaymentStatus.value = true;

    try {
        await window.axios.put(`${endpoint}/${selectedRecord.value.id}/payment-status`, { payment_status: statusForm.payment_status });
        notice.value = { type: 'success', text: 'Payment status updated successfully.' };
        await loadRecords();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to update payment status.') };
    } finally {
        updatingPaymentStatus.value = false;
    }
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

    window.location.href = item.path;
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
    const menu = buildFallbackMenu(screen, roleScope);

    syncOpenMenus(menu);

    return {
        meta: {
            brand: 'E-commerce',
            page_title: 'Payment Records',
            kicker: 'Frontend payments',
            subheadline: 'Review customer checkout payment records, references, and payment statuses from storefront orders.',
            primary_action_label: 'Refresh records',
        },
        menu,
    };
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
