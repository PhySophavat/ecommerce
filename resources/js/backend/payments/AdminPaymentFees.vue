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
                    @primary-action="loadFees"
                    @refresh="loadFees"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-[26px] border px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="loading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading platform fee records...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article v-for="card in summaryCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em]" :class="card.tone">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="rounded-[30px] border border-slate-200 bg-white shadow-sm">
                            <div class="border-b border-slate-200 px-6 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Fee ledger</p>
                                <h2 class="mt-1 text-xl font-bold text-slate-950">{{ filteredRecords.length }} platform fee records</h2>
                            </div>

                            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px]">
                                    <input
                                        v-model.trim="filters.search"
                                        type="text"
                                        placeholder="Search merchant, order, owner, or description"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none"
                                    >
                                    <input
                                        v-model="filters.date"
                                        type="date"
                                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none"
                                    >
                                </div>
                            </div>

                            <div v-if="filteredRecords.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">
                                No platform fee records matched the current filters.
                            </div>

                            <div v-else class="overflow-x-auto">
                                <table class="min-w-[1100px] w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Merchant</th>
                                            <th class="px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Owner</th>
                                            <th class="px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Order</th>
                                            <th class="px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Status</th>
                                            <th class="px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Description</th>
                                            <th class="px-4 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Fee</th>
                                            <th class="px-6 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Recorded</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="record in filteredRecords" :key="record.id" class="border-t border-slate-100">
                                            <td class="px-6 py-4">
                                                <p class="font-semibold text-slate-900">{{ record.merchant_name }}</p>
                                                <p class="text-xs text-slate-400">Merchant ID: {{ record.merchant_id }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">{{ record.owner_name || '-' }}</td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <span class="font-semibold text-slate-900">{{ record.order_number || '-' }}</span>
                                                <span v-if="record.order_id" class="block text-xs text-slate-400">Order ID: {{ record.order_id }}</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span
                                                    class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                                                    :class="record.status === 'collected' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                >
                                                    {{ record.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">{{ record.description || '-' }}</td>
                                            <td class="px-4 py-4 text-right font-semibold" :class="record.status === 'collected' ? 'text-rose-600' : 'text-amber-600'">{{ currency(record.amount) }}</td>
                                            <td class="px-6 py-4 text-slate-600">{{ record.created_at_label || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/payment-fees';
const screen = window.__APP_CONTEXT__?.screen ?? 'payment-fees';
const loading = ref(false);
const notice = ref(null);
const records = ref([]);
const openMenus = ref({});
const filters = reactive({
    search: '',
    date: '',
});
const summary = ref({
    total_fees: '0.00',
    fee_records: 0,
    merchants_charged: 0,
    average_fee: '0.00',
});
const dashboard = ref(initialDashboard());

const summaryCards = computed(() => [
    { label: 'Total Platform Fees', value: currency(summary.value.total_fees), tone: 'text-rose-600' },
    { label: 'Fee Records', value: String(summary.value.fee_records ?? 0), tone: 'text-slate-950' },
    { label: 'Merchants Charged', value: String(summary.value.merchants_charged ?? 0), tone: 'text-slate-950' },
    { label: 'Average Fee', value: currency(summary.value.average_fee), tone: 'text-amber-600' },
]);

const filteredRecords = computed(() => {
    const query = filters.search.trim().toLowerCase();

    return records.value.filter((record) => {
        if (filters.date && !String(record.created_at || '').startsWith(filters.date)) {
            return false;
        }

        if (!query) {
            return true;
        }

        return [
            record.merchant_name,
            record.owner_name,
            record.order_number,
            record.description,
            record.merchant_id,
            record.order_id,
        ].join(' ').toLowerCase().includes(query);
    });
});

onMounted(loadFees);

async function loadFees() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        records.value = response.data.records ?? [];
        summary.value = response.data.summary ?? summary.value;
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load platform fee records right now.') };
    } finally {
        loading.value = false;
    }
}

function initialDashboard() {
    return {
        meta: {
            brand: 'E-commerce',
            page_title: 'Platform Fee',
            kicker: 'Commission ledger',
            subheadline: 'Review platform fee deductions collected from merchant payouts across all orders.',
            primary_action_label: 'Refresh fees',
        },
        menu: [],
    };
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

function currency(value) {
    const amount = Number.parseFloat(String(value ?? '0'));
    return `$${Number.isNaN(amount) ? '0.00' : amount.toFixed(2)}`;
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
