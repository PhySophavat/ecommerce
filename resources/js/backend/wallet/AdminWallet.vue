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
                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading wallet overview...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="grid gap-6 xl:grid-cols-2">
                            <a :href="links.deposits" class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm transition hover:-translate-y-0.5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Deposits</p>
                                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Review deposit requests</h2>
                                <p class="mt-3 text-sm text-slate-500">Open the admin deposit queue to verify KHQR proofs and approve wallet top-ups.</p>
                            </a>

                            <a :href="links.withdrawals" class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm transition hover:-translate-y-0.5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Withdrawals</p>
                                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Review withdrawal requests</h2>
                                <p class="mt-3 text-sm text-slate-500">Open the payout queue to approve or mark merchant withdrawals as paid.</p>
                            </a>
                        </section>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/wallet';
const screen = window.__APP_CONTEXT__?.screen ?? 'wallet';
const isLoading = ref(true);
const openMenus = ref({});
const dashboard = ref({ meta: { brand: 'E-commerce' }, menu: [] });
const summary = ref({
    pending_deposits: 0,
    approved_deposits: 0,
    pending_withdrawals: 0,
    paid_withdrawals: 0,
});
const links = ref({
    deposits: '/admin/deposits',
    withdrawals: '/admin/withdrawals',
});

const statCards = computed(() => [
    { label: 'Pending Deposits', value: String(summary.value.pending_deposits ?? 0) },
    { label: 'Approved Deposits', value: String(summary.value.approved_deposits ?? 0) },
    { label: 'Pending Withdrawals', value: String(summary.value.pending_withdrawals ?? 0) },
    { label: 'Paid Withdrawals', value: String(summary.value.paid_withdrawals ?? 0) },
]);

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? [],
        };
        summary.value = response.data.summary ?? summary.value;
        links.value = response.data.links ?? links.value;
        syncOpenMenus(response.data.menu ?? []);
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
</script>
