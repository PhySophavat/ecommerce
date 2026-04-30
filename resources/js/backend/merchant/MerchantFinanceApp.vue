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
                    @primary-action="refresh"
                    @refresh="refresh"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-3xl border px-4 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div class="mb-6 rounded-[30px] border border-slate-200 bg-white px-6 py-5 shadow-sm">
                        <nav class="flex flex-wrap gap-3">
                            <a
                                v-for="item in navItems"
                                :key="item.key"
                                :href="item.href"
                                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                :class="screen === item.key ? 'bg-[#A25F88] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >
                                {{ item.label }}
                            </a>
                        </nav>
                    </div>

                    <div v-if="isLoading" class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                        Loading wallet data...
                    </div>

                    <MerchantWallet
                        v-else-if="screen === 'wallet'"
                        :wallet="wallet"
                        :recent-transactions="recentTransactions"
                        :merchant="merchantProfile"
                        :providers="depositProviders"
                        :deposits="deposits"
                        :bank-accounts="withdrawalAccounts"
                        :minimum-amount="minimumAmount"
                        :withdraw-fee="withdrawFee"
                        :is-submitting="isSubmitting"
                        :success-token="withdrawalSuccessToken"
                        @submit-deposit="submitDeposit"
                        @submit-withdrawal="submitWithdrawal"
                        @copied-khqr="showSuccess('KHQR code copied to clipboard.')"
                        @downloaded-khqr="showSuccess('KHQR image downloaded.')"
                    />

                    <MerchantDeposit
                        v-else-if="screen === 'deposit'"
                        :merchant="merchantProfile"
                        :providers="depositProviders"
                        :deposits="deposits"
                        :is-submitting="isSubmitting"
                        @submit="submitDeposit"
                        @copied="showSuccess('KHQR code copied to clipboard.')"
                        @downloaded="showSuccess('KHQR image downloaded.')"
                    />

                    <MerchantBankAccounts
                        v-else-if="screen === 'bank-accounts'"
                        :accounts="accounts"
                        :bank-options="bankOptions"
                        :busy-id="busyId"
                        @create="createAccount"
                        @update="updateAccount"
                        @delete="deleteAccount"
                    />

                    <MerchantWithdraw
                        v-else-if="screen === 'withdraw'"
                        :bank-accounts="withdrawalAccounts"
                        :wallet="wallet"
                        :minimum-amount="minimumAmount"
                        :withdraw-fee="withdrawFee"
                        :is-submitting="isSubmitting"
                        :success-token="withdrawalSuccessToken"
                        @submit="submitWithdrawal"
                    />

                    <MerchantTransactionHistory
                        v-else
                        :filters="transactionFilters"
                        :selected-type="selectedTransactionType"
                        :transactions="transactions"
                        @change-type="changeTransactionType"
                    />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
import MerchantBankAccounts from './MerchantBankAccounts.vue';
import MerchantDeposit from './MerchantDeposit.vue';
import MerchantTransactionHistory from './MerchantTransactionHistory.vue';
import MerchantWallet from './MerchantWallet.vue';
import MerchantWithdraw from './MerchantWithdraw.vue';

const screen = window.__APP_CONTEXT__?.screen ?? 'wallet';
const navItems = [
    { key: 'wallet', label: 'Wallet', href: '/merchant/wallet' },
    { key: 'deposit', label: 'Deposit', href: '/merchant/deposits' },
    { key: 'withdraw', label: 'Withdraw', href: '/merchant/withdrawals' },
    { key: 'transactions', label: 'Transactions', href: '/merchant/wallet/transactions' },
    { key: 'bank-accounts', label: 'Bank Accounts', href: '/merchant/bank-accounts' },
];

