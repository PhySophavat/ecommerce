<template>
    <div class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Wallet Summary</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Available balance</h2>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Available Balance</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(wallet.available_balance) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Available to Withdraw</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(wallet.available_to_withdraw) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending Balance</p>
                    <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ currency(wallet.pending_balance) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Withdrawal Fee</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(withdrawFee, form.currency) }}</p>
                </article>
            </div>

            <div class="mt-6 rounded-3xl border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-4 py-4 text-sm text-slate-600">
                Minimum withdrawal: <strong>{{ currency(minimumAmount, form.currency) }}</strong>
            </div>

            <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-950 px-5 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">Live preview</p>
                <p class="mt-3 text-3xl font-extrabold tracking-[-0.04em]">{{ currency(previewAmount, form.currency) }}</p>
                <p class="mt-2 text-sm text-white/70">Net payout after fee: {{ currency(netAmount, form.currency) }}</p>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Withdrawal Request</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Request withdrawal</h2>
            <p class="mt-2 text-sm text-slate-500">Choose your payout currency, amount, and destination account.</p>

            <div v-if="bankAccounts.length === 0" class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-5 text-sm text-amber-700">
                <p class="font-semibold text-amber-800">No approved payout account yet.</p>
                <p class="mt-2">Create a bank account first, then wait for admin approval before requesting a withdrawal.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a
                        href="/merchant/bank-accounts"
                        class="rounded-2xl bg-[#A25F88] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Create Bank Account
                    </a>
                    <a
                        href="/merchant/wallet"
                        class="rounded-2xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Back to Wallet
                    </a>
                </div>
            </div>

            <form v-else class="mt-6 space-y-5" @submit.prevent="submit">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Currency</span>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                            :class="form.currency === 'USD'
                                ? 'border-[#A25F88] bg-[#fff7fb] shadow-sm'
                                : 'border-slate-200 bg-slate-50 hover:border-[#A25F88]/40'"
                            @click="form.currency = 'USD'"
                        >
                            <p class="text-sm font-bold text-slate-950">USD</p>
                            <p class="mt-1 text-xs text-slate-500">Dollar $</p>
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border px-4 py-3 text-left transition"
                            :class="form.currency === 'KHR'
                                ? 'border-[#A25F88] bg-[#fff7fb] shadow-sm'
                                : 'border-slate-200 bg-slate-50 hover:border-[#A25F88]/40'"
                            @click="form.currency = 'KHR'"
                        >
                            <p class="text-sm font-bold text-slate-950">KHR</p>
                            <p class="mt-1 text-xs text-slate-500">Khmer Riel ៛</p>
                        </button>
                    </div>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                            {{ form.currency === 'KHR' ? '៛' : '$' }}
                        </span>
                        <input
                            v-model="form.amount"
                            type="number"
                            :step="amountStep"
                            min="0"
                            :inputmode="form.currency === 'KHR' ? 'numeric' : 'decimal'"
                            :placeholder="amountPlaceholder"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]"
                            @input="normalizeAmount"
                        >
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ form.currency === 'KHR' ? 'KHR accepts whole numbers only.' : 'USD accepts decimal amounts like 10.50.' }}
                    </p>
                </label>

                <div v-if="filteredAccounts.length === 0" class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-5 text-sm text-amber-700">
                    <p class="font-semibold text-amber-800">No approved {{ form.currency }} payout account is available.</p>
                    <p class="mt-2">Add a matching {{ form.currency }} bank account on the bank accounts page, then wait for admin approval.</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a
                            href="/merchant/bank-accounts"
                            class="rounded-2xl bg-[#A25F88] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                        >
                            Manage Bank Accounts
                        </a>
                    </div>
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Bank Account</span>
                    <select
                        v-model="form.bank_account_id"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]"
                    >
                        <option value="">Select account</option>
                        <option v-for="account in filteredAccounts" :key="account.id" :value="String(account.id)">
                            {{ account.label }}
                        </option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Note (optional)</span>
                    <textarea
                        v-model="form.note"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]"
                    />
                </label>

                <div class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 sm:grid-cols-2">
                    <div>
                        <p class="font-semibold text-slate-900">Preview</p>
                        <p class="mt-1">{{ currency(previewAmount, form.currency) }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Available balance</p>
                        <p class="mt-1">{{ currency(wallet.available_to_withdraw, form.currency) }}</p>
                    </div>
                </div>

                <div class="rounded-3xl border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-4 py-4 text-sm text-slate-600">
                    After you request withdrawal, it will appear in the admin withdrawals table with <strong class="text-slate-900">pending</strong> status until admin approves or rejects it.
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSubmitting || !canSubmit"
                >
                    Request Withdrawal
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    bankAccounts: { type: Array, default: () => [] },
    wallet: { type: Object, required: true },
    minimumAmount: { type: String, default: '10.00' },
    withdrawFee: { type: String, default: '0.00' },
    isSubmitting: { type: Boolean, default: false },
    successToken: { type: Number, default: 0 },
});

const emit = defineEmits(['submit']);

const form = reactive({
    currency: 'USD',
    bank_account_id: '',
    amount: '',
    note: '',
});

const amountStep = computed(() => (form.currency === 'KHR' ? '1' : '0.01'));
const amountPlaceholder = computed(() => (
    form.currency === 'KHR' ? 'Enter amount in KHR' : 'Enter amount in USD'
));

watch(
    () => props.bankAccounts,
    (accounts) => {
        syncDefaultAccount(accounts, form.currency);
    },
    { immediate: true, deep: true },
);

watch(
    () => form.currency,
    () => {
        normalizeAmount();
        syncDefaultAccount(props.bankAccounts, form.currency);
    },
);

watch(
    () => props.successToken,
    () => {
        form.amount = '';
        form.note = '';
    },
);

const previewAmount = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');
    return Number.isNaN(amount) ? 0 : amount;
});

const netAmount = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');
    const fee = Number.parseFloat(props.withdrawFee || '0');
    const net = Math.max(amount - fee, 0);
    return form.currency === 'KHR' ? Math.round(net) : net.toFixed(2);
});

const filteredAccounts = computed(() => props.bankAccounts.filter((account) => account.currency === form.currency));

const canSubmit = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');

    return filteredAccounts.value.length > 0
        && Boolean(form.bank_account_id)
        && form.amount !== ''
        && amount >= Number.parseFloat(props.minimumAmount)
        && amount <= Number.parseFloat(props.wallet.available_to_withdraw ?? '0')
        && amount > Number.parseFloat(props.withdrawFee);
});

function submit() {
    emit('submit', {
        bank_account_id: Number.parseInt(form.bank_account_id, 10),
        amount: form.amount,
        currency: form.currency,
        note: form.note,
    });
}

function normalizeAmount() {
    if (form.amount === '') {
        return;
    }

    if (form.currency === 'KHR') {
        form.amount = String(Math.max(Math.trunc(Number.parseFloat(form.amount || '0')), 0));
        return;
    }

    const amount = Number.parseFloat(form.amount || '0');

    if (Number.isNaN(amount)) {
        form.amount = '';
        return;
    }

    form.amount = String(amount);
}

function syncDefaultAccount(accounts, currency) {
    const candidates = accounts.filter((account) => account.currency === currency);
    const existingStillValid = candidates.some((account) => String(account.id) === String(form.bank_account_id));

    if (existingStillValid) {
        return;
    }

    const defaultAccount = candidates.find((account) => account.is_default) ?? candidates[0];
    form.bank_account_id = defaultAccount ? String(defaultAccount.id) : '';
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
</script>
