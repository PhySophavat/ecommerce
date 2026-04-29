<template>
    <div class="min-h-screen bg-[linear-gradient(180deg,#fff8fc_0%,#f9fafb_45%,#eef4ff_100%)] px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-[32px] border border-slate-200 bg-white/95 shadow-[0_24px_80px_rgba(15,23,42,0.08)] backdrop-blur">
                <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,rgba(162,95,136,0.18),transparent_36%),radial-gradient(circle_at_top_right,rgba(251,191,36,0.18),transparent_30%),white] px-6 py-8 sm:px-8">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#A25F88]">Merchant Wallet</p>
                            <h1 class="mt-2 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">Wallet, deposits, withdrawals, and payout accounts</h1>
                            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                                Track wallet balances, top up via KHQR, request payouts, and review your full wallet ledger.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <article class="rounded-3xl border border-slate-200 bg-white px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Available</p>
                                <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ currency(wallet.available_balance) }}</p>
                            </article>
                            <article class="rounded-3xl border border-slate-200 bg-white px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending</p>
                                <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ currency(wallet.pending_balance) }}</p>
                            </article>
                            <article class="rounded-3xl border border-slate-200 bg-white px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Wallet Total</p>
                                <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ currency(wallet.balance_total) }}</p>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="border-b border-slate-200 px-6 py-4 sm:px-8">
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

                <div class="px-6 py-6 sm:px-8">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-3xl border px-4 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="rounded-3xl border border-slate-200 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                        Loading wallet data...
                    </div>

                    <MerchantWallet
                        v-else-if="screen === 'wallet'"
                        :wallet="wallet"
                        :recent-transactions="recentTransactions"
                    />

                    <MerchantDeposit
                        v-else-if="screen === 'deposit'"
                        :khqr="khqr"
                        :deposits="deposits"
                        :is-submitting="isSubmitting"
                        @submit="submitDeposit"
                        @copied="showSuccess('KHQR code copied to clipboard.')"
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
                        @submit="submitWithdrawal"
                    />

                    <MerchantTransactionHistory
                        v-else
                        :filters="transactionFilters"
                        :selected-type="selectedTransactionType"
                        :transactions="transactions"
                        @change-type="changeTransactionType"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
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
const accounts = ref([]);
const bankOptions = ref([]);
const deposits = ref([]);
const khqr = ref({ code: '', image_url: null });
const recentTransactions = ref([]);
const transactions = ref([]);
const transactionFilters = ref([]);
const selectedTransactionType = ref('all');
const withdrawalAccounts = ref([]);
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

onMounted(async () => {
    await refresh();
});

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
}

async function loadDeposits() {
    const response = await window.axios.get('/api/merchant/deposits');
    khqr.value = response.data.khqr ?? khqr.value;
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
    withdrawalAccounts.value = response.data.bank_accounts ?? [];
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
