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
               

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-[26px] border px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading withdrawals...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-4 xl:grid-cols-5">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="withdraw-card overflow-hidden rounded-2xl">
                            <div class="withdraw-card-header px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="withdraw-kicker">Payout queue</p>
                                    <h2 class="withdraw-title mt-1">Merchant withdrawal requests</h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="filter in filters"
                                        :key="filter.value"
                                        type="button"
                                        class="withdraw-filter rounded-xl px-4 py-2 text-sm"
                                        :class="selectedStatus === filter.value ? 'withdraw-filter-active' : 'withdraw-filter-idle'"
                                        @click="changeStatus(filter.value)"
                                    >
                                        {{ filter.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="withdraw-toolbar border-b border-slate-200 px-6 py-3">
                                <p class="text-sm text-slate-500">Review merchant payout requests and update their status from the table below.</p>
                            </div>

                            <div class="mt-6 flex justify-center px-6">
                                 

                                    <section class="w-full xl:w-[80%] rounded-2xl border border-slate-200 bg-white p-5">
                                     

                                        <div class="mt-6 space-y-5">
                                            <label class="block">
                                                <span class="mb-2 block text-sm font-semibold text-slate-700">Currency</span>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border px-4 py-3 text-left transition"
                                                        :class="actionForm.currency === 'USD'
                                                            ? 'border-[#A25F88] bg-[#A25F88] text-white shadow-sm'
                                                            : 'border-slate-200 bg-slate-50 text-slate-900 hover:border-[#A25F88]/40 hover:bg-white'"
                                                        @click="actionForm.currency = 'USD'; normalizeActionAmount()"
                                                    >
                                                        <p class="text-sm font-bold">USD</p>
                                                        <p class="mt-1 text-xs" :class="actionForm.currency === 'USD' ? 'text-white/80' : 'text-slate-500'">Dollar $</p>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border px-4 py-3 text-left transition"
                                                        :class="actionForm.currency === 'KHR'
                                                            ? 'border-[#A25F88] bg-[#A25F88] text-white shadow-sm'
                                                            : 'border-slate-200 bg-slate-50 text-slate-900 hover:border-[#A25F88]/40 hover:bg-white'"
                                                        @click="actionForm.currency = 'KHR'; normalizeActionAmount()"
                                                    >
                                                        <p class="text-sm font-bold">KHR</p>
                                                        <p class="mt-1 text-xs" :class="actionForm.currency === 'KHR' ? 'text-white/80' : 'text-slate-500'">Khmer Riel ៛</p>
                                                    </button>
                                                </div>
                                            </label>

                                            <label class="block">
                                                <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                                                <div class="relative">
                                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                                                        {{ actionForm.currency === 'KHR' ? '៛' : '$' }}
                                                    </span>
                                                    <input
                                                        v-model="actionForm.amount"
                                                        type="number"
                                                        :step="actionForm.currency === 'KHR' ? '1' : '0.01'"
                                                        min="0"
                                                        :inputmode="actionForm.currency === 'KHR' ? 'numeric' : 'decimal'"
                                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-900 outline-none"
                                                        :placeholder="actionForm.currency === 'KHR' ? 'KHR' : 'USD'"
                                                        @input="normalizeActionAmount"
                                                    >
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">
                                                    {{ actionForm.currency === 'KHR' ? 'KHR accepts whole numbers ' : 'USD accepts decimal amounts like 10.50.' }}
                                                </p>
                                            </label>

                                            <label v-if="isMerchantUser" class="block">
                                                <span class="mb-2 block text-sm font-semibold text-slate-700">Bank Account</span>
                                                <div v-if="merchantBankAccounts.length === 0" class="mb-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-700">
                                                    No bank account yet. Create one below and it will be selected automatically.
                                                </div>
                                                <select
                                                    v-model="merchantForm.bank_account_id"
                                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none"
                                                    :disabled="merchantBankAccounts.length === 0"
                                                >
                                                    <option value="">Select account</option>
                                                    <option v-for="account in merchantBankAccounts" :key="account.id" :value="String(account.id)">
                                                        {{ account.label }}
                                                    </option>
                                                </select>
                                            </label>

                                          

                                            <div v-if="isMerchantUser" class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 sm:grid-cols-3">
                                                <div>
                                                    <p class="font-semibold text-slate-900">Available to withdraw</p>
                                                    <p class="mt-1">{{ currency(merchantBalances.available_to_withdraw, actionForm.currency) }}</p>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-900">Available balance</p>
                                                    <p class="mt-1">{{ currency(merchantBalances.available_balance, actionForm.currency) }}</p>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-900">Pending balance</p>
                                                    <p class="mt-1">{{ currency(merchantBalances.pending_balance, actionForm.currency) }}</p>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-dashed border-[#A25F88] bg-[#fff7fb] hover:bg-[#D4A5C1] px-4 py-4 text-sm text-slate-600">
                                                <button class="font-semibold text-oklch(40.8% 0.153 2.432)">Withdrawal request:</button>
                                                <span v-if="isMerchantUser" class="ml-2">Submit the request and it will be added to the table below immediately.</span>
                                                <span v-else class="ml-2">Review merchant withdrawal requests in the table below.</span>
                                            </div>

                                            <p v-if="isMerchantUser" class="text-sm" :class="merchantCanSubmit ? 'text-emerald-700' : 'text-rose-600'">
                                                {{ merchantSubmitHint }}
                                            </p>

                                            <button
                                                v-if="isMerchantUser"
                                                type="button"
                                                class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                                :disabled="processingId === 'merchant-submit' || !merchantCanSubmit"
                                                @click="submitMerchantWithdrawal"
                                            >
                                                {{ processingId === 'merchant-submit' ? 'Submitting...' : 'Submit Withdrawal Request' }}
                                            </button>
                                        </div>
                                    </section>
                                </div>

                            <!-- </div> -->

                            <div v-if="false" class="mx-6 my-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                                No withdrawals match this filter.
                            </div>

                            <div class="withdraw-table-wrap mt-5 overflow-x-auto">
                                <table class="min-w-[1160px] w-full text-sm">
                                    <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                        <tr class="withdraw-table-head">
                                            <th class="px-5 py-4">Merchant</th>
                                            <th class="px-4 py-4">Currency</th>
                                            <th class="px-4 py-4">Amount</th>
                                            <th class="px-4 py-4">Fee</th>
                                            <th class="px-4 py-4">Bank Account</th>
                                            <th class="px-4 py-4">Status</th>
                                            <th class="px-4 py-4">Created</th>
                                            <th class="px-5 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="withdrawals.length === 0">
                                            <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">
                                                No withdrawals match this filter.
                                            </td>
                                        </tr>
                                        <tr v-for="item in withdrawals" :key="item.id" class="withdraw-table-row">
                                            <td class="px-5 py-4">
                                                <p class="font-bold text-slate-950">{{ item.merchant?.shop_name }}</p>
                                                <p class="text-slate-500">{{ item.merchant?.owner_name }} • {{ item.merchant?.email }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <p class="font-bold text-slate-950">{{ item.currency }}</p>
                                                <p class="text-slate-500">{{ currencyLabel(item.currency) }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <p class="font-bold text-slate-950">{{ currency(item.amount, item.currency) }}</p>
                                                <p class="text-slate-500">Net {{ currency(item.net_amount, item.currency) }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <p class="font-semibold text-slate-900">{{ currency(item.fee_amount, item.currency) }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p class="font-semibold text-slate-900">{{ item.bank_account?.bank_name }}</p>
                                                <p>{{ item.bank_account?.account_holder_name }} • {{ item.bank_account?.account_number }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="withdraw-badge" :class="statusClass(item.status)">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p>{{ formatDate(item.created_at) }}</p>
                                                <p class="mt-1 text-xs text-slate-400">{{ formatTime(item.created_at) }}</p>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="action-button action-button-approve"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'approve')"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="action-button action-button-reject"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'reject')"
                                                    >
                                                        Reject
                                                    </button>
                                                    <button
                                                        v-if="item.status === 'approved'"
                                                        type="button"
                                                        class="action-button action-button-paid"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'mark-paid')"
                                                    >
                                                        Mark Paid
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="withdraw-table-footer px-6 py-3">
                                <span class="text-xs text-slate-400">{{ withdrawals.length > 0 ? `Showing ${withdrawals.length} withdrawal requests` : 'No withdrawal requests yet' }}</span>
                            </div>
                        </section>
                    </template>

                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/withdrawals';
const screen = window.__APP_CONTEXT__?.screen ?? 'withdrawals';
const refreshIntervalMs = 5000;
const isLoading = ref(true);
const notice = ref(null);
const processingId = ref(null);
const selectedStatus = ref('all');
const openMenus = ref({});
const refreshTimerId = ref(null);
const currentUser = ref(window.__APP_CONTEXT__?.currentUser ?? null);
const merchantBankAccounts = ref([]);
const merchantBankOptions = ref([]);
const merchantBalances = ref({
    available_to_withdraw: '0.00',
    available_balance: '0.00',
    pending_balance: '0.00',
});
const merchantMinimumAmount = ref('10.00');
const merchantWithdrawFee = ref('0.00');
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Withdrawals',
        kicker: 'Merchant payouts',
        subheadline: 'Review merchant withdrawal requests.',
        links: {
            frontend: '/',
            admin_users: '/admin/users',
            admin_merchants: '/admin/merchants',
            admin_withdrawals: '/admin/withdrawals',
            logout: '/auth/logout',
        },
    },
    menu: [],
});
const filters = ref([]);
const withdrawals = ref([]);
const actionPanel = ref(null);
const summary = ref({
    all: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
    paid: 0,
});
const actionForm = reactive({
    action: '',
    note: '',
    item: null,
    currency: 'USD',
    amount: '',
    bank_label: '',
    bank_detail: '',
});
const merchantForm = reactive({
    bank_account_id: '',
    note: '',
});
const bankAccountForm = reactive({
    bank_name: '',
    account_name: '',
    account_number: '',
    account_type: 'bank',
});

const statCards = computed(() => [
    { label: 'All', value: String(summary.value.all ?? 0) },
    { label: 'Pending', value: String(summary.value.pending ?? 0) },
    { label: 'Approved', value: String(summary.value.approved ?? 0) },
    { label: 'Rejected', value: String(summary.value.rejected ?? 0) },
    { label: 'Paid', value: String(summary.value.paid ?? 0) },
]);
const actionTitle = computed(() => ({
    approve: 'Approve withdrawal',
    reject: 'Reject withdrawal',
    'mark-paid': 'Mark withdrawal as paid',
}[actionForm.action] ?? 'Update withdrawal'));
const actionButtonLabel = computed(() => ({
    approve: 'Approve',
    reject: 'Reject',
    'mark-paid': 'Mark Paid',
}[actionForm.action] ?? 'Submit'));
const actionPlaceholder = computed(() => ({
    approve: 'Optional note for approval.',
    reject: 'Reason for rejection.',
    'mark-paid': 'Optional transfer reference or payout note.',
}[actionForm.action] ?? 'Optional note.'));
const isMerchantUser = computed(() => currentUser.value?.role === 'merchant');
const merchantCanSubmit = computed(() => {
    const amount = Number.parseFloat(actionForm.amount || '0');
    const availableToWithdraw = Number.parseFloat(merchantBalances.value.available_to_withdraw || '0');
    const minimumAmount = Number.parseFloat(merchantMinimumAmount.value || '0');

    return isMerchantUser.value
        && merchantForm.bank_account_id !== ''
        && !Number.isNaN(amount)
        && amount > 0
        && amount >= minimumAmount
        && amount <= availableToWithdraw;
});
const merchantSubmitHint = computed(() => {
    if (!isMerchantUser.value) {
        return '';
    }

    const amount = Number.parseFloat(actionForm.amount || '0');
    const availableToWithdraw = Number.parseFloat(merchantBalances.value.available_to_withdraw || '0');
    const minimumAmount = Number.parseFloat(merchantMinimumAmount.value || '0');

    if (merchantForm.bank_account_id === '') {
        return 'Select an approved bank account first.';
    }

    if (availableToWithdraw <= 0) {
        return 'No available balance to withdraw yet. Add wallet balance first, then submit the request.';
    }

    if (Number.isNaN(amount) || amount <= 0) {
        return `Enter a withdrawal amount of at least ${currency(minimumAmount, actionForm.currency)}.`;
    }

    if (amount < minimumAmount) {
        return `Minimum withdrawal amount is ${currency(minimumAmount, actionForm.currency)}.`;
    }

    if (amount > availableToWithdraw) {
        return `Withdrawal amount exceeds available balance of ${currency(availableToWithdraw, actionForm.currency)}.`;
    }

    return `This request will be added to the table below immediately after submission. Fee: ${currency(merchantWithdrawFee.value, actionForm.currency)}.`;
});
const canCreateBankAccount = computed(() => {
    return bankAccountForm.bank_name.trim() !== ''
        && bankAccountForm.account_name.trim() !== ''
        && bankAccountForm.account_number.trim() !== '';
});

onMounted(async () => {
    await loadInitialState();
    startAutoRefresh();
    window.addEventListener('focus', handleWindowFocus);
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    stopAutoRefresh();
    window.removeEventListener('focus', handleWindowFocus);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await fetchDashboard();

        summary.value = response.data.summary ?? summary.value;
        filters.value = response.data.filters ?? [];
        selectedStatus.value = response.data.selected_status ?? selectedStatus.value;
        withdrawals.value = response.data.withdrawals ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
    } catch (error) {
        showError(error, 'Unable to load withdrawals.');
    } finally {
        isLoading.value = false;
    }
}

async function loadInitialState() {
    await loadDashboard();

    if (isMerchantUser.value) {
        await loadMerchantWithdrawalForm();
    }
}

async function fetchDashboard() {
    return window.axios.get(endpoint, {
        params: {
            status: selectedStatus.value,
        },
    });
}

async function changeStatus(status) {
    selectedStatus.value = status;
    await loadDashboard();
}

function startAutoRefresh() {
    stopAutoRefresh();
    refreshTimerId.value = window.setInterval(() => {
        refreshQuietly();
    }, refreshIntervalMs);
}

function stopAutoRefresh() {
    if (refreshTimerId.value !== null) {
        window.clearInterval(refreshTimerId.value);
        refreshTimerId.value = null;
    }
}

async function refreshQuietly() {
    if (document.hidden || isLoading.value || processingId.value !== null) {
        return;
    }

    try {
        const response = await fetchDashboard();

        summary.value = response.data.summary ?? summary.value;
        filters.value = response.data.filters ?? filters.value;
        selectedStatus.value = response.data.selected_status ?? selectedStatus.value;
        withdrawals.value = response.data.withdrawals ?? withdrawals.value;
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? dashboard.value.menu ?? []);

        if (isMerchantUser.value) {
            await loadMerchantWithdrawalForm();
        }
    } catch {
        // Keep the current table state if a background refresh fails.
    }
}

async function loadMerchantWithdrawalForm() {
    const [withdrawalResponse, accountResponse] = await Promise.all([
        window.axios.get('/api/merchant/withdrawals'),
        window.axios.get('/api/merchant/bank-accounts'),
    ]);

    merchantBalances.value = withdrawalResponse.data.balances ?? merchantBalances.value;
    merchantMinimumAmount.value = withdrawalResponse.data.minimum_amount ?? merchantMinimumAmount.value;
    merchantWithdrawFee.value = withdrawalResponse.data.withdraw_fee ?? merchantWithdrawFee.value;
    merchantBankOptions.value = accountResponse.data.meta?.bank_options ?? merchantBankOptions.value;
    merchantBankAccounts.value = withdrawalResponse.data.bank_accounts ?? [];

    if (merchantBankAccounts.value.length === 0) {
        merchantBankAccounts.value = (accountResponse.data.accounts ?? [])
            .filter((account) => account.status === 'approved')
            .map((account) => ({
                id: account.id,
                label: `${account.bank_name} - ${account.account_holder_name} (${account.account_number ?? `****${account.account_number_last4 ?? ''}`})`,
                is_default: Boolean(account.is_default),
            }));
    }

    if (!merchantForm.bank_account_id) {
        const defaultAccount = merchantBankAccounts.value.find((account) => account.is_default) ?? merchantBankAccounts.value[0];
        merchantForm.bank_account_id = defaultAccount ? String(defaultAccount.id) : '';
    }

    if (!bankAccountForm.bank_name) {
        bankAccountForm.bank_name = merchantBankOptions.value[0]?.value ?? '';
    }
}

function handleWindowFocus() {
    refreshQuietly();
}

function handleVisibilityChange() {
    if (!document.hidden) {
        refreshQuietly();
    }
}

function runAction(item, action) {
    actionForm.item = item;
    actionForm.action = action;
    actionForm.note = item.note ?? '';
    actionForm.currency = item.currency ?? 'USD';
    actionForm.amount = item.amount ?? '';
    actionForm.bank_label = item.bank_account?.bank_name ?? '';
    actionForm.bank_detail = [item.bank_account?.account_holder_name, item.bank_account?.account_number].filter(Boolean).join(' • ');
    nextTick(() => {
        actionPanel.value?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
}

async function submitMerchantWithdrawal() {
    if (!merchantCanSubmit.value) {
        notice.value = {
            type: 'error',
            text: 'Select a bank account and enter a valid amount first.',
        };
        return;
    }

    processingId.value = 'merchant-submit';

    try {
        const response = await window.axios.post('/api/merchant/withdrawals', {
            bank_account_id: Number.parseInt(merchantForm.bank_account_id, 10),
            amount: actionForm.amount,
            currency: actionForm.currency,
            note: merchantForm.note,
        });

        const createdWithdrawal = response.data.withdrawal ?? null;

        notice.value = {
            type: 'success',
            text: response.data.message ?? 'Withdrawal request submitted successfully.',
        };

        merchantBalances.value = response.data.balances ?? merchantBalances.value;
        merchantForm.note = '';
        actionForm.amount = '';

        if (createdWithdrawal) {
            if (selectedStatus.value !== 'all' && selectedStatus.value !== createdWithdrawal.status) {
                selectedStatus.value = 'all';
            }

            withdrawals.value = [
                createdWithdrawal,
                ...withdrawals.value.filter((item) => item.id !== createdWithdrawal.id),
            ];
        }

        await Promise.all([
            loadDashboard(),
            loadMerchantWithdrawalForm(),
        ]);
    } catch (error) {
        showError(error, 'Unable to submit withdrawal request.');
    } finally {
        processingId.value = null;
    }
}

async function createMerchantBankAccount() {
    if (!canCreateBankAccount.value) {
        notice.value = {
            type: 'error',
            text: 'Fill in bank name, account name, and account number first.',
        };
        return;
    }

    processingId.value = 'bank-account-create';

    try {
        const response = await window.axios.post('/api/merchant/bank-accounts', {
            bank_name: bankAccountForm.bank_name,
            account_name: bankAccountForm.account_name,
            account_number: bankAccountForm.account_number,
            account_type: bankAccountForm.account_type,
            is_default: true,
            status: 'active',
        });

        const account = response.data.account;

        notice.value = {
            type: 'success',
            text: response.data.message ?? 'Bank account added successfully.',
        };

        if (account) {
            merchantBankAccounts.value = [
                {
                    id: account.id,
                    label: `${account.bank_name} - ${account.account_holder_name} (${account.account_number})`,
                    is_default: Boolean(account.is_default),
                },
                ...merchantBankAccounts.value.filter((item) => item.id !== account.id),
            ];
            merchantForm.bank_account_id = String(account.id);
        }

        resetBankAccountForm();
        await loadMerchantWithdrawalForm();
    } catch (error) {
        showError(error, 'Unable to create bank account.');
    } finally {
        processingId.value = null;
    }
}

function resetBankAccountForm() {
    bankAccountForm.bank_name = merchantBankOptions.value[0]?.value ?? '';
    bankAccountForm.account_name = '';
    bankAccountForm.account_number = '';
    bankAccountForm.account_type = 'bank';
}

function closeActionForm() {
    actionForm.action = '';
    actionForm.note = '';
    actionForm.item = null;
    actionForm.currency = 'USD';
    actionForm.amount = '';
    actionForm.bank_label = '';
    actionForm.bank_detail = '';
}

function normalizeActionAmount() {
    if (actionForm.amount === '') {
        return;
    }

    if (actionForm.currency === 'KHR') {
        const amount = Number.parseFloat(actionForm.amount || '0');
        actionForm.amount = Number.isNaN(amount) ? '' : String(Math.max(Math.trunc(amount), 0));
        return;
    }

    const amount = Number.parseFloat(actionForm.amount || '0');
    actionForm.amount = Number.isNaN(amount) ? '' : String(amount);
}

function setAction(action) {
    if (!canRunAction(action)) {
        return;
    }

    actionForm.action = action;
}

function canRunAction(action) {
    if (!actionForm.item) {
        return false;
    }

    if (action === 'approve' || action === 'reject') {
        return actionForm.item.status === 'pending';
    }

    if (action === 'mark-paid') {
        return actionForm.item.status === 'approved';
    }

    return false;
}

async function submitActionForm() {
    if (!actionForm.item || !actionForm.action) {
        return;
    }

    const { item, action, note } = actionForm;
    processingId.value = item.id;

    try {
        const response = await window.axios.put(`/api/admin/withdrawals/${item.id}/${action}`, {
            note,
        });

        notice.value = {
            type: 'success',
            text: response.data.message ?? 'Withdrawal updated.',
        };

        await loadDashboard();
        closeActionForm();
    } catch (error) {
        showError(error, 'Unable to update withdrawal.');
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

function showError(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const first = Object.values(response.errors).flat()[0];
        notice.value = { type: 'error', text: first ?? fallback };
        return;
    }

    notice.value = {
        type: 'error',
        text: response?.message ?? fallback,
    };
}

function currency(value, code = 'USD') {
    const amount = Number.parseFloat(value ?? 0);

    if (code === 'KHR') {
        return new Intl.NumberFormat('km-KH', {
            style: 'currency',
            currency: 'KHR',
            maximumFractionDigits: 0,
        }).format(Number.isNaN(amount) ? 0 : amount);
    }

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number.isNaN(amount) ? 0 : amount);
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

function formatTime(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function currencyLabel(code) {
    return {
        USD: 'US Dollar',
        KHR: 'Khmer Riel',
    }[code] ?? code ?? '-';
}

function statusClass(status) {
    return {
        pending: 'badge-pending',
        approved: 'badge-approved',
        rejected: 'badge-rejected',
        paid: 'badge-paid',
    }[status] ?? 'badge-default';
}
</script>

<style scoped>
.withdraw-card {
    background: #fff;
    border: 0.5px solid #e2e8f0;
}

.withdraw-card-header {
    border-bottom: 0.5px solid #e2e8f0;
}

.withdraw-toolbar,
.withdraw-table-footer {
    background: #fafafa;
}

.withdraw-kicker {
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.withdraw-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

.withdraw-filter {
    font-weight: 500;
    border: 0.5px solid #cbd5e1;
    transition: background 0.1s, color 0.1s, border-color 0.1s;
}

.withdraw-filter-active {
    background: #0f172a;
    border-color: #0f172a;
    color: #fff;
}

.withdraw-filter-idle {
    background: #fff;
    color: #475569;
}

.withdraw-filter-idle:hover {
    background: #f8fafc;
}

.withdraw-meta-label {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.withdraw-table-wrap {
    border-top: 0.5px solid #e2e8f0;
    background: #fff;
}

.withdraw-table-head th {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    border-bottom: 0.5px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
}

.withdraw-table-row {
    border-bottom: 0.5px solid #f1f5f9;
    transition: background 0.16s ease;
}

.withdraw-table-row:hover {
    background: #fbfcfe;
}

.withdraw-badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 999px;
    white-space: nowrap;
    text-transform: capitalize;
}

.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #dbeafe; color: #1d4ed8; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-paid { background: #d1fae5; color: #065f46; }
.badge-default { background: #f1f5f9; color: #64748b; }

.action-button {
    border-radius: 999px;
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.16s ease;
    white-space: nowrap;
}

.action-button:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.action-button-approve {
    background: #ecfdf5;
    color: #059669;
}

.action-button-approve:hover {
    background: #d1fae5;
}

.action-button-reject {
    background: #fff1f2;
    color: #e11d48;
}

.action-button-reject:hover {
    background: #ffe4e6;
}

.action-button-paid {
    background: #eff6ff;
    color: #2563eb;
}

.action-button-paid:hover {
    background: #dbeafe;
}
</style>
