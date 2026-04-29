<template>
    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Wallet Ledger</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Transaction history</h2>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    :class="selectedType === filter.value ? 'bg-[#A25F88] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    @click="$emit('change-type', filter.value)"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>

        <div v-if="transactions.length === 0" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
            No transactions found for this filter.
        </div>

        <div v-else class="mt-6 overflow-x-auto rounded-3xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    <tr>
                        <th class="px-4 py-4">Type</th>
                        <th class="px-4 py-4">Direction</th>
                        <th class="px-4 py-4">Amount</th>
                        <th class="px-4 py-4">Balance After</th>
                        <th class="px-4 py-4">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in transactions" :key="item.id" class="border-t border-slate-200">
                        <td class="px-4 py-4">
                            <p class="font-semibold capitalize text-slate-900">{{ item.type.replace('_', ' ') }}</p>
                            <p class="text-slate-500">{{ item.description || '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="item.direction === 'credit' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                {{ item.direction }}
                            </span>
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
    </section>
</template>

<script setup>
defineProps({
    filters: { type: Array, default: () => [] },
    selectedType: { type: String, default: 'all' },
    transactions: { type: Array, default: () => [] },
});

defineEmits(['change-type']);

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}
</script>
