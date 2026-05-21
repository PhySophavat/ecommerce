<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-x-clip rounded-[36px]">
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
                    @primary-action="loadDashboard"
                    @header-filter-change="updatePeriodFilter"
                    @refresh="loadDashboard"
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

                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading platform wallet...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em]" :class="card.tone">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                            <section class="rounded-[30px] border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-200 px-6 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Top merchants</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Highest fee contributors</h2>
                                </div>

                                <div v-if="filteredTopMerchants.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">
                                    No merchant fees have been recorded yet.
                                </div>

                                <div v-else class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Merchant</th>
                                                <th class="px-4 py-3 text-left text-[11px] uppercase tracking-[0.08em] text-slate-400">Owner</th>
                                                <th class="px-4 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Fee Paid</th>
                                                <th class="px-6 py-3 text-right text-[11px] uppercase tracking-[0.08em] text-slate-400">Available Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="merchant in filteredTopMerchants" :key="merchant.id" class="border-t border-slate-100">
                                                <td class="px-6 py-4 font-semibold text-slate-900">{{ merchant.shop_name }}</td>
                                                <td class="px-4 py-4 text-slate-600">{{ merchant.owner_name || '-' }}</td>
                                                <td class="px-4 py-4 text-right font-semibold text-rose-600">{{ currency(merchant.total_platform_fee_paid) }}</td>
                                                <td class="px-6 py-4 text-right text-slate-700">{{ currency(merchant.available_balance) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-200 px-6 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Recent activity</p>
                                    <h2 class="mt-1 text-xl font-bold text-slate-950">Latest platform fee deductions</h2>
                                </div>

                                <div v-if="filteredRecentFees.length === 0" class="px-6 py-12 text-center text-sm text-slate-400">
                                    No recent fee activity yet.
                                </div>

                                <div v-else class="space-y-4 px-6 py-5">
                                    <article v-for="fee in filteredRecentFees" :key="fee.id" class="rounded-[24px] border border-slate-200 bg-slate-50 px-4 py-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ fee.merchant_name }}</p>
                                                <p class="mt-1 text-sm text-slate-500">{{ fee.description || 'Platform fee recorded.' }}</p>
                                                <p class="mt-2 text-xs uppercase tracking-[0.08em] text-slate-400">
                                                    {{ fee.order_number ? `Order ${fee.order_number}` : 'No order number' }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-rose-600">{{ currency(fee.amount) }}</p>
                                                <p class="mt-1 text-xs text-slate-400">{{ fee.created_at_label || '-' }}</p>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </section>
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

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/wallet';
const screen = window.__APP_CONTEXT__?.screen ?? 'wallet';
const isLoading = ref(true);
const notice = ref(null);
const openMenus = ref({});
const selectedPeriod = ref('30d');
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Wallet',
        kicker: 'Platform wallet',
        subheadline: 'Track total platform fee balance collected from merchants and review the latest fee deductions.',
        primary_action_label: 'Refresh wallet',
        hide_secondary_refresh: true,
        toolbar_filter: {
            label: 'Period',
            value: '30d',
            options: [
                { label: 'All', value: 'all' },
                { label: 'Today', value: 'today' },
                { label: '7 Days', value: '7d' },
                { label: '30 Days', value: '30d' },
            ],
        },
    },
    menu: [],
});
const summary = ref({
    platform_fee_balance: '0.00',
    merchants_charged: 0,
    fee_transactions: 0,
    average_fee_per_merchant: '0.00',
});
const topMerchants = ref([]);
const recentFees = ref([]);

const statCards = computed(() => [
    { label: 'Platform Fee Balance', value: currency(summary.value.platform_fee_balance), tone: 'text-rose-600' },
    { label: 'Merchants Charged', value: String(summary.value.merchants_charged ?? 0), tone: 'text-slate-950' },
    { label: 'Recent Fee Records', value: String(filteredRecentFees.value.length), tone: 'text-slate-950' },
    { label: 'Average Per Merchant', value: currency(summary.value.average_fee_per_merchant), tone: 'text-amber-600' },
]);
const filteredRecentFees = computed(() => recentFees.value.filter((fee) => matchesPeriod(fee.created_at)));
const filteredTopMerchants = computed(() => topMerchants.value);

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        summary.value = response.data.summary ?? summary.value;
        topMerchants.value = response.data.top_merchants ?? [];
        recentFees.value = response.data.recent_fees ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: {
                ...dashboard.value.meta,
                ...(response.data.meta ?? {}),
                toolbar_filter: {
                    ...(dashboard.value.meta.toolbar_filter ?? {}),
                    ...(response.data.meta?.toolbar_filter ?? {}),
                    value: selectedPeriod.value,
                },
            },
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
        notice.value = null;
    } catch (error) {
        const data = error?.response?.data;
        notice.value = { type: 'error', text: data?.message ?? 'Unable to load platform wallet right now.' };
    } finally {
        isLoading.value = false;
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

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

function currency(value) {
    const amount = Number.parseFloat(String(value ?? '0'));
    return `$${Number.isNaN(amount) ? '0.00' : amount.toFixed(2)}`;
}

function updatePeriodFilter(value) {
    selectedPeriod.value = value || '30d';
    dashboard.value = {
        ...dashboard.value,
        meta: {
            ...dashboard.value.meta,
            toolbar_filter: {
                ...(dashboard.value.meta.toolbar_filter ?? {}),
                value: selectedPeriod.value,
            },
        },
    };
}

function matchesPeriod(value) {
    if (selectedPeriod.value === 'all') {
        return true;
    }

    const date = value ? new Date(value) : null;

    if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
        return selectedPeriod.value === '30d';
    }

    const now = new Date();

    if (selectedPeriod.value === 'today') {
        return date.getFullYear() === now.getFullYear()
            && date.getMonth() === now.getMonth()
            && date.getDate() === now.getDate();
    }

    const days = selectedPeriod.value === '7d' ? 7 : 30;
    const diff = now.getTime() - date.getTime();

    return diff <= days * 24 * 60 * 60 * 1000;
}
</script>
