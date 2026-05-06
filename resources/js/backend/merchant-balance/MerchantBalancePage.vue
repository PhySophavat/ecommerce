<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
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
                        Loading merchant balances...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em]" :class="card.tone">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="card rounded-2xl overflow-hidden">
                            <div class="card-header px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="kicker">Merchant Ledger</p>
                                    <!-- <h2 class="card-title mt-1">{{ filteredMerchants.length }} merchant balance records</h2> -->
                                </div>
                            </div>

                            <div class="toolbar px-6 py-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search merchant, email, ID, or bank"
                                    class="search-input flex-1 max-w-xs rounded-xl px-4 py-2 text-sm"
                                >
                                <select v-model="statusFilter" class="filter-select rounded-xl px-3 py-2 text-sm">
                                    <option value="">All statuses</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>

                            <div v-if="filteredMerchants.length === 0" class="px-6 py-10 text-center text-sm text-slate-400">
                                No merchant balances found.
                            </div>

                            <div v-else class="table-wrap overflow-x-auto">
                                <table class="min-w-[1540px] w-full text-sm">
                                    <thead>
                                        <tr class="table-head">
                                            <th class="px-6 py-3 text-left">Merchant</th>
                                            <th class="px-4 py-3 text-left">Merchant ID</th>
                                            <th class="px-4 py-3 text-left">User ID</th>
                                            <th class="px-4 py-3 text-left">Status</th>
                                            <!-- <th class="px-4 py-3 text-left">Bank Accounts</th> -->
                                            <th class="px-4 py-3 text-left">Total Balance</th>
                                            <th class="px-4 py-3 text-left">Available</th>
                                            <th class="px-4 py-3 text-left">Pending</th>
                                            <th class="px-4 py-3 text-left">Deposited</th>
                                            <th class="px-4 py-3 text-left">Withdrawn</th>
                                            <th class="px-6 py-3 text-left">Platform Fees</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in filteredMerchants" :key="item.id" class="table-row">
                                            <td class="px-6 py-3.5">
                                                <p class="font-bold text-slate-950">{{ item.shop_name }}</p>
                                                <!-- <p class="text-xs text-slate-400">{{ item.owner_name }} • {{ item.email }}</p> -->
                                            </td>
                                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ item.id }}</td>
                                            <td class="px-4 py-3.5 font-semibold text-slate-900">{{ item.user_id }}</td>
                                            <td class="px-4 py-3.5">
                                                <span class="badge" :class="statusClass(item.status)">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            
                                            <td class="px-4 py-3.5 font-semibold text-slate-950">{{ currency(item.balance_total) }}</td>
                                            <td class="px-4 py-3.5 text-slate-700">{{ currency(item.available_balance) }}</td>
                                            <td class="px-4 py-3.5 text-amber-600 font-semibold">{{ currency(item.pending_balance) }}</td>
                                            <td class="px-4 py-3.5 text-emerald-600 font-semibold">{{ currency(item.total_deposited) }}</td>
                                            <td class="px-4 py-3.5 text-slate-700">{{ currency(item.total_withdrawn) }}</td>
                                            <td class="px-6 py-3.5 text-rose-600 font-semibold">{{ currency(item.total_platform_fee_paid) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-footer px-6 py-3 flex items-center justify-between">
                                <span class="text-xs text-slate-400">Showing {{ filteredMerchants.length }} of {{ merchants.length }} merchants</span>
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

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/merchant-balance';
const screen = window.__APP_CONTEXT__?.screen ?? 'merchant-balance';
const isLoading = ref(true);
const notice = ref(null);
const searchQuery = ref('');
const statusFilter = ref('');
const openMenus = ref({});
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Merchant Balance',
        kicker: 'Merchant finance',
        subheadline: 'Review merchant balances, total deposits, withdrawals, and pending funds from one place.',
        links: {},
    },
    menu: [],
});
const summary = ref({
    merchants: 0,
    balance_total: '0.00',
    available_balance: '0.00',
    pending_balance: '0.00',
    total_deposited: '0.00',
    total_withdrawn: '0.00',
});
const merchants = ref([]);

const statCards = computed(() => [
    { label: 'Merchants', value: String(summary.value.merchants ?? 0), tone: 'text-slate-950' },
    { label: 'Total Balance', value: currency(summary.value.balance_total), tone: 'text-slate-950' },
    { label: 'Available', value: currency(summary.value.available_balance), tone: 'text-slate-950' },
    { label: 'Pending', value: currency(summary.value.pending_balance), tone: 'text-amber-600' },
    { label: 'Deposited', value: currency(summary.value.total_deposited), tone: 'text-emerald-600' },
    { label: 'Withdrawn', value: currency(summary.value.total_withdrawn), tone: 'text-slate-950' },
]);

const filteredMerchants = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return merchants.value.filter((item) =>
        (statusFilter.value === '' || item.status === statusFilter.value) && (
            query === ''
            || String(item.shop_name ?? '').toLowerCase().includes(query)
            || String(item.owner_name ?? '').toLowerCase().includes(query)
            || String(item.email ?? '').toLowerCase().includes(query)
            || String(item.id ?? '').includes(query)
            || String(item.user_id ?? '').includes(query)
            || (item.bank_accounts ?? []).some((account) =>
                String(account.bank_name ?? '').toLowerCase().includes(query)
                || String(account.account_holder_name ?? '').toLowerCase().includes(query)
                || String(account.account_number ?? '').toLowerCase().includes(query)
            )
        )
    );
});

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        summary.value = response.data.summary ?? summary.value;
        merchants.value = response.data.merchants ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
    } catch (error) {
        showError(error, 'Unable to load merchant balances.');
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

    if (!item.path || item.is_enabled === false) {
        return;
    }

    window.location.href = item.path;
}

function showError(error, fallback) {
    const response = error?.response?.data;
    notice.value = { type: 'error', text: response?.message ?? fallback };
}

function currency(value) {
    const amount = Number.parseFloat(String(value ?? '0'));
    return `$${Number.isNaN(amount) ? '0.00' : amount.toFixed(2)}`;
}

function statusClass(status) {
    return {
        Pending: 'badge-pending',
        Approved: 'badge-approved',
        Rejected: 'badge-rejected',
        Suspended: 'badge-suspended',
    }[status] ?? 'badge-default';
}
</script>

<style scoped>
.card { background: #fff; border: 0.5px solid #e2e8f0; }
.card-header { border-bottom: 0.5px solid #e2e8f0; }
.kicker { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
.card-title { font-size: 18px; font-weight: 700; color: #0f172a; }
.toolbar { border-bottom: 0.5px solid #e2e8f0; background: #fafafa; }
.search-input,
.filter-select {
    border: 0.5px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    outline: none;
}
.table-wrap { border-top: 0.5px solid #e2e8f0; }
.table-head { background: #f8fafc; }
.table-head th {
    font-size: 11px;
    font-weight: 500;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .07em;
    border-bottom: 0.5px solid #e2e8f0;
    white-space: nowrap;
}
.table-row { border-bottom: 0.5px solid #f1f5f9; }
.table-row:last-child { border-bottom: none; }
.table-footer { border-top: 0.5px solid #e2e8f0; background: #fafafa; }
.badge { display: inline-block; font-size: 11px; font-weight: 500; padding: 2px 9px; border-radius: 20px; white-space: nowrap; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #d1fae5; color: #065f46; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-suspended { background: #f1f5f9; color: #475569; }
.badge-default { background: #f1f5f9; color: #64748b; }
</style>
