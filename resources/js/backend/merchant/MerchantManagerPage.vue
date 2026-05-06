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

                    <!-- Notice -->
                    <section
                        v-if="notice"
                        class="notice mb-5 rounded-xl px-5 py-3.5 text-sm"
                        :class="notice.type === 'error' ? 'notice-error' : 'notice-success'"
                    >
                        {{ notice.text }}
                    </section>

                    <!-- Loading -->
                    <div v-if="isLoading" class="card rounded-2xl px-6 py-14 text-center text-sm text-slate-400">
                        Loading merchant data…
                    </div>

                    <template v-else>

                        <!-- Stat cards -->
                        <section class="mb-5 grid gap-3 grid-cols-2 sm:grid-cols-3 md:grid-cols-5">
                            <article
                                v-for="card in statCards"
                                :key="card.label"
                                class="stat-card rounded-2xl px-5 py-4"
                            >
                                <p class="stat-label">{{ card.label }}</p>
                                <p class="stat-value" :class="card.valueClass">{{ card.value }}</p>
                            </article>
                        </section>

                        <!-- ── Merchants list ── -->
                        <section v-if="screen === 'merchants'" class="card rounded-2xl overflow-hidden">
                            <div class="card-header px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="kicker">Store information</p>
                                    <h3 class="card-title mt-1">{{ merchants.length }} registered stores</h3>
                                </div>
                                <a href="/admin/merchants/pending" class="btn btn-primary rounded-xl px-5 py-2.5 text-sm">
                                    Review pending merchants
                                </a>
                            </div>

                            <!-- Toolbar: search + filter -->
                            <div class="toolbar px-6 py-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search stores or owners…"
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

                            <div v-if="filteredMerchants.length === 0" class="px-6 py-10 text-sm text-slate-400 text-center">
                                No stores match your filters.
                            </div>

                            <div v-else class="table-wrap overflow-x-auto">
                                <table class="w-full min-w-[960px] text-sm">
                                    <thead>
                                        <tr class="table-head">
                                            <th class="px-6 py-3 text-left">Store</th>
                                            <th class="px-4 py-3 text-left">Owner</th>
                                            <th class="px-4 py-3 text-left">Type</th>
                                            <th class="px-4 py-3 text-left">Location</th>
                                            <th class="px-4 py-3 text-left">Status</th>
                                            <th class="px-4 py-3 text-right">Products</th>
                                            <th class="px-6 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in filteredMerchants"
                                            :key="item.id"
                                            class="table-row cursor-pointer"
                                            @click="openMerchantDetails(item)"
                                        >
                                            <td class="px-6 py-3.5">
                                                <div class="flex items-center gap-3">
                                                    <img
                                                        v-if="item.shop_logo"
                                                        :src="`/storage/${item.shop_logo}`"
                                                        :alt="item.shop_name"
                                                        class="store-logo rounded-xl object-cover"
                                                    >
                                                    <div
                                                        v-else
                                                        class="store-logo store-initial rounded-xl flex items-center justify-center font-bold text-sm"
                                                        :style="iconStyle(item.status)"
                                                    >
                                                        {{ item.shop_name.charAt(0) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="font-semibold text-slate-900 truncate max-w-[180px]">{{ item.shop_name }}</p>
                                                        <p class="text-xs text-slate-400 truncate max-w-[180px]">{{ item.business_description || 'No description' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <p class="font-medium text-slate-800 truncate max-w-[160px]">{{ item.user.name }}</p>
                                                <p class="text-xs text-slate-400 truncate max-w-[160px]">{{ item.user.email }}</p>
                                            </td>
                                            <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap">{{ item.business_type || '—' }}</td>
                                            <td class="px-4 py-3.5">
                                                <span class="text-slate-500 text-xs truncate block max-w-[140px]">{{ item.locationLabel }}</span>
                                            </td>
                                            <td class="px-4 py-3.5">
                                                <span class="badge" :class="statusClass(item.status)">{{ item.status }}</span>
                                            </td>
                                            <td class="px-4 py-3.5 text-right">
                                                <span class="font-medium text-slate-800">{{ item.products_count }}</span>
                                                <span v-if="item.pending_products_count > 0" class="text-xs text-slate-400 ml-1">/ {{ item.pending_products_count }} pending</span>
                                            </td>
                                            <td class="px-6 py-3.5 text-right">
                                                <button
                                                    class="icon-btn rounded-xl"
                                                    title="View details"
                                                    @click.stop="openMerchantDetails(item)"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-footer px-6 py-3 flex items-center justify-between">
                                <span class="text-xs text-slate-400">Showing {{ filteredMerchants.length }} of {{ merchants.length }} stores</span>
                            </div>
                        </section>

                        <!-- ── Pending approval queue ── -->
                        <section v-else-if="screen === 'pending-merchants'" class="card rounded-2xl overflow-hidden">
                            <div class="card-header px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="kicker">Approval Queue</p>
                                    <h3 class="card-title mt-1">{{ merchants.length }} merchants waiting for approval</h3>
                                </div>
                                <a href="/admin/merchants" class="btn btn-secondary rounded-xl px-5 py-2.5 text-sm">
                                    ← Back to merchants
                                </a>
                            </div>

                            <div v-if="merchants.length === 0" class="px-6 py-10 text-sm text-slate-400 text-center">
                                No pending merchants.
                            </div>

                            <div v-else class="divide-y divide-slate-100">
                                <article
                                    v-for="item in merchants"
                                    :key="item.id"
                                    class="pending-item px-6 py-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-semibold text-slate-900">{{ item.shop_name }}</h4>
                                            <span class="badge" :class="statusClass(item.status)">{{ item.status }}</span>
                                        </div>
                                        <p class="text-sm text-slate-500">{{ item.business_type }} · {{ item.user.name }} · {{ item.user.email }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ item.locationLabel }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2 shrink-0">
                                        <a :href="`/admin/merchants/${item.id}`" class="btn btn-secondary rounded-xl px-4 py-2 text-sm">View details</a>
                                        <button
                                            class="btn btn-success rounded-xl px-4 py-2 text-sm"
                                            :disabled="processingMerchantId === item.id"
                                            @click="approveMerchant(item)"
                                        >Approve</button>
                                        <button
                                            class="btn btn-danger rounded-xl px-4 py-2 text-sm"
                                            :disabled="processingMerchantId === item.id"
                                            @click="rejectMerchant(item)"
                                        >Reject</button>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <!-- ── Merchant detail ── -->
                        <section v-else-if="screen === 'merchant-details' && merchant" class="space-y-5">

                            <div class="card rounded-2xl px-6 py-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        <img
                                            v-if="merchant.shop_logo"
                                            :src="`/storage/${merchant.shop_logo}`"
                                            :alt="merchant.shop_name"
                                            class="detail-logo rounded-2xl object-cover border border-slate-100"
                                        >
                                        <div
                                            v-else
                                            class="detail-logo rounded-2xl flex items-center justify-center text-2xl font-bold border border-slate-100"
                                            style="background:#eff6ff;color:#1d4ed8;"
                                        >
                                            {{ merchant.shop_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="kicker">Store overview</p>
                                            <h2 class="text-xl font-bold text-slate-900 mt-1">{{ merchant.shop_name }}</h2>
                                            <p class="text-xs text-slate-400 mt-0.5">Registered {{ formatDate(merchant.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="badge" :class="detailStatusClass(merchant.status)">{{ merchant.status }}</span>
                                        <span
                                            v-if="merchant.verification_status && merchant.verification_status !== merchant.status"
                                            class="badge"
                                            :class="verificationStatusClass(merchant.verification_status)"
                                        >{{ merchant.verification_status }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <article class="card rounded-2xl px-6 py-5">
                                    <h3 class="info-title">Business information</h3>
                                    <dl class="mt-3 space-y-2.5 text-sm">
                                        <div class="info-row"><dt>Type</dt><dd>{{ merchant.business_type || '—' }}</dd></div>
                                        <div class="info-row"><dt>Description</dt><dd>{{ merchant.business_description || '—' }}</dd></div>
                                    </dl>
                                </article>

                                <article class="card rounded-2xl px-6 py-5">
                                    <h3 class="info-title">Owner information</h3>
                                    <dl class="mt-3 space-y-2.5 text-sm">
                                        <div class="info-row"><dt>Name</dt><dd>{{ merchant.user.name }}</dd></div>
                                        <div class="info-row"><dt>Email</dt><dd>{{ merchant.user.email }}</dd></div>
                                        <div class="info-row"><dt>Phone</dt><dd>{{ merchant.user.phone || '—' }}</dd></div>
                                    </dl>
                                </article>

                                <article class="card rounded-2xl px-6 py-5">
                                    <h3 class="info-title">Location information</h3>
                                    <dl class="mt-3 space-y-2.5 text-sm">
                                        <div class="info-row"><dt>Address</dt><dd>{{ merchant.location?.full_address || '—' }}</dd></div>
                                        <div class="info-row"><dt>Province / City</dt><dd>{{ merchant.location?.province_city || '—' }}</dd></div>
                                        <div class="info-row"><dt>District</dt><dd>{{ merchant.location?.district || '—' }}</dd></div>
                                        <div class="info-row"><dt>Commune</dt><dd>{{ merchant.location?.commune || '—' }}</dd></div>
                                        <div class="info-row"><dt>Delivery area</dt><dd>{{ merchant.location?.delivery_area || '—' }}</dd></div>
                                    </dl>
                                </article>

                                <article class="card rounded-2xl px-6 py-5">
                                    <h3 class="info-title">Merchant Balance</h3>
                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="balance-tile">
                                            <p class="balance-label">Total Balance</p>
                                            <p class="balance-value text-slate-950">{{ currency(merchant.wallet?.balance_total) }}</p>
                                        </div>
                                        <div class="balance-tile">
                                            <p class="balance-label">Available</p>
                                            <p class="balance-value text-slate-950">{{ currency(merchant.wallet?.available_balance) }}</p>
                                        </div>
                                        <div class="balance-tile">
                                            <p class="balance-label">Pending</p>
                                            <p class="balance-value text-amber-600">{{ currency(merchant.wallet?.pending_balance) }}</p>
                                        </div>
                                        <div class="balance-tile">
                                            <p class="balance-label">Deposited</p>
                                            <p class="balance-value text-emerald-600">{{ currency(merchant.wallet?.total_deposited) }}</p>
                                        </div>
                                        <div class="balance-tile">
                                            <p class="balance-label">Withdrawn</p>
                                            <p class="balance-value text-slate-950">{{ currency(merchant.wallet?.total_withdrawn) }}</p>
                                        </div>
                                        <div class="balance-tile">
                                            <p class="balance-label">Platform Fees</p>
                                            <p class="balance-value text-rose-600">{{ currency(merchant.wallet?.total_platform_fee_paid) }}</p>
                                        </div>
                                    </div>
                                </article>

                                <article class="card rounded-2xl px-6 py-5">
                                    <h3 class="info-title">Actions</h3>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button v-if="merchant.status === 'Pending'"   class="btn btn-success rounded-xl px-4 py-2 text-sm" :disabled="processingMerchantId === merchant.id" @click="approveMerchant(merchant)">Approve</button>
                                        <button v-if="merchant.status === 'Pending'"   class="btn btn-danger rounded-xl px-4 py-2 text-sm"   :disabled="processingMerchantId === merchant.id" @click="rejectMerchant(merchant)">Reject</button>
                                        <button v-if="merchant.status === 'Approved'"  class="btn btn-warn rounded-xl px-4 py-2 text-sm"     :disabled="processingMerchantId === merchant.id" @click="suspendMerchant(merchant)">Suspend</button>
                                        <button v-if="merchant.status === 'Suspended'" class="btn btn-success rounded-xl px-4 py-2 text-sm"  :disabled="processingMerchantId === merchant.id" @click="reactivateMerchant(merchant)">Reactivate</button>
                                        <a href="/admin/merchants" class="btn btn-secondary rounded-xl px-4 py-2 text-sm">← Back</a>
                                    </div>
                                    <div v-if="merchant.rejection_reason" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
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
const searchQuery = ref('');
const statusFilter = ref('');

const menuScreen = computed(() => 'merchants');
const stats = computed(() => dashboard.value.stats ?? {});

const merchants = computed(() =>
    (dashboard.value.merchants ?? []).map((m) => ({
        ...m,
        locationLabel:
            [m.location?.province_city, m.location?.full_address].filter(Boolean).join(' · ') ||
            'No location provided',
        products_count: m.products_count ?? 0,
        pending_products_count: m.pending_products_count ?? 0,
        approved_products_count: m.approved_products_count ?? 0,
    }))
);

const merchant = computed(() => dashboard.value.merchant ?? null);

const filteredMerchants = computed(() => {
    let list = merchants.value;
    if (statusFilter.value) list = list.filter((m) => m.status === statusFilter.value);
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(
            (m) =>
                m.shop_name.toLowerCase().includes(q) ||
                m.user.name.toLowerCase().includes(q) ||
                m.user.email.toLowerCase().includes(q)
        );
    }
    return list;
});

const statCards = computed(() => [
    { label: 'Total',     value: String(stats.value.total     ?? merchants.value.length), valueClass: 'val-blue' },
    { label: 'Pending',   value: String(stats.value.pending   ?? 0), valueClass: 'val-amber' },
    { label: 'Approved',  value: String(stats.value.approved  ?? 0), valueClass: 'val-green' },
    { label: 'Rejected',  value: String(stats.value.rejected  ?? 0), valueClass: 'val-red' },
    { label: 'Suspended', value: String(stats.value.suspended ?? 0), valueClass: 'val-gray' },
]);

onMounted(async () => { await loadDashboard(); });

async function loadDashboard() {
    isLoading.value = true;
    try {
        const response = await window.axios.get(endpoint, { headers: { Accept: 'application/json' } });
        dashboard.value = { ...dashboard.value, ...response.data, stats: response.data.stats ?? dashboard.value.stats };
        syncOpenMenus(response.data.menu ?? []);
        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load merchant data right now.') };
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

function openMerchantDetails(item) {
    window.location.href = `/admin/merchants/${item.id}`;
}

function handlePrimaryAction() {
    if (screen === 'merchants') { window.location.href = '/merchant/register'; return; }
    if (screen === 'pending-merchants' || screen === 'merchant-details') { window.location.href = '/admin/merchants'; return; }
    window.location.href = '/merchant/register';
}

async function approveMerchant(item) {
    await runAction(item, `/admin/merchants/${item.id}/approve`, null, 'Merchant approved successfully.');
}

async function rejectMerchant(item) {
    const reason = window.prompt('Reason for rejection (optional):', '') ?? '';
    await runAction(item, `/admin/merchants/${item.id}/reject`, { rejection_reason: reason }, 'Merchant rejected successfully.');
}

async function suspendMerchant(item) {
    await runAction(item, `/admin/merchants/${item.id}/suspend`, null, 'Merchant suspended successfully.');
}

async function reactivateMerchant(item) {
    await runAction(item, `/admin/merchants/${item.id}/reactivate`, null, 'Merchant reactivated successfully.');
}

async function runAction(item, url, payload, fallback) {
    processingMerchantId.value = item.id;
    try {
        const response = await window.axios.post(url, payload ?? {}, { headers: { Accept: 'application/json' } });
        notice.value = { type: 'success', text: response.data?.message ?? fallback };
        await loadDashboard();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to update merchant right now.') };
    } finally {
        processingMerchantId.value = null;
    }
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function statusClass(status) {
    return { Pending: 'badge-pending', Approved: 'badge-approved', Rejected: 'badge-rejected', Suspended: 'badge-suspended' }[status] ?? 'badge-default';
}

function detailStatusClass(status) { return statusClass(status); }

function verificationStatusClass(status) {
    return { Verified: 'badge-verified', Pending: 'badge-pending', 'Not Verified': 'badge-rejected' }[status] ?? 'badge-default';
}

function iconStyle(status) {
    return {
        Pending:   { background: '#fef3c7', color: '#92400e' },
        Approved:  { background: '#d1fae5', color: '#065f46' },
        Rejected:  { background: '#fee2e2', color: '#991b1b' },
        Suspended: { background: '#f1f5f9', color: '#475569' },
    }[status] ?? { background: '#eff6ff', color: '#1e40af' };
}

function currency(value) {
    const amount = Number.parseFloat(String(value ?? '0'));
    return `$${Number.isNaN(amount) ? '0.00' : amount.toFixed(2)}`;
}

function initialDashboard() {
    return {
        meta: { brand: 'E-commerce', page_title: 'Merchants', kicker: 'Seller management', subheadline: 'Manage merchant registrations and approvals.' },
        menu: [],
        accounts: { count: 0, items: [] },
        stats: { total: 0, pending: 0, approved: 0, rejected: 0, suspended: 0 },
        merchants: [],
        merchant: null,
    };
}

function extractMessage(error, fallback) {
    const data = error?.response?.data;
    if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
    }
    return data?.message ?? fallback;
}
</script>

<style scoped>
/* ── Stat cards ── */
.stat-card { background: #fff; border: 0.5px solid #e2e8f0; }
.stat-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
.stat-value { font-size: 24px; font-weight: 700; }
.val-blue  { color: #2563eb; }
.val-amber { color: #d97706; }
.val-green { color: #059669; }
.val-red   { color: #dc2626; }
.val-gray  { color: #64748b; }

/* ── Card ── */
.card { background: #fff; border: 0.5px solid #e2e8f0; }
.card-header { border-bottom: 0.5px solid #e2e8f0; }
.kicker { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
.card-title { font-size: 18px; font-weight: 700; color: #0f172a; }

/* ── Notice ── */
.notice { border: 0.5px solid transparent; }
.notice-success { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
.notice-error   { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }

/* ── Buttons ── */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    font-weight: 500; cursor: pointer; border: 0.5px solid #cbd5e1;
    background: #fff; color: #0f172a; text-decoration: none;
    transition: background .1s, opacity .1s; white-space: nowrap;
}
.btn:hover    { background: #f8fafc; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-primary  { background: #0f172a; color: #fff; border-color: #0f172a; }
.btn-primary:hover  { background: #1e293b; }
.btn-secondary { background: #fff; }
.btn-success  { background: #059669; color: #fff; border-color: #059669; }
.btn-success:hover  { background: #047857; }
.btn-danger   { background: #dc2626; color: #fff; border-color: #dc2626; }
.btn-danger:hover   { background: #b91c1c; }
.btn-warn     { background: #d97706; color: #fff; border-color: #d97706; }
.btn-warn:hover     { background: #b45309; }

/* ── Toolbar ── */
.toolbar { border-bottom: 0.5px solid #e2e8f0; background: #fafafa; }
.search-input {
    border: 0.5px solid #cbd5e1; background: #fff; color: #0f172a;
    outline: none; font-size: 13px; padding: 7px 12px;
}
.search-input:focus { border-color: #94a3b8; }
.filter-select {
    border: 0.5px solid #cbd5e1; background: #fff; color: #0f172a;
    outline: none; font-size: 13px; padding: 7px 10px; cursor: pointer;
}

/* ── Table ── */
.table-wrap { border-top: 0.5px solid #e2e8f0; }
.table-head { background: #f8fafc; }
.table-head th {
    font-size: 11px; font-weight: 500; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .07em;
    border-bottom: 0.5px solid #e2e8f0; white-space: nowrap;
}
.table-row { border-bottom: 0.5px solid #f1f5f9; transition: background .1s; }
.table-row:hover { background: #f8fafc; }
.table-row:last-child { border-bottom: none; }

.store-logo { width: 36px; height: 36px; flex-shrink: 0; }
.store-initial { font-size: 13px; }

/* ── Badges ── */
.badge { display: inline-block; font-size: 11px; font-weight: 500; padding: 2px 9px; border-radius: 20px; white-space: nowrap; }
.badge-pending   { background: #fef3c7; color: #92400e; }
.badge-approved  { background: #d1fae5; color: #065f46; }
.badge-rejected  { background: #fee2e2; color: #991b1b; }
.badge-suspended { background: #f1f5f9; color: #475569; }
.badge-verified  { background: #dbeafe; color: #1e40af; }
.badge-default   { background: #f1f5f9; color: #64748b; }

/* ── Icon button ── */
.icon-btn {
    width: 30px; height: 30px; border: 0.5px solid #e2e8f0;
    background: #fff; display: inline-flex; align-items: center;
    justify-content: center; cursor: pointer; color: #64748b;
    transition: background .1s, color .1s;
}
.icon-btn:hover { background: #f1f5f9; color: #0f172a; }

/* ── Table footer ── */
.table-footer { border-top: 0.5px solid #e2e8f0; background: #fafafa; }

/* ── Pending items ── */
.pending-item { transition: background .1s; }
.pending-item:hover { background: #fafafa; }

/* ── Detail view ── */
.detail-logo { width: 60px; height: 60px; flex-shrink: 0; }
.info-title { font-size: 14px; font-weight: 600; color: #0f172a; border-bottom: 0.5px solid #f1f5f9; padding-bottom: 10px; }
.info-row { display: flex; gap: 8px; font-size: 13px; }
.info-row dt { color: #64748b; min-width: 120px; flex-shrink: 0; }
.info-row dd { color: #0f172a; }
.balance-tile { border: 0.5px solid #e2e8f0; background: #f8fafc; border-radius: 16px; padding: 14px 16px; }
.balance-label { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; }
.balance-value { margin-top: 8px; font-size: 24px; font-weight: 700; }
</style>
