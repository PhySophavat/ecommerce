<template>
    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Withdrawal log</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Request history</h2>
            </div>
            <p class="text-sm text-slate-500">{{ withdrawals.length }} records</p>
        </div>

        <div v-if="withdrawals.length === 0" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
            No withdrawal requests yet.
        </div>

        <div v-else class="mt-6 overflow-x-auto rounded-3xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    <tr>
                        <th class="px-4 py-4">Amount</th>
                        <th class="px-4 py-4">Bank account</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Date</th>
                        <th class="px-4 py-4">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in withdrawals" :key="item.id" class="border-t border-slate-200">
                        <td class="px-4 py-4 font-semibold text-slate-950">
                            {{ currency(item.amount, item.currency) }}
                            <p class="mt-1 text-xs font-medium text-slate-500">Net: {{ currency(item.net_amount, item.currency) }}</p>
                        </td>
                        <td class="px-4 py-4 text-slate-600">
                            <p class="font-semibold text-slate-900">{{ item.bank_account?.bank_name }}</p>
                            <p>{{ item.bank_account?.account_holder_name }} • {{ item.bank_account?.account_number }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="statusClass(item.status)">
                                {{ item.status }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-600">{{ formatDate(item.created_at) }}</td>
                        <td class="px-4 py-4 text-slate-600">{{ item.note || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>

<script setup>
defineProps({
    withdrawals: {
        type: Array,
        default: () => [],
    },
});

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

function statusClass(status) {
    return {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-sky-100 text-sky-700',
        rejected: 'bg-rose-100 text-rose-700',
        paid: 'bg-emerald-100 text-emerald-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}
</script>
