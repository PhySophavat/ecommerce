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
                    @primary-action="openCreateModal"
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
                        Loading merchant bank accounts...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-5">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="card overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-sm">
                            <div class="card-header flex flex-col gap-4 px-6 py-6 lg:flex-row lg:items-end lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Approval queue</p>
                                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Merchant payout accounts</h2>
                                    <p class="mt-2 text-sm text-slate-500">Approve, reject, disable, or remove merchant payout accounts before they can be used on the withdrawal page.</p>
                                </div>

                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    <button
                                        v-for="filter in filters"
                                        :key="filter.value"
                                        type="button"
                                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                        :class="selectedStatus === filter.value ? 'bg-[#A25F88] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        @click="changeStatus(filter.value)"
                                    >
                                        {{ filter.label }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="accounts.length === 0" class="mt-6 rounded-[24px] border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-5 py-10 text-center text-sm text-slate-500">
                                No merchant bank accounts match this filter.
                            </div>

                            <div v-else class="table-wrap overflow-x-auto">
                                <table class="w-full min-w-[1120px] text-sm">
                                    <thead>
                                        <tr class="table-head text-left">
                                            <th class="w-[24%] px-5 py-4">Merchant</th>
                                            <th class="w-[28%] px-4 py-4">Payout account</th>
                                            <th class="w-[8%] px-4 py-4">Currency</th>
                                            <th class="w-[11%] px-4 py-4">Type</th>
                                            <th class="w-[10%] px-4 py-4">Status</th>
                                            <th class="w-[8%] px-4 py-4">Default</th>
                                            <th class="w-[11%] px-4 py-4">Submitted</th>
                                            <th class="w-[20%] px-5 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in accounts" :key="item.id" class="table-row align-top">
                                            <td class="px-5 py-4">
                                                <p class="font-bold text-slate-950">{{ item.merchant?.shop_name }}</p>
                                                <p class="text-slate-500">{{ item.merchant?.owner_name }} • {{ item.merchant?.email }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p class="font-semibold text-slate-900">{{ item.bank_name }}</p>
                                                <p>{{ item.account_holder_name }} • {{ item.account_number }}</p>
                                                <p class="mt-1 text-xs">{{ item.phone_number }}</p>
                                                <p v-if="item.reject_reason" class="mt-2 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ item.reject_reason }}</p>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap font-semibold text-slate-900">{{ item.currency }}</td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <span class="leading-6">{{ item.account_type === 'khqr' ? 'KHQR' : 'Bank Account' }}</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="badge" :class="statusClass(item.status)">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span v-if="item.is_default" class="badge badge-accent">Default</span>
                                                <span v-else class="text-slate-400">-</span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-slate-600">{{ formatDate(item.created_at) }}</td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex flex-nowrap justify-end gap-2 whitespace-nowrap">
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="rounded-full bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'approve')"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="rounded-full bg-rose-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-500"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'reject')"
                                                    >
                                                        Reject
                                                    </button>
                                                    <button
                                                        v-if="item.status === 'approved'"
                                                        type="button"
                                                        class="rounded-full bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'disable')"
                                                    >
                                                        Disable
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-full bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                                                        :disabled="processingId === item.id"
                                                        @click="runDelete(item)"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="table-footer px-6 py-3 text-sm text-slate-400">
                                    Showing {{ accounts.length }} bank account{{ accounts.length === 1 ? '' : 's' }}
                                </div>
                            </div>
                        </section>
                    </template>
                </main>
            </div>
        </div>

        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 px-4 py-6"
            @click.self="closeCreateModal"
        >
            <section class="w-full max-w-2xl rounded-[30px] border border-slate-200 bg-white p-6 shadow-2xl sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">New account</p>
                        <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Create bank account</h2>
                        <p class="mt-2 text-sm text-slate-500">Create a merchant payout account directly from the admin approval screen.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-2xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                        @click="closeCreateModal"
                    >
                        Close
                    </button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submitCreateForm">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Merchant</span>
                        <select v-model="createForm.merchant_id" class="field-input">
                            <option value="">Select merchant</option>
                            <option v-for="merchant in merchantOptions" :key="merchant.id" :value="String(merchant.id)">{{ merchant.label }}</option>
                        </select>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Bank name</span>
                            <select v-model="createForm.bank_name" class="field-input">
                                <option value="">Select bank</option>
                                <option v-for="option in bankOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Currency</span>
                            <select v-model="createForm.currency" class="field-input">
                                <option v-for="option in currencyOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                    </div>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Account holder name</span>
                        <input v-model="createForm.account_holder_name" type="text" class="field-input" placeholder="Merchant owner or business name">
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Account number</span>
                            <input v-model="createForm.account_number" type="text" class="field-input" placeholder="Enter account number">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700">Phone number</span>
                            <input v-model="createForm.phone_number" type="text" class="field-input" placeholder="088 123 4567">
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-[#4f5de4] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isCreating"
                    >
                        {{ isCreating ? 'Creating...' : 'Create bank account' }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/bank-accounts';
const screen = window.__APP_CONTEXT__?.screen ?? 'bank-accounts';
const isLoading = ref(true);
const notice = ref(null);
const processingId = ref(null);
const selectedStatus = ref('all');
const openMenus = ref({});
const showCreateModal = ref(false);
const isCreating = ref(false);
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Bank Accounts',
        kicker: 'Merchant payouts',
        subheadline: 'Review merchant payout accounts.',
        primary_action_label: 'New bank account',
        links: {},
    },
    menu: [],
});
const filters = ref([]);
const accounts = ref([]);
const summary = ref({ all: 0, pending: 0, approved: 0, rejected: 0, disabled: 0 });
const merchantOptions = ref([]);
const bankOptions = ref([]);
const currencyOptions = ref([]);
const createForm = reactive(defaultCreateForm());