const isLoading = ref(true);
const isSubmitting = ref(false);
const busyId = ref(null);
const notice = ref(null);
const openMenus = ref({});
const accounts = ref([]);
const bankOptions = ref([]);
const deposits = ref([]);
const depositProviders = ref([]);
const merchantProfile = ref({ shop_name: '', owner_name: '' });
const recentTransactions = ref([]);
const transactions = ref([]);
const transactionFilters = ref([]);
const selectedTransactionType = ref('all');
const withdrawalAccounts = ref([]);
const withdrawalSuccessToken = ref(0);
const minimumAmount = ref('10.00');
const withdrawFee = ref('0.00');
const wallet = ref({
    balance_total: '0.00',
    available_balance: '0.00',
    pending_balance: '0.00',
    available_to_withdraw: '0.00',
    total_withdrawn: '0.00',
    total_deposited: '0.00',
    total_platform_fee_paid: '0.00',
});
const dashboard = computed(() => ({
    meta: {
        brand: 'E-commerce',
        page_title: ({
            wallet: 'Merchant Wallet',
            deposit: 'Merchant Deposit',
            withdraw: 'Merchant Withdrawals',
            transactions: 'Merchant Transactions',
            'bank-accounts': 'Merchant Bank Accounts',
        })[screen] ?? 'Merchant Wallet',
        kicker: 'Merchant finance',
        subheadline: 'Track wallet balances, top up via KHQR, request payouts, and review your full wallet ledger.',
    },
    menu: [
        {
            label: 'Wallet',
            slug: 'wallet',
            icon: 'wallet',
            path: '/merchant/wallet',
            is_enabled: true,
            is_active: screen === 'wallet',
            is_expanded: false,
            children: [],
        },
        {
            label: 'Deposit',
            slug: 'deposit',
            icon: 'payments',
            path: '/merchant/deposits',
            is_enabled: true,
            is_active: screen === 'deposit',
            is_expanded: false,
            children: [],
        },
        {
            label: 'Withdrawals',
            slug: 'merchant-finance',
            icon: 'payments',
            path: null,
            is_enabled: true,
            is_active: screen === 'withdraw' || screen === 'transactions' || screen === 'bank-accounts',
            is_expanded: true,
            children: [
                {
                    label: 'Withdraw',
                    slug: 'withdraw',
                    path: '/merchant/withdrawals',
                    is_enabled: true,
                    is_active: screen === 'withdraw',
                    is_expanded: false,
                    children: [],
                },
                {
                    label: 'Transactions',
                    slug: 'transactions',
                    path: '/merchant/wallet/transactions',
                    is_enabled: true,
                    is_active: screen === 'transactions',
                    is_expanded: false,
                    children: [],
                },
                {
                    label: 'Bank Accounts',
                    slug: 'bank-accounts',
                    path: '/merchant/bank-accounts',
                    is_enabled: true,
                    is_active: screen === 'bank-accounts',
                    is_expanded: false,
                    children: [],
                },
            ],
        },
    ],
}));

onMounted(async () => {
    await refresh();
});

function toggleMenu(slug) {
    openMenus.value = {
        ...openMenus.value,
        [slug]: !openMenus.value[slug],
    };
}

function isMenuOpen(slug) {
    if (!(slug in openMenus.value)) {
        return slug === 'merchant-finance';
    }

    return Boolean(openMenus.value[slug]);
}

async function refresh() {
    isLoading.value = true;

    try {
        await Promise.all([
            loadWallet(),
            loadAccounts(),
            loadDeposits(),
            loadWithdrawals(),
            loadTransactions(),
        ]);
    } catch (error) {
        showError(error, 'Unable to load wallet data right now.');
    } finally {
        isLoading.value = false;
    }
}

async function loadWallet() {
    const response = await window.axios.get('/api/merchant/wallet');
    wallet.value = response.data.wallet ?? wallet.value;
    recentTransactions.value = response.data.recent_transactions ?? [];
}

