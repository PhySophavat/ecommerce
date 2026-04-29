<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <AdminSidebar :dashboard="dashboard" :is-menu-open="isMenuOpen" :screen="screen" @select-item="handleMenuSelection" @toggle-menu="toggleMenu" />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader :dashboard="dashboard" :is-menu-open="isMenuOpen" :screen="screen" @primary-action="loadDashboard" @refresh="loadDashboard" @select-item="handleMenuSelection" @toggle-menu="toggleMenu" />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section v-if="notice" class="mb-6 rounded-[26px] border px-5 py-4 text-sm" :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading deposits...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-4">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Top-up queue</p>
                                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Merchant deposit requests</h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button v-for="filter in filters" :key="filter.value" type="button" class="rounded-full px-4 py-2 text-sm font-semibold transition" :class="selectedStatus === filter.value ? 'bg-[#A25F88] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" @click="changeStatus(filter.value)">
                                        {{ filter.label }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="deposits.length === 0" class="mt-6 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                                No deposits match this filter.
                            </div>

                            <div v-else class="mt-6 overflow-x-auto rounded-[28px] border border-slate-200">
                                <table class="min-w-[1080px] w-full text-sm">
                                    <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                        <tr>
                                            <th class="px-5 py-4">Merchant</th>
                                            <th class="px-4 py-4">Amount</th>
                                            <th class="px-4 py-4">Bank</th>
                                            <th class="px-4 py-4">Sender</th>
                                            <th class="px-4 py-4">Proof</th>
                                            <th class="px-4 py-4">Status</th>
                                            <th class="px-5 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in deposits" :key="item.id" class="border-t border-slate-200">
                                            <td class="px-5 py-4">
                                                <p class="font-bold text-slate-950">{{ item.merchant?.shop_name }}</p>
                                                <p class="text-slate-500">{{ item.merchant?.owner_name }} • {{ item.merchant?.email }}</p>
                                            </td>
                                            <td class="px-4 py-4 font-bold text-slate-950">{{ currency(item.amount) }}</td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p class="font-semibold text-slate-900">{{ item.bank_name }}</p>
                                                <p class="line-clamp-2 break-all text-xs">{{ item.khqr_code }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p>{{ item.account_name }}</p>
                                                <p>{{ item.account_number }} â€¢ {{ item.phone_number }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <a v-if="item.payment_proof_url" :href="item.payment_proof_url" target="_blank" class="font-semibold text-[#A25F88] hover:underline">View proof</a>
                                                <span v-else>-</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="statusClass(item.status)">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button v-if="item.status === 'pending'" type="button" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500" :disabled="processingId === item.id" @click="runAction(item, 'approve')">
                                                        Approve
                                                    </button>
                                                    <button v-if="item.status === 'pending'" type="button" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500" :disabled="processingId === item.id" @click="runAction(item, 'reject')">
                                                        Reject
                                                    </button>
                                                </div>
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

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/deposits';
const screen = window.__APP_CONTEXT__?.screen ?? 'deposits';
const isLoading = ref(true);
const notice = ref(null);
const processingId = ref(null);
const selectedStatus = ref('all');
const openMenus = ref({});
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Deposits',
        kicker: 'Wallet top-ups',
        subheadline: 'Review merchant deposit proofs.',
        links: {
            frontend: '/',
            admin_users: '/admin/users',
            admin_merchants: '/admin/merchants',
            admin_deposits: '/admin/deposits',
            admin_withdrawals: '/admin/withdrawals',
            logout: '/auth/logout',
        },
    },
    menu: [],
});
const filters = ref([]);
const deposits = ref([]);
const summary = ref({ all: 0, pending: 0, approved: 0, rejected: 0 });

const statCards = computed(() => [
    { label: 'All', value: String(summary.value.all ?? 0) },
    { label: 'Pending', value: String(summary.value.pending ?? 0) },
    { label: 'Approved', value: String(summary.value.approved ?? 0) },
    { label: 'Rejected', value: String(summary.value.rejected ?? 0) },
]);

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint, { params: { status: selectedStatus.value } });
        summary.value = response.data.summary ?? summary.value;
        filters.value = response.data.filters ?? [];
        selectedStatus.value = response.data.selected_status ?? selectedStatus.value;
        deposits.value = response.data.deposits ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
    } catch (error) {
        showError(error, 'Unable to load deposits.');
    } finally {
        isLoading.value = false;
    }
}

async function changeStatus(status) {
    selectedStatus.value = status;
    await loadDashboard();
}

async function runAction(item, action) {
    const adminNote = window.prompt('Optional admin note:', '') ?? '';
    processingId.value = item.id;

    try {
        const response = await window.axios.put(`/api/admin/deposits/${item.id}/${action}`, { admin_note: adminNote });
        notice.value = { type: 'success', text: response.data.message ?? 'Deposit updated.' };
        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to update deposit.');
    } finally {
        processingId.value = null;
    }
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

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        await window.axios.post('/auth/logout');
        window.location.assign('/login');
        return;
    }

    if (!item.path || !item.is_enabled) return;
    window.location.href = item.path;
}

function showError(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const first = Object.values(response.errors).flat()[0];
        notice.value = { type: 'error', text: first ?? fallback };
        return;
    }

    notice.value = { type: 'error', text: response?.message ?? fallback };
}

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function statusClass(status) {
    return {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}
</script>
