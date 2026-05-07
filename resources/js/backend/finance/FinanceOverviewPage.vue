<template>
    <div class="min-h-screen bg-[#F8FAFC] px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px] border border-[#E5E7EB] bg-white shadow-[0_30px_80px_rgba(17,24,39,0.08)]">
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
                    @primary-action="refresh"
                    @refresh="refresh"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <div v-if="loading" class="rounded-[28px] border border-[#E5E7EB] bg-white px-6 py-14 text-center text-sm text-[#6B7280]">
                        Loading finance overview...
                    </div>

                    <template v-else>
                        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article v-for="card in metricCards" :key="card.label" class="rounded-[28px] border border-[#E5E7EB] bg-white p-5 shadow-sm transition hover:border-[#A25F88]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">{{ card.label }}</p>
                                <p class="mt-4 text-3xl font-black tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="mt-6 grid gap-6 xl:grid-cols-3">
                            <FinanceDonutChart title="Transaction IN / OUT" eyebrow="Transactions" :items="charts.transaction_flow" format="currency" />
                            <FinanceDonutChart title="Payment Count by Bank" eyebrow="Payments" :items="charts.payments_by_bank" />
                            <FinanceDonutChart title="Order Success / Failed" eyebrow="Orders" :items="charts.orders_by_status" />
                        </section>

                        <section class="mt-6 rounded-[28px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">Recent ledger</p>
                                    <h3 class="mt-2 text-xl font-bold tracking-[-0.03em] text-[#111827]">Latest finance transactions</h3>
                                </div>
                                <div class="rounded-2xl bg-[#F8FAFC] px-4 py-2 text-sm text-[#6B7280]">
                                    {{ scopeLabel }} scope
                                </div>
                            </div>

                            <div class="mt-5 overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-[#E5E7EB] text-[#6B7280]">
                                            <th class="px-3 py-3 font-semibold">Code</th>
                                            <th class="px-3 py-3 font-semibold">Type</th>
                                            <th class="px-3 py-3 font-semibold">Method</th>
                                            <th class="px-3 py-3 font-semibold">Status</th>
                                            <th class="px-3 py-3 font-semibold">Amount</th>
                                            <th class="px-3 py-3 font-semibold">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="transaction in recentTransactions" :key="transaction.transaction_code" class="border-b border-[#F3F4F6] last:border-b-0">
                                            <td class="px-3 py-4 font-semibold text-[#111827]">{{ transaction.transaction_code }}</td>
                                            <td class="px-3 py-4">
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="transaction.type === 'IN' ? 'bg-[#F6EBF1] text-[#A25F88]' : 'bg-[#FFF4F2] text-[#C56A54]'">
                                                    {{ transaction.type }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-4 text-[#111827]">{{ transaction.method }}</td>
                                            <td class="px-3 py-4 text-[#6B7280]">{{ transaction.status }}</td>
                                            <td class="px-3 py-4 font-semibold text-[#111827]">{{ money(transaction.amount, transaction.currency) }}</td>
                                            <td class="px-3 py-4 text-[#6B7280]">{{ transaction.description || 'No description' }}</td>
                                        </tr>
                                        <tr v-if="recentTransactions.length === 0">
                                            <td colspan="6" class="px-3 py-8 text-center text-[#6B7280]">No finance transactions available yet.</td>
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
import { computed, onMounted, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
import { buildFallbackMenu } from '../layout/adminMenuFallback.js';
import FinanceDonutChart from './FinanceDonutChart.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/finance-overview';
const screen = window.__APP_CONTEXT__?.screen ?? 'finance-overview';
const scope = ref('admin');
const loading = ref(true);
const openMenus = ref({});
const cards = ref({});
const charts = ref({
    transaction_flow: [],
    payments_by_bank: [],
    orders_by_status: [],
});
const recentTransactions = ref([]);
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Finance Overview',
        kicker: 'Finance analytics',
        subheadline: 'Review balances, payment mix, transaction flow, and order outcomes.',
        primary_action_label: 'Refresh overview',
    },
    menu: buildFallbackMenu(screen),
});

const metricCards = computed(() => ([
    { label: 'Total Balance', value: money(cards.value.total_balance ?? 0) },
    { label: 'Total Transactions', value: String(cards.value.total_transactions ?? 0) },
    { label: 'Total IN', value: money(cards.value.total_in ?? 0) },
    { label: 'Total OUT', value: money(cards.value.total_out ?? 0) },
    { label: 'Successful Orders', value: String(cards.value.successful_orders ?? 0) },
    { label: 'Failed Orders', value: String(cards.value.failed_orders ?? 0) },
    { label: 'Successful Payments', value: String(cards.value.successful_payments ?? 0) },
    { label: 'Failed Payments', value: String(cards.value.failed_payments ?? 0) },
]));

const scopeLabel = computed(() => scope.value === 'merchant' ? 'Merchant' : 'Admin');

onMounted(async () => {
    await refresh();
});

async function refresh() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        scope.value = response.data.scope ?? scope.value;
        cards.value = response.data.cards ?? cards.value;
        charts.value = response.data.charts ?? charts.value;
        recentTransactions.value = response.data.recent_transactions ?? [];

        const menu = response.data.menu ?? buildFallbackMenu(screen);
        dashboard.value = {
            meta: {
                ...dashboard.value.meta,
                ...(response.data.meta ?? response.data.dashboard?.meta ?? {}),
            },
            menu,
        };

        syncOpenMenus(menu);
    } finally {
        loading.value = false;
    }
}

function syncOpenMenus(menuItems) {
    openMenus.value = menuItems.reduce((state, item) => {
        state[item.slug] = Boolean(item.is_expanded);
        return state;
    }, {});
}

function toggleMenu(slug) {
    openMenus.value = {
        ...openMenus.value,
        [slug]: !openMenus.value[slug],
    };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        await window.axios.post('/auth/logout');
        window.location.assign('/login');
        return;
    }

    if (!item.path || item.is_enabled === false) {
        return;
    }

    window.location.href = item.path;
}

function money(value, currencyCode = 'USD') {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: currencyCode }).format(Number(value || 0));
}
</script>
