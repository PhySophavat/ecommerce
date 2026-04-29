<template>
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article v-for="card in cards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em]" :class="card.tone">{{ currency(card.value) }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Recent activity</p>
                        <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Latest wallet transactions</h2>
                    </div>
                    <a href="/merchant/wallet/transactions" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                        View all
                    </a>
                </div>

                <div v-if="recentTransactions.length === 0" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                    No wallet activity yet.
                </div>

                <div v-else class="mt-6 overflow-x-auto rounded-3xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            <tr>
                                <th class="px-4 py-4">Type</th>
                                <th class="px-4 py-4">Amount</th>
                                <th class="px-4 py-4">Balance After</th>
                                <th class="px-4 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in recentTransactions" :key="item.id" class="border-t border-slate-200">
                                <td class="px-4 py-4">
                                    <p class="font-semibold capitalize text-slate-900">{{ item.type.replace('_', ' ') }}</p>
                                    <p class="text-slate-500">{{ item.description || '-' }}</p>
                                </td>
                                <td class="px-4 py-4 font-bold" :class="item.direction === 'credit' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ item.direction === 'credit' ? '+' : '-' }}{{ currency(item.amount) }}
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ currency(item.balance_after) }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ formatDate(item.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Quick actions</p>
                    <div class="mt-6 grid gap-3">
                        <a href="/merchant/deposits" class="rounded-2xl bg-[#A25F88] px-5 py-4 text-sm font-semibold text-white transition hover:opacity-90">
                            Create deposit request
                        </a>
                        <a href="/merchant/withdrawals" class="rounded-2xl bg-slate-950 px-5 py-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Request withdrawal
                        </a>
                        <a href="/merchant/bank-accounts" class="rounded-2xl bg-slate-100 px-5 py-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                            Manage bank accounts
                        </a>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-[linear-gradient(180deg,#fff7fb_0%,#ffffff_100%)] p-5 shadow-sm sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Available to withdraw</p>
                    <p class="mt-3 text-4xl font-extrabold tracking-[-0.06em] text-slate-950">{{ currency(wallet.available_to_withdraw) }}</p>
                    <p class="mt-3 text-sm text-slate-600">
                        Pending withdrawal requests remain reserved until admin marks them as paid.
                    </p>
                </section>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    wallet: { type: Object, required: true },
    recentTransactions: { type: Array, default: () => [] },
});

const cards = computed(() => [
    { label: 'Available', value: props.wallet.available_balance, tone: 'text-slate-950' },
    { label: 'Pending', value: props.wallet.pending_balance, tone: 'text-amber-600' },
    { label: 'Withdrawn', value: props.wallet.total_withdrawn, tone: 'text-slate-950' },
    { label: 'Deposited', value: props.wallet.total_deposited, tone: 'text-emerald-600' },
    { label: 'Platform Fees', value: props.wallet.total_platform_fee_paid, tone: 'text-rose-600' },
]);

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}
</script>
