<template>
    <div class="min-h-screen bg-[#F8FAFC] px-3 py-3 sm:px-4 lg:px-6 lg:py-5">
        <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1600px] overflow-x-clip rounded-[30px] border border-[#E5E7EB] bg-white shadow-[0_18px_42px_rgba(15,23,42,0.06)]">
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
                    <div v-if="loading" class="rounded-[24px] border border-[#E5E7EB] bg-white px-6 py-14 text-center text-sm text-[#6B7280]">
                        Loading finance overview...
                    </div>

                    <template v-else>
                        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article
                                v-for="card in metricCards"
                                :key="card.label"
                                class="flex min-h-[124px] flex-col justify-between rounded-[20px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:border-[#D8C0CF]"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ card.label }}</p>
                                        <p class="mt-3 text-[1.8rem] font-black tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
                                    </div>
                                    <span class="flex h-10 w-10 items-center justify-center rounded-[14px]" :class="card.iconWrap">
                                        <span class="text-xs font-bold" :class="card.iconTone">{{ card.icon }}</span>
                                    </span>
                                </div>
                            </article>
                        </section>

                        <section class="mt-5 grid gap-4 xl:grid-cols-3">
                            <div class="xl:col-span-3">
                                <FinanceStackedAreaChart
                                    title="Payment Verification Trend"
                                    subtitle="Success vs failed verification."
                                    :points="charts.payment_verification_trend"
                                />
                            </div>
                            <FinanceDonutChart title="Transaction IN / OUT" eyebrow="Transactions" :items="charts.transaction_flow" format="currency" />
                            <FinanceDonutChart title="Payment Count by Bank" eyebrow="Payments" :items="charts.payments_by_bank" />
                            <FinanceDonutChart title="Order Success / Failed" eyebrow="Orders" :items="charts.orders_by_status" />
                        </section>

                        <section class="mt-5 rounded-[24px] border border-[#E5E7EB] bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-6">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Transactions</p>
                                    <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Latest finance transactions</h3>
                                </div>
                                <div class="rounded-[12px] bg-[#F8FAFC] px-3 py-2 text-sm font-medium text-[#6B7280]">
                                    {{ scopeLabel }} scope
                                </div>
                            </div>

                            <div class="mt-5 overflow-x-auto">
                                <table class="min-w-[960px] w-full text-left text-sm">
                                    <thead class="bg-[#F8FAFC] text-[#6B7280]">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Code</th>
                                            <th class="px-4 py-3 font-semibold">Type</th>
                                            <th class="px-4 py-3 font-semibold">Method</th>
                                            <th class="px-4 py-3 font-semibold">Status</th>
                                            <th class="px-4 py-3 text-right font-semibold">Amount</th>
                                            <th class="px-4 py-3 font-semibold">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="recentTransactions.length === 0">
                                            <td colspan="6" class="px-4 py-12 text-center text-[#6B7280]">No finance transactions available yet.</td>
                                        </tr>
                                        <tr
                                            v-for="transaction in recentTransactions"
                                            :key="transaction.transaction_code"
                                            class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]"
                                        >
                                            <td class="px-4 py-3.5 font-semibold text-[#111827]">{{ transaction.transaction_code }}</td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold" :class="transaction.type === 'IN' ? 'bg-[#F6EAF1] text-[#A25F88]' : 'bg-[#FFF4EC] text-[#C56A54]'">
                                                    {{ transaction.type }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-[#111827]">{{ transaction.method }}</td>
                                            <td class="px-4 py-3.5">
                                                <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(transaction.status)">
                                                    {{ transaction.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3.5 text-right font-semibold text-[#111827]">{{ money(transaction.amount, transaction.currency) }}</td>
                                            <td class="max-w-[260px] px-4 py-3.5 text-[#6B7280]">
                                                <span class="block truncate">{{ transaction.description || 'No description' }}</span>
                                            </td>
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
import FinanceStackedAreaChart from './FinanceStackedAreaChart.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/finance-overview';
const screen = window.__APP_CONTEXT__?.screen ?? 'finance-overview';
const roleScope = window.__APP_CONTEXT__?.role_scope ?? 'admin';
const scope = ref('admin');
const loading = ref(true);
const openMenus = ref({});
const cards = ref({});
const charts = ref({
    transaction_flow: [],
    payments_by_bank: [],
    orders_by_status: [],
    payment_verification_trend: [],
});
const recentTransactions = ref([]);
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Finance Overview',
        kicker: 'Finance analytics',
        subheadline: 'Balances, payments, and transactions.',
        primary_action_label: 'Refresh overview',
    },
    menu: buildFallbackMenu(screen, roleScope),
});

const metricCards = computed(() => ([
    { label: 'Total Balance', value: money(cards.value.total_balance ?? 0), icon: '$', iconWrap: 'bg-[#F6EAF1]', iconTone: 'text-[#A25F88]' },
    { label: 'Total Transactions', value: String(cards.value.total_transactions ?? 0), icon: 'TX', iconWrap: 'bg-[#EFF6FF]', iconTone: 'text-[#2563EB]' },
    { label: 'Total IN', value: money(cards.value.total_in ?? 0), icon: 'IN', iconWrap: 'bg-[#ECFDF5]', iconTone: 'text-[#10B981]' },
    { label: 'Total OUT', value: money(cards.value.total_out ?? 0), icon: 'OUT', iconWrap: 'bg-[#FFF7ED]', iconTone: 'text-[#D97706]' },
    { label: 'Successful Orders', value: String(cards.value.successful_orders ?? 0), icon: 'OK', iconWrap: 'bg-[#EEF2FF]', iconTone: 'text-[#64748B]' },
    { label: 'Failed Orders', value: String(cards.value.failed_orders ?? 0), icon: 'NO', iconWrap: 'bg-[#FEF2F2]', iconTone: 'text-[#DC2626]' },
    { label: 'Successful Payments', value: String(cards.value.successful_payments ?? 0), icon: 'PAY', iconWrap: 'bg-[#ECFDF5]', iconTone: 'text-[#10B981]' },
    { label: 'Failed Payments', value: String(cards.value.failed_payments ?? 0), icon: 'ERR', iconWrap: 'bg-[#FEF2F2]', iconTone: 'text-[#DC2626]' },
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

        const menu = response.data.menu ?? buildFallbackMenu(screen, roleScope);
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

function statusClass(status) {
    return {
        success: 'bg-[#ECFDF5] text-[#10B981]',
        pending: 'bg-[#FFF7ED] text-[#D97706]',
        failed: 'bg-[#FEF2F2] text-[#DC2626]',
    }[String(status ?? '').toLowerCase()] ?? 'bg-[#F3F4F6] text-[#6B7280]';
}
</script>