const statCards = computed(() => [
    { label: 'All', value: String(summary.value.all ?? 0) },
    { label: 'Pending', value: String(summary.value.pending ?? 0) },
    { label: 'Approved', value: String(summary.value.approved ?? 0) },
    { label: 'Rejected', value: String(summary.value.rejected ?? 0) },
    { label: 'Disabled', value: String(summary.value.disabled ?? 0) },
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
        accounts.value = response.data.accounts ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: {
                ...dashboard.value.meta,
                ...(response.data.meta ?? {}),
            },
            menu: response.data.menu ?? dashboard.value.menu,
        };
        merchantOptions.value = response.data.form?.merchants ?? [];
        bankOptions.value = response.data.form?.bank_options ?? [];
        currencyOptions.value = response.data.form?.currencies ?? [];
        if (!createForm.bank_name) {
            createForm.bank_name = bankOptions.value[0]?.value ?? '';
        }
        if (!createForm.currency) {
            createForm.currency = currencyOptions.value[0]?.value ?? 'USD';
        }
        syncOpenMenus(response.data.menu ?? []);
    } catch (error) {
        showError(error, 'Unable to load merchant bank accounts.');
    } finally {
        isLoading.value = false;
    }
}

async function changeStatus(status) {
    selectedStatus.value = status;
    await loadDashboard();
}

async function runAction(item, action) {
    const rejectReason = action === 'approve'
        ? ''
        : (window.prompt(`Optional reason for ${action}:`, item.reject_reason ?? '') ?? '');

    processingId.value = item.id;

    try {
        const response = await window.axios.put(`/api/admin/bank-accounts/${item.id}/${action}`, {
            reject_reason: rejectReason,
        });
        notice.value = { type: 'success', text: response.data.message ?? 'Bank account updated.' };
        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to update bank account.');
    } finally {
        processingId.value = null;
    }
}

async function runDelete(item) {
    if (!window.confirm(`Delete ${item.bank_name} for ${item.merchant?.shop_name}?`)) {
        return;
    }

    processingId.value = item.id;

    try {
        const response = await window.axios.delete(`/api/admin/bank-accounts/${item.id}`);
        notice.value = { type: 'success', text: response.data.message ?? 'Bank account deleted.' };
        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to delete bank account.');
    } finally {
        processingId.value = null;
    }
}

function openCreateModal() {
    resetCreateForm();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

async function submitCreateForm() {
    isCreating.value = true;

    try {
        const response = await window.axios.post('/api/admin/bank-accounts', {
            merchant_id: Number.parseInt(createForm.merchant_id, 10),
            bank_name: createForm.bank_name,
            account_holder_name: createForm.account_holder_name,
            account_number: createForm.account_number,
            phone_number: createForm.phone_number,
            currency: createForm.currency,
            account_type: 'bank_account',
            khqr_code: '',
            is_default: false,
        });
        notice.value = { type: 'success', text: response.data.message ?? 'Bank account created.' };
        closeCreateModal();
        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to create bank account.');
    } finally {
        isCreating.value = false;
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

    if (response?.errors) {
        const first = Object.values(response.errors).flat()[0];
        notice.value = { type: 'error', text: first ?? fallback };
        return;
    }

    notice.value = { type: 'error', text: response?.message ?? fallback };
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(value));
}

function statusClass(status) {
    return {
        pending: 'badge-pending',
        approved: 'badge-approved',
        rejected: 'badge-rejected',
        disabled: 'badge-disabled',
    }[status] ?? 'badge-default';
}

function resetCreateForm() {
    Object.assign(createForm, defaultCreateForm(), {
        bank_name: bankOptions.value[0]?.value ?? '',
        currency: currencyOptions.value[0]?.value ?? 'USD',
    });
}

function defaultCreateForm() {
    return {
        merchant_id: '',
        bank_name: '',
        account_holder_name: '',
        account_number: '',
        phone_number: '',
        currency: 'USD',
    };
}
</script>

<style scoped>
.card {
    background: #fff;
}

.card-header {
    border-bottom: 0.5px solid #f1f5f9;
}

.table-wrap {
    border-top: 0.5px solid #e2e8f0;
}

.table-head {
    background: #f8fafc;
}

.table-head th {
    border-bottom: 0.5px solid #e2e8f0;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
}

.table-row {
    border-bottom: 0.5px solid #f1f5f9;
    transition: background 0.1s;
}

.table-row:hover {
    background: #f8fafc;
}

.table-row:last-child {
    border-bottom: none;
}

.badge {
    display: inline-block;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    padding: 0.35rem 0.8rem;
    text-transform: uppercase;
    white-space: nowrap;
}

.badge-pending {
    background: #fef3c7;
    color: #92400e;
}

.badge-approved {
    background: #d1fae5;
    color: #065f46;
}

.badge-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.badge-disabled,
.badge-default {
    background: #f1f5f9;
    color: #475569;
}

.badge-accent {
    background: #a25f88;
    color: #fff;
}

.table-footer {
    border-top: 0.5px solid #e2e8f0;
    background: #fafafa;
}

.field-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 0.85rem 1rem;
    font-size: 0.95rem;
    color: #0f172a;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.field-input:focus {
    border-color: #4f5de4;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(79, 93, 228, 0.14);
}
</style>
