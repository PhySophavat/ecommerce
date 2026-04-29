<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="menuScreen"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="handlePrimaryAction"
                    @refresh="loadDashboard"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="admin-frosted mb-6 rounded-[26px] px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="admin-card rounded-[30px] px-6 py-14 text-center text-sm text-slate-500">
                        Loading merchant data...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-5">
                            <article
                                v-for="card in statCards"
                                :key="card.label"
                                class="admin-card rounded-[28px] px-5 py-5"
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section v-if="screen === 'merchants'" class="space-y-6">
                            <div class="admin-card rounded-[30px] px-6 py-6">
                                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Merchant Directory</p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                            {{ dashboard.accounts?.count ?? 0 }} merchant accounts
                                        </h3>
                                    </div>
                                    <a
                                        href="/admin/merchants/pending"
                                        class="admin-primary-button rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5"
                                    >
                                        Review pending merchants
                                    </a>
                                </div>

                                <div class="mt-6 overflow-x-auto rounded-[28px] border border-[#e3e9f7] bg-[#fbfcff]">
                                    <table class="w-full min-w-[860px] text-sm">
                                        <thead class="text-left text-[11px] uppercase tracking-[0.18em] text-slate-400">
                                            <tr class="border-b border-[#e6ebf8]">
                                                <th class="px-5 py-4">Account</th>
                                                <th class="px-4 py-4">Role</th>
                                                <th class="px-4 py-4">Products</th>
                                                <th class="px-4 py-4">Pending</th>
                                                <th class="px-4 py-4">Approved</th>
                                                <th class="px-5 py-4 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="account in dashboard.accounts?.items ?? []"
                                                :key="account.id"
                                                class="border-b border-[#eef2fb] last:border-b-0"
                                            >
                                                <td class="px-5 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#F3E8F1] text-sm font-extrabold text-[#A25F88]">
                                                            {{ account.initials }}
                                                        </div>
                                                        <div class="min-w-0">
                                                            <p class="truncate font-bold text-slate-950">{{ account.name }}</p>
                                                            <p class="truncate text-sm text-slate-500">{{ account.email }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">
                                                        {{ account.role }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 font-semibold text-slate-900">{{ account.products_count }}</td>
                                                <td class="px-4 py-4 text-slate-600">{{ account.pending_products_count }}</td>
                                                <td class="px-4 py-4 text-slate-600">{{ account.approved_products_count }}</td>
                                                <td class="px-5 py-4 text-right">
                                                    <a
                                                        :href="`/admin/merchants/pending`"
                                                        class="text-sm font-semibold text-[#A25F88] hover:text-[#874a6f]"
                                                    >
                                                        Open queue
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>

                        <section v-else-if="screen === 'pending-merchants'" class="admin-card rounded-[30px] px-6 py-6">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Approval Queue</p>
                                    <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                        {{ merchants.length }} merchants waiting for approval
                                    </h3>
                                </div>
                                <a href="/admin/merchants" class="admin-secondary-button rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
                                    Back to merchants
                                </a>
                            </div>

                            <div v-if="merchants.length === 0" class="admin-muted-panel mt-6 rounded-[24px] px-5 py-6 text-sm text-slate-500">
                                No pending merchants.
                            </div>

                            <div v-else class="mt-6 space-y-4">
                                <article
                                    v-for="merchantItem in merchants"
                                    :key="merchantItem.id"
                                    class="rounded-[28px] border border-[#e3e9f7] bg-[#fbfcff] p-5"
                                >
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-xl font-bold text-slate-950">{{ merchantItem.shop_name }}</h4>
                                                <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-yellow-800">
                                                    {{ merchantItem.status }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-slate-500">{{ merchantItem.business_type }} • {{ merchantItem.user.name }} • {{ merchantItem.user.email }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ merchantItem.locationLabel }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <a
                                                :href="`/admin/merchants/${merchantItem.id}`"
                                                class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5"
                                            >
                                                View details
                                            </a>
                                            <button
                                                type="button"
                                                class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                                :disabled="processingMerchantId === merchantItem.id"
                                                @click="approveMerchant(merchantItem)"
                                            >
                                                Approve
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                                :disabled="processingMerchantId === merchantItem.id"
                                                @click="rejectMerchant(merchantItem)"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section v-else-if="screen === 'merchant-details' && merchant" class="space-y-6">
                            <div class="admin-card overflow-hidden rounded-[30px]">
                                <div
                                    class="h-56 bg-gradient-to-r from-[#1d4ed8] via-[#2563eb] to-[#7c3aed]"
                                    :style="merchant.cover_image ? `background-image:url('/storage/${merchant.cover_image}');background-size:cover;background-position:center;` : ''"
                                ></div>
                                <div class="px-6 pb-6">
                                    <div class="relative -mt-12 mb-4">
                                        <img
                                            v-if="merchant.shop_logo"
                                            :src="`/storage/${merchant.shop_logo}`"
                                            :alt="merchant.shop_name"
                                            class="h-24 w-24 rounded-full border-4 border-white object-cover"
                                        >
                                        <div
                                            v-else
                                            class="flex h-24 w-24 items-center justify-center rounded-full border-4 border-white bg-slate-200 text-4xl font-bold text-slate-500"
                                        >
                                            {{ merchant.shop_name.charAt(0) }}
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h2 class="text-3xl font-extrabold tracking-[-0.04em] text-slate-950">{{ merchant.shop_name }}</h2>
                                            <p class="mt-2 text-sm text-slate-500">Registered {{ formatDate(merchant.created_at) }}</p>
                                        </div>
                                        <div class="flex flex-wrap gap-3">
                                            <span class="rounded-full bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                                                {{ merchant.status }}
                                            </span>
                                            <span class="rounded-full bg-[#F3E8F1] px-3 py-2 text-sm font-semibold text-[#A25F88]">
                                                {{ merchant.verification_status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-6 xl:grid-cols-2">
                                <article class="admin-card rounded-[30px] px-6 py-6">
                                    <h3 class="text-lg font-bold text-slate-950">Business Information</h3>
                                    <dl class="mt-4 space-y-3 text-sm text-slate-600">
                                        <div><span class="font-semibold text-slate-900">Type:</span> {{ merchant.business_type }}</div>
                                        <div><span class="font-semibold text-slate-900">Description:</span> {{ merchant.business_description || '-' }}</div>
                                    </dl>
                                </article>

                                <article class="admin-card rounded-[30px] px-6 py-6">
                                    <h3 class="text-lg font-bold text-slate-950">Owner Information</h3>
                                    <dl class="mt-4 space-y-3 text-sm text-slate-600">
                                        <div><span class="font-semibold text-slate-900">Name:</span> {{ merchant.user.name }}</div>
                                        <div><span class="font-semibold text-slate-900">Email:</span> {{ merchant.user.email }}</div>
                                        <div><span class="font-semibold text-slate-900">Phone:</span> {{ merchant.user.phone || '-' }}</div>
                                    </dl>
                                </article>

                                <article class="admin-card rounded-[30px] px-6 py-6">
                                    <h3 class="text-lg font-bold text-slate-950">Location Information</h3>
                                    <dl class="mt-4 space-y-3 text-sm text-slate-600">
                                        <div><span class="font-semibold text-slate-900">Address:</span> {{ merchant.location?.full_address || '-' }}</div>
                                        <div><span class="font-semibold text-slate-900">Province / City:</span> {{ merchant.location?.province_city || '-' }}</div>
                                        <div><span class="font-semibold text-slate-900">District:</span> {{ merchant.location?.district || '-' }}</div>
                                        <div><span class="font-semibold text-slate-900">Commune:</span> {{ merchant.location?.commune || '-' }}</div>
                                        <div><span class="font-semibold text-slate-900">Delivery Area:</span> {{ merchant.location?.delivery_area || '-' }}</div>
                                    </dl>
                                </article>

                                <article class="admin-card rounded-[30px] px-6 py-6">
                                    <h3 class="text-lg font-bold text-slate-950">Actions</h3>
                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <button
                                            v-if="merchant.status === 'Pending'"
                                            type="button"
                                            class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                            :disabled="processingMerchantId === merchant.id"
                                            @click="approveMerchant(merchant)"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            v-if="merchant.status === 'Pending'"
                                            type="button"
                                            class="rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                            :disabled="processingMerchantId === merchant.id"
                                            @click="rejectMerchant(merchant)"
                                        >
                                            Reject
                                        </button>
                                        <button
                                            v-if="merchant.status === 'Approved'"
                                            type="button"
                                            class="rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                            :disabled="processingMerchantId === merchant.id"
                                            @click="suspendMerchant(merchant)"
                                        >
                                            Suspend
                                        </button>
                                        <button
                                            v-if="merchant.status === 'Suspended'"
                                            type="button"
                                            class="rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
                                            :disabled="processingMerchantId === merchant.id"
                                            @click="reactivateMerchant(merchant)"
                                        >
                                            Reactivate
                                        </button>
                                        <a href="/admin/merchants" class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5">
                                            Back to merchants
                                        </a>
                                    </div>

                                    <div v-if="merchant.rejection_reason" class="mt-4 rounded-[22px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700">
                                        {{ merchant.rejection_reason }}
                                    </div>
                                </article>
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

const screen = window.__APP_CONTEXT__?.screen ?? 'merchants';
const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/admin/merchants';
const processingMerchantId = ref(null);
const isLoading = ref(true);
const notice = ref(null);
const openMenus = ref({});
const dashboard = ref(initialDashboard());

const menuScreen = computed(() => 'merchants');
const stats = computed(() => dashboard.value.stats ?? {});
const merchants = computed(() => (dashboard.value.merchants ?? []).map((merchantItem) => ({
    ...merchantItem,
    locationLabel: [merchantItem.location?.province_city, merchantItem.location?.full_address].filter(Boolean).join(' • ') || 'No location provided',
})));
const merchant = computed(() => dashboard.value.merchant ?? null);
const statCards = computed(() => [
    { label: 'Total', value: String(stats.value.total ?? dashboard.value.accounts?.count ?? 0) },
    { label: 'Pending', value: String(stats.value.pending ?? 0) },
    { label: 'Approved', value: String(stats.value.approved ?? 0) },
    { label: 'Rejected', value: String(stats.value.rejected ?? 0) },
    { label: 'Suspended', value: String(stats.value.suspended ?? 0) },
]);

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            headers: {
                Accept: 'application/json',
            },
        });

        dashboard.value = {
            ...dashboard.value,
            ...response.data,
            stats: response.data.stats ?? dashboard.value.stats,
        };
        syncOpenMenus(response.data.menu ?? []);
        notice.value = null;
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to load merchant data right now.'),
        };
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

function handlePrimaryAction() {
    if (screen === 'merchants') {
        window.location.href = '/merchant/register';
        return;
    }

    if (screen === 'pending-merchants' || screen === 'merchant-details') {
        window.location.href = '/admin/merchants';
        return;
    }

    window.location.href = '/merchant/register';
}

async function approveMerchant(item) {
    await runMerchantAction(item, `/admin/merchants/${item.id}/approve`, null, 'Merchant approved successfully.');
}

async function rejectMerchant(item) {
    const reason = window.prompt('Reason for rejection (optional):', '') ?? '';
    await runMerchantAction(item, `/admin/merchants/${item.id}/reject`, { rejection_reason: reason }, 'Merchant rejected successfully.');
}

async function suspendMerchant(item) {
    await runMerchantAction(item, `/admin/merchants/${item.id}/suspend`, null, 'Merchant suspended successfully.');
}

async function reactivateMerchant(item) {
    await runMerchantAction(item, `/admin/merchants/${item.id}/reactivate`, null, 'Merchant reactivated successfully.');
}

async function runMerchantAction(item, url, payload, fallbackMessage) {
    processingMerchantId.value = item.id;

    try {
        const response = await window.axios.post(url, payload ?? {}, {
            headers: {
                Accept: 'application/json',
            },
        });

        notice.value = {
            type: 'success',
            text: response.data?.message ?? fallbackMessage,
        };

        await loadDashboard();
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to update merchant right now.'),
        };
    } finally {
        processingMerchantId.value = null;
    }
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}

function initialDashboard() {
    return {
        meta: {
            brand: 'E-commerce',
            page_title: 'Merchants',
            kicker: 'Seller management',
            subheadline: 'Manage merchant registrations and approvals.',
            links: {
                admin_users: '/admin/users',
                admin_merchants: '/admin/merchants',
                logout: '/auth/logout',
            },
        },
        menu: [],
        accounts: {
            count: 0,
            items: [],
        },
        stats: {
            total: 0,
            pending: 0,
            approved: 0,
            rejected: 0,
            suspended: 0,
        },
        merchants: [],
        merchant: null,
    };
}

function extractMessage(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const firstError = Object.values(response.errors).flat()[0];

        if (firstError) {
            return firstError;
        }
    }

    return response?.message ?? fallback;
}
</script>
