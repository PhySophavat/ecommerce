<template>
    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-[28px] border border-slate-200 bg-slate-50/70 p-5 sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Wallet summary</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Available to withdraw</h2>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Withdrawable</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(balances.available_to_withdraw) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Current wallet</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(balances.available_balance) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Pending requests</p>
                    <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ currency(balances.pending_balance) }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Withdraw fee</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ currency(withdrawFee) }}</p>
                </article>
            </div>

            <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-600">
                Minimum withdrawal: <strong>{{ currency(minimumAmount) }}</strong>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">New request</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Request a payout</h2>

            <div v-if="bankAccounts.length === 0" class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-700">
                Add an active bank account before requesting a withdrawal.
            </div>

            <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Payout account</span>
                    <select v-model="form.bank_account_id" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                        <option value="">Select account</option>
                        <option v-for="account in bankAccounts" :key="account.id" :value="String(account.id)">{{ account.label }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                    <input v-model="form.amount" type="number" step="0.01" min="0" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Note</span>
                    <textarea v-model="form.note" rows="4" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"></textarea>
                </label>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    Net payout after fee: <strong class="text-slate-950">{{ currency(netAmount) }}</strong>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                    :disabled="isSubmitting || !canSubmit"
                >
                    Submit Withdrawal
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    bankAccounts: {
        type: Array,
        default: () => [],
    },
    balances: {
        type: Object,
        required: true,
    },
    minimumAmount: {
        type: String,
        default: '10.00',
    },
    withdrawFee: {
        type: String,
        default: '0.00',
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submit']);

const form = reactive({
    bank_account_id: '',
    amount: '',
    note: '',
});

watch(
    () => props.bankAccounts,
    (accounts) => {
        if (form.bank_account_id) {
            return;
        }

        const defaultAccount = accounts.find((account) => account.is_default) ?? accounts[0];
        form.bank_account_id = defaultAccount ? String(defaultAccount.id) : '';
    },
    { immediate: true, deep: true },
);

const netAmount = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');
    const fee = Number.parseFloat(props.withdrawFee || '0');

    return Math.max(amount - fee, 0).toFixed(2);
});

const canSubmit = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');
    return Boolean(form.bank_account_id)
        && amount >= Number.parseFloat(props.minimumAmount)
        && amount <= Number.parseFloat(props.balances.available_to_withdraw ?? '0')
        && amount > Number.parseFloat(props.withdrawFee);
});

async function submit() {
    await emit('submit', {
        bank_account_id: Number.parseInt(form.bank_account_id, 10),
        amount: form.amount,
        note: form.note,
    });

    form.amount = '';
    form.note = '';
}

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number.isNaN(amount) ? 0 : amount);
}
</script>
