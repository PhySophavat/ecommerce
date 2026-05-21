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
                    @primary-action="loadMethods"
                    @refresh="loadMethods"
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

                    <section class="grid gap-5 2xl:grid-cols-[minmax(0,1.2fr)_minmax(360px,0.8fr)]">
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div class="grid gap-3 lg:grid-cols-2">
                                    <input v-model.trim="filters.search" type="text" placeholder="Search payment method..." class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                    <select v-model="filters.referenceRequirement" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none">
                                        <option value="">All reference rules</option>
                                        <option value="required">Reference required</option>
                                        <option value="optional">Reference optional</option>
                                    </select>
                                </div>
                            </div>

                            <div v-if="loading" class="px-6 py-12 text-center text-sm text-slate-400">Loading payment methods...</div>
                            <div v-else-if="filteredMethods.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">No payment methods matched the current filters.</div>
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-[900px] w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="min-w-[180px] px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Method</th>
                                            <th class="min-w-[260px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Customer flow</th>
                                            <th class="min-w-[150px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Reference</th>
                                            <th class="min-w-[160px] px-3 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Verification</th>
                                            <th class="min-w-[90px] px-3 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Orders</th>
                                            <th class="min-w-[90px] px-4 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="method in filteredMethods"
                                            :key="method.code"
                                            class="cursor-pointer border-t border-slate-100 transition hover:bg-slate-50"
                                            :class="selectedMethod?.code === method.code ? 'bg-rose-50/40' : ''"
                                            @click="selectedMethod = method"
                                        >
                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-slate-900">{{ method.label }}</p>
                                                <p class="text-xs uppercase tracking-[0.08em] text-slate-400">{{ method.code }}</p>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">{{ method.customer_text }}</td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="method.requires_reference ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'">
                                                    {{ method.requires_reference ? 'Required' : 'Optional' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-slate-600">{{ method.verification }}</td>
                                            <td class="px-3 py-4 text-right font-semibold text-slate-900">{{ method.orders_count }}</td>
                                            <td class="px-4 py-4 text-right font-semibold text-emerald-600">{{ method.paid_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <aside class="rounded-2xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <p class="text-[11px] uppercase tracking-[0.08em] text-slate-400">Method detail</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ selectedMethod?.label || 'Select a payment method' }}</h2>
                            </div>

                            <div v-if="!selectedMethod" class="px-6 py-12 text-center text-sm text-slate-400">Choose a frontend payment method to review how checkout uses it.</div>
                            <div v-else class="space-y-6 px-6 py-5">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Customer experience</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ selectedMethod.customer_text }}</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Reference rule</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedMethod.requires_reference ? 'Reference required' : 'Reference optional' }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Verification</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ selectedMethod.verification }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Orders using this method</p>
                                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ selectedMethod.orders_count }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Paid orders</p>
                                        <p class="mt-2 text-2xl font-bold text-emerald-600">{{ selectedMethod.paid_count }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Pending payments</p>
                                        <p class="mt-2 text-2xl font-bold text-amber-600">{{ selectedMethod.pending_count }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                                        <p class="text-xs uppercase tracking-[0.08em] text-slate-400">Failed or cancelled</p>
                                        <p class="mt-2 text-2xl font-bold text-rose-600">{{ selectedMethod.failed_count + selectedMethod.cancelled_count }}</p>
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
import { computed, onMounted, reactive, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
import { buildFallbackMenu } from '../layout/adminMenuFallback.js';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/payment-methods';
const screen = window.__APP_CONTEXT__?.screen ?? 'payment-methods';
const roleScope = window.__APP_CONTEXT__?.role_scope ?? 'admin';
const loading = ref(false);
const notice = ref(null);
const methods = ref([]);
const selectedMethod = ref(null);
const summary = ref({
    methods: 0,
    active_methods: 0,
    reference_required: 0,
    customer_orders: 0,
});
const openMenus = ref({});
const filters = reactive({
    search: '',
    referenceRequirement: '',
});
const dashboard = ref(initialDashboard());

const filteredMethods = computed(() => methods.value.filter((method) => {
    const query = filters.search.trim().toLowerCase();

    if (filters.referenceRequirement === 'required' && !method.requires_reference) {
        return false;
    }

    if (filters.referenceRequirement === 'optional' && method.requires_reference) {
        return false;
    }

    if (!query) {
        return true;
    }

    return [
        method.code,
        method.label,
        method.customer_text,
        method.verification,
    ].join(' ').toLowerCase().includes(query);
}));

const summaryCards = computed(() => [
    { label: 'Methods', value: String(summary.value.methods ?? 0), valueClass: 'text-slate-900' },
    { label: 'Active', value: String(summary.value.active_methods ?? 0), valueClass: 'text-emerald-600' },
    { label: 'Reference Required', value: String(summary.value.reference_required ?? 0), valueClass: 'text-amber-600' },
    { label: 'Customer Orders', value: String(summary.value.customer_orders ?? 0), valueClass: 'text-rose-600' },
]);

onMounted(loadMethods);

async function loadMethods() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        methods.value = response.data.methods ?? [];
        summary.value = response.data.summary ?? summary.value;
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
        if (methods.value.length > 0) {
            selectedMethod.value = methods.value.find((method) => method.code === selectedMethod.value?.code) ?? methods.value[0];
        } else {
            selectedMethod.value = null;
        }
        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load payment methods right now.') };
    } finally {
        loading.value = false;
    }
}

function initialDashboard() {
    const menu = buildFallbackMenu(screen, roleScope);
    syncOpenMenus(menu);

    return {
        meta: {
            brand: 'E-commerce',
            page_title: 'Payment Methods',
            kicker: 'Frontend payments',
            subheadline: 'Review the payment methods available during storefront checkout and how customers use them.',
            primary_action_label: 'Refresh methods',
        },
        menu,
    };
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