async function loadAccounts() {
    const response = await window.axios.get('/api/merchant/bank-accounts');
    accounts.value = response.data.accounts ?? [];
    bankOptions.value = response.data.meta?.bank_options ?? [];
    withdrawalAccounts.value = accounts.value
        .filter((account) => account.status === 'active')
        .map((account) => ({
            id: account.id,
            label: `${account.bank_name} (${account.account_number ?? `****${account.account_number_last4 ?? ''}`})`,
            is_default: Boolean(account.is_default),
        }));
}

async function loadDeposits() {
    const response = await window.axios.get('/api/merchant/deposits');
    merchantProfile.value = response.data.merchant ?? merchantProfile.value;
    depositProviders.value = response.data.providers ?? depositProviders.value;
    deposits.value = response.data.deposits ?? [];
}

async function loadWithdrawals() {
    const response = await window.axios.get('/api/merchant/withdrawals');
    wallet.value = {
        ...wallet.value,
        ...(response.data.balances ?? {}),
    };
    minimumAmount.value = response.data.minimum_amount ?? minimumAmount.value;
    withdrawFee.value = response.data.withdraw_fee ?? withdrawFee.value;
}

async function loadTransactions(type = selectedTransactionType.value) {
    const response = await window.axios.get('/api/merchant/wallet/transactions', {
        params: { type },
    });
    wallet.value = response.data.wallet ?? wallet.value;
    transactionFilters.value = response.data.filters ?? [];
    selectedTransactionType.value = response.data.selected_type ?? type;
    transactions.value = response.data.transactions ?? [];
}

async function changeTransactionType(type) {
    await loadTransactions(type);
}

async function createAccount(payload) {
    busyId.value = 'create';

    try {
        const response = await window.axios.post('/api/merchant/bank-accounts', payload);
        showSuccess(response.data.message ?? 'Bank account added.');
        await Promise.all([loadAccounts(), loadWithdrawals()]);
    } catch (error) {
        showError(error, 'Unable to add bank account.');
        throw error;
    } finally {
        busyId.value = null;
    }
}

async function updateAccount(id, payload) {
    busyId.value = id;

    try {
        const response = await window.axios.put(`/api/merchant/bank-accounts/${id}`, payload);
        showSuccess(response.data.message ?? 'Bank account updated.');
        await Promise.all([loadAccounts(), loadWithdrawals()]);
    } catch (error) {
        showError(error, 'Unable to update bank account.');
        throw error;
    } finally {
        busyId.value = null;
    }
}

async function deleteAccount(id) {
    if (!window.confirm('Delete this payout account?')) {
        return;
    }

    busyId.value = id;

    try {
        const response = await window.axios.delete(`/api/merchant/bank-accounts/${id}`);
        showSuccess(response.data.message ?? 'Bank account deleted.');
        await Promise.all([loadAccounts(), loadWithdrawals()]);
    } catch (error) {
        showError(error, 'Unable to delete bank account.');
    } finally {
        busyId.value = null;
    }
}

async function submitDeposit(payload) {
    isSubmitting.value = true;

    try {
        const response = await window.axios.post('/api/merchant/deposits', payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        showSuccess(response.data.message ?? 'Deposit submitted.');
        await Promise.all([loadDeposits(), loadWallet(), loadTransactions()]);
    } catch (error) {
        showError(error, 'Unable to submit deposit.');
        throw error;
    } finally {
        isSubmitting.value = false;
    }
}

async function submitWithdrawal(payload) {
    isSubmitting.value = true;

    try {
        const response = await window.axios.post('/api/merchant/withdrawals', payload);
        showSuccess(response.data.message ?? 'Withdrawal submitted.');
        withdrawalSuccessToken.value += 1;
        await Promise.all([loadWithdrawals(), loadWallet(), loadTransactions()]);
    } catch (error) {
        showError(error, 'Unable to submit withdrawal.');
        throw error;
    } finally {
        isSubmitting.value = false;
    }
}

function showSuccess(text) {
    notice.value = { type: 'success', text };
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

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}
</script>
