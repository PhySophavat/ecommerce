<template>
    <div class="space-y-8">
        <section class="mx-auto w-full max-w-[760px] rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Withdraw</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Request withdrawal</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Available</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ currency(wallet.available_to_withdraw, form.currency) }}</p>
                </article>
                <article class="rounded-[24px] border border-amber-200 bg-amber-50/80 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-600">Pending</p>
                    <p class="mt-2 text-2xl font-extrabold text-amber-700">{{ currency(wallet.pending_balance, form.currency) }}</p>
                </article>
                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Fee</p>
                    <p class="mt-2 text-2xl font-extrabold text-slate-950">{{ currency(withdrawFee, form.currency) }}</p>
                </article>
            </div>

            <div v-if="bankAccounts.length === 0" class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-5 text-sm text-amber-700">
                <p class="font-semibold text-amber-800">No approved payout account yet.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a
                        href="/merchant/bank-accounts"
                        class="rounded-2xl bg-[#A25F88] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Create Bank Account
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
                        </button>
                    </div>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-400">
                            {{ form.currency === 'KHR' ? 'R' : '$' }}
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
                </label>

                <div v-if="filteredAccounts.length === 0" class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-5 text-sm text-amber-700">
                    <p class="font-semibold text-amber-800">No approved {{ form.currency }} account.</p>
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
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Account</span>
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
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Note</span>
                    <textarea
                        v-model="form.note"
                        rows="3"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]"
                        placeholder="Optional note"
                    />
                </label>

                <div class="grid gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 sm:grid-cols-2">
                    <div>
                        <p class="font-semibold text-slate-900">Amount</p>
                        <p class="mt-1">{{ currency(previewAmount, form.currency) }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Net</p>
                        <p class="mt-1">{{ currency(netAmount, form.currency) }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Minimum</p>
                        <p class="mt-1">{{ currency(minimumAmount, form.currency) }}</p>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-900">Available</p>
                        <p class="mt-1">{{ currency(wallet.available_to_withdraw, form.currency) }}</p>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSubmitting || !canSubmit"
                >
                    {{ isSubmitting ? 'Submitting...' : 'Submit' }}
                </button>
            </form>
        </section>

        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <h2 class="text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Payout accounts</h2>
                <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">{{ filteredAccounts.length }} accounts</div>
            </div>

            <div v-if="filteredAccounts.length === 0" class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                No accounts found.
            </div>

            <div v-else class="mt-6 overflow-x-auto rounded-[28px] border border-slate-200">
                <table class="min-w-[900px] w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <tr>
                            <th class="px-4 py-4">Bank</th>
                            <th class="px-4 py-4">Holder</th>
                            <th class="px-4 py-4">Number</th>
                            <th class="px-4 py-4">Phone</th>
                            <th class="px-4 py-4">Currency</th>
                            <th class="px-4 py-4">Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="account in filteredAccounts" :key="account.id" class="border-t border-slate-200 bg-white">
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ account.bank_name }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ account.account_holder_name }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ account.account_number }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ account.phone_number || '-' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ account.currency }}</span></td>
                            <td class="px-4 py-4">
                                <span v-if="account.is_default" class="rounded-full bg-[#A25F88] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-white">Default</span>
                                <span v-else class="text-xs text-slate-400">-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
