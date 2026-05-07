<template>
    <section class="space-y-5 bg-[#F8FAFC]">
        <article class="rounded-[28px] border border-[#E5E7EB] bg-[#FFFFFF] px-5 py-5 shadow-[0_14px_36px_rgba(17,24,39,0.045)] sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">{{ merchantDashboard.hero.eyebrow }}</p>
                    <h2 class="mt-2 text-[1.6rem] font-black tracking-[-0.05em] text-[#111827]">{{ merchantDashboard.hero.title }}</h2>
                    <p class="mt-2 max-w-2xl text-[13px] leading-6 text-[#6B7280]">Merchant dashboard overview.</p>
                </div>

                <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-stretch sm:justify-end">
                    <label class="flex min-h-[44px] items-center gap-2 rounded-xl border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-2.5 text-[13px] font-semibold text-[#111827]">
                        <span class="text-[#6B7280]">Range</span>
                        <select v-model="selectedRange" class="bg-transparent pr-6 outline-none">
                            <option v-for="item in merchantDashboard.date_ranges" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-[#E7C9DA] bg-[#F6EAF1] px-4 py-2.5 text-[13px] font-semibold text-[#8E4F76] shadow-sm transition hover:border-[#A25F88] hover:bg-[#FFFFFF]" @click="$emit('open-balance')">
                        Open balance
                    </button>
                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-[#A25F88] px-4 py-2.5 text-[13px] font-semibold text-white shadow-[0_10px_22px_rgba(162,95,136,0.18)] transition hover:bg-[#8E4F76]" @click="$emit('open-products')">
                        Open products
                    </button>
                </div>
            </div>
        </article>

        <section class="space-y-4 rounded-[30px] border border-[#F3D3E3] bg-[#FDF2F8] p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 px-1">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Finance summary</p>
                    <h3 class="mt-1 text-lg font-bold tracking-[-0.03em] text-[#111827]">Balance and wallet overview</h3>
                </div>
                <span class="rounded-full bg-[#F6EAF1] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#A25F88]">
                    Core finance
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in primarySummaryCards"
                    :key="card.label"
                    class="rounded-[24px] border bg-[#FFFFFF] px-5 py-4 shadow-[0_12px_28px_rgba(17,24,39,0.05)]"
                    :class="primaryCardClass(card)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em]" :class="primaryLabelClass(card)">{{ card.label }}</p>
                            <p class="mt-3 text-[1.7rem] font-black leading-none tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em]" :class="primaryBadgeClass(card)">
                            {{ summaryCategory(card.label) }}
                        </span>
                    </div>
                </article>
            </div>

            <div class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-[24px] border border-[#F3D3E3] bg-white/80 px-5 py-4 shadow-[0_10px_24px_rgba(17,24,39,0.04)] sm:px-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Finance details</p>
                            <h3 class="mt-1 text-base font-bold tracking-[-0.03em] text-[#111827]">Balances and movement</h3>
                        </div>
                        <span class="rounded-full bg-[#F6EAF1] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#A25F88]">
                            {{ financeCompactCards.length }} stats
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-2">
                        <article
                            v-for="card in financeCompactCards"
                            :key="card.label"
                            class="rounded-[18px] border border-[#F3D3E3] bg-[#FFFFFF] px-4 py-3"
                        >
                            <p class="text-[10px] font-semibold uppercase tracking-[0.14em]" :class="compactLabelClass(card)">{{ card.label }}</p>
                            <p class="mt-2 text-[1.05rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[24px] border border-[#F3D3E3] bg-white/80 px-5 py-4 shadow-[0_10px_24px_rgba(17,24,39,0.04)] sm:px-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Key finance note</p>
                            <h3 class="mt-1 text-base font-bold tracking-[-0.03em] text-[#111827]">Wallet note</h3>
                        </div>
                        <span class="rounded-full bg-[#F6EAF1] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#A25F88]">
                            Organized
                        </span>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-[#6B7280]">Key numbers first.</p>
                </section>
            </div>
        </section>

        <section class="space-y-4 rounded-[30px] border border-[#CFEFDC] bg-[#F0FDF4] p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 px-1">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#10B981]">Orders & payments summary</p>
                    <h3 class="mt-1 text-lg font-bold tracking-[-0.03em] text-[#111827]">Orders and payments</h3>
                </div>
                <span class="rounded-full bg-[#ECFDF5] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#10B981]">
                    Healthy flow
                </span>
            </div>

            <div class="grid items-start gap-4 xl:grid-cols-2">
                <div class="grid gap-4">
                    <section class="rounded-[24px] border border-[#CFEFDC] bg-white/80 px-5 py-4 shadow-[0_10px_24px_rgba(17,24,39,0.04)] sm:px-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#10B981]">Operations snapshot</p>
                                <h3 class="mt-1 text-base font-bold tracking-[-0.03em] text-[#111827]">Orders and payments</h3>
                            </div>
                            <span class="rounded-full bg-[#ECFDF5] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#10B981]">
                                {{ orderCompactCards.length }} stats
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="card in orderCompactCards"
                                :key="card.label"
                                class="rounded-[18px] border border-[#CFEFDC] bg-[#FFFFFF] px-4 py-3"
                            >
                                <p class="text-[10px] font-semibold uppercase tracking-[0.14em]" :class="compactLabelClass(card)">{{ card.label }}</p>
                                <p class="mt-2 text-[1.05rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
                            </article>
                        </div>
                    </section>

                    <MerchantDonutChart title="Order status" eyebrow="Order quality" :items="activeDataset.order_status_summary" />
                </div>

                <div class="grid gap-4">
                    <MerchantDonutChart title="Payment count by bank" eyebrow="Payment mix" :items="activeDataset.payment_count_by_bank" />
                    <MerchantDonutChart title="Transaction flow" eyebrow="Finance movement" :items="activeDataset.transaction_flow" value-type="currency" />
                </div>
            </div>
        </section>

        <section class="space-y-4 rounded-[30px] border border-[#D7E7FF] bg-[#EFF6FF] p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 px-1">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#3B82F6]">Analytics & reports</p>
                    <h3 class="mt-1 text-lg font-bold tracking-[-0.03em] text-[#111827]">Sales analytics</h3>
                </div>
                <span class="rounded-full bg-[#FFFFFF] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#3B82F6]">
                    Insights
                </span>
            </div>

            <div class="grid items-stretch gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.92fr)]">
                <MerchantMixedChart
                    title="Sales over time"
                    eyebrow="Sales analytics"
                    :points="activeDataset.sales_over_time"
                    bar-label="Total sales"
                    line-label="Total orders"
                />
                <MerchantAreaChart title="Cumulative sales over time" eyebrow="Revenue trend" :points="activeDataset.cumulative_sales" />
            </div>

            <article class="rounded-[26px] border border-[#D7E7FF] bg-[#FFFFFF] p-5 shadow-[0_14px_34px_rgba(17,24,39,0.045)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3B82F6]">Product sales</p>
                        <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">Top 5 Product Sales</h3>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-[18px] border border-[#E5E7EB]">
                    <table class="min-w-full text-left text-[13px]">
                        <thead class="bg-[#F8FAFC] text-[#6B7280]">
                            <tr>
                                <th class="px-3 py-2.5 font-semibold">Product name</th>
                                <th class="px-3 py-2.5 font-semibold">Sold quantity</th>
                                <th class="px-3 py-2.5 font-semibold">Total sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in topProductRows" :key="item.key" class="border-t border-[#E5E7EB]">
                                <td class="px-3 py-3 font-semibold" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#111827]'">
                                    {{ item.product_name }}
                                </td>
                                <td class="px-3 py-3" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#6B7280]'">
                                    {{ item.sold_quantity }}
                                </td>
                                <td class="px-3 py-3 font-semibold" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#111827]'">
                                    {{ item.total_sales }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="grid gap-4 rounded-[30px] border border-[#F8DEC2] bg-[#FFF7ED] p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-5">
            <article v-for="card in activeDataset.extra_cards" :key="card.label" class="rounded-[20px] border border-[#E5E7EB] bg-[#FFFFFF] px-4 py-4 shadow-[0_10px_24px_rgba(17,24,39,0.04)]">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-[#F59E0B]">{{ card.label }}</p>
                <p class="mt-2 text-[1.35rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
            </article>
        </section>

        <section class="space-y-4 rounded-[30px] border border-[#F8DEC2] bg-[#FFF7ED] p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3 px-1">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#F59E0B]">Warnings & outgoing</p>
                    <h3 class="mt-1 text-lg font-bold tracking-[-0.03em] text-[#111827]">Outgoing flow</h3>
                </div>
                <span class="rounded-full bg-[#FFFFFF] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#F59E0B]">
                    Monitored
                </span>
            </div>

            <article class="rounded-[26px] border border-[#F8DEC2] bg-[#FFFFFF] p-5 shadow-[0_14px_34px_rgba(17,24,39,0.045)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#F59E0B]">Ledger</p>
                        <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">Recent transactions</h3>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-[13px]">
                        <thead class="border-b border-[#E5E7EB] text-[#6B7280]">
                            <tr>
                                <th class="px-2.5 py-2.5 font-semibold">Transaction ID</th>
                                <th class="px-2.5 py-2.5 font-semibold">Order ID</th>
                                <th class="px-2.5 py-2.5 font-semibold">Type</th>
                                <th class="px-2.5 py-2.5 font-semibold">Amount</th>
                                <th class="px-2.5 py-2.5 font-semibold">Currency</th>
                                <th class="px-2.5 py-2.5 font-semibold">Payment Method</th>
                                <th class="px-2.5 py-2.5 font-semibold">Status</th>
                                <th class="px-2.5 py-2.5 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in activeDataset.recent_transactions" :key="item.transaction_id" class="border-b border-[#E5E7EB] last:border-b-0">
                                <td class="px-2.5 py-3 font-semibold text-[#111827]">{{ item.transaction_id }}</td>
                                <td class="px-2.5 py-3 text-[#6B7280]">{{ item.order_id }}</td>
                                <td class="px-2.5 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="item.type === 'IN' ? 'bg-[#F6EAF1] text-[#A25F88]' : 'bg-[#FFF7E8] text-[#F59E0B]'">
                                        {{ item.type }}
                                    </span>
                                </td>
                                <td class="px-2.5 py-3 font-semibold text-[#111827]">{{ item.amount }}</td>
                                <td class="px-2.5 py-3 text-[#6B7280]">{{ item.currency }}</td>
                                <td class="px-2.5 py-3 text-[#111827]">{{ item.payment_method }}</td>
                                <td class="px-2.5 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(item.status)">{{ item.status }}</span>
                                </td>
                                <td class="px-2.5 py-3 text-[#6B7280]">{{ item.date }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import MerchantAreaChart from './merchant-dashboard/MerchantAreaChart.vue';
import MerchantDonutChart from './merchant-dashboard/MerchantDonutChart.vue';
import MerchantMixedChart from './merchant-dashboard/MerchantMixedChart.vue';

const props = defineProps({
    merchantDashboard: {
        type: Object,
        required: true,
    },
});

defineEmits(['open-balance', 'open-products']);

const selectedRange = ref(props.merchantDashboard.selected_range ?? '30days');

const activeDataset = computed(() => props.merchantDashboard.datasets?.[selectedRange.value] ?? props.merchantDashboard.datasets?.['30days'] ?? {
    sales_over_time: [],
    order_status_summary: [],
    payment_count_by_bank: [],
    transaction_flow: [],
    top_product_sales: [],
    cumulative_sales: [],
    recent_transactions: [],
    extra_cards: [],
});

const summaryCards = computed(() => props.merchantDashboard.summary_cards ?? []);
const summaryLookup = computed(() => new Map(summaryCards.value.map((card) => [card.label, card])));
const primarySummaryLabels = ['Total Balance', 'Transaction In', 'Transaction Out', 'Successful Orders'];
const financeCompactLabels = ['Balance KHR', 'Total Transactions'];
const orderCompactLabels = ['Failed Orders', 'Successful Payments', 'Failed Payments'];

const primarySummaryCards = computed(() => primarySummaryLabels.map((label) => summaryLookup.value.get(label)).filter(Boolean));
const financeCompactCards = computed(() => financeCompactLabels.map((label) => summaryLookup.value.get(label)).filter(Boolean));
const orderCompactCards = computed(() => orderCompactLabels.map((label) => summaryLookup.value.get(label)).filter(Boolean));

const topProductRows = computed(() => {
    const items = (activeDataset.value.top_product_sales ?? []).slice(0, 5).map((item, index) => ({
        ...item,
        key: `${item.product_name}-${index}`,
        isPlaceholder: false,
    }));

    while (items.length < 5) {
        items.push({
            key: `placeholder-${items.length}`,
            product_name: 'No product yet',
            sold_quantity: '--',
            total_sales: '--',
            isPlaceholder: true,
        });
    }

    return items;
});

function statusClass(status) {
    return {
        Success: 'bg-[#ECFDF5] text-[#10B981]',
        Failed: 'bg-[#FEF2F2] text-[#EF4444]',
        Pending: 'bg-[#FFF7E8] text-[#F59E0B]',
        Cancelled: 'bg-[#F8FAFC] text-[#9CA3AF]',
    }[status] ?? 'bg-[#F8FAFC] text-[#9CA3AF]';
}

function summaryCategory(label) {
    if (['Total Balance', 'Transaction In', 'Transaction Out'].includes(label)) {
        return 'Finance';
    }

    return 'Orders';
}

function primaryCardClass(card) {
    return {
        'Total Balance': 'border-[#E7C9DA] bg-[linear-gradient(180deg,#F6EAF1_0%,#FFFFFF_100%)]',
        'Transaction In': 'border-[#CDEFE2] bg-[linear-gradient(180deg,#ECFDF5_0%,#FFFFFF_100%)]',
        'Transaction Out': 'border-[#F4DEC0] bg-[linear-gradient(180deg,#FFF7E8_0%,#FFFFFF_100%)]',
        'Successful Orders': 'border-[#D7E7FF] bg-[linear-gradient(180deg,#EFF6FF_0%,#FFFFFF_100%)]',
    }[card.label] ?? 'border-[#E5E7EB]';
}

function primaryLabelClass(card) {
    return {
        'Total Balance': 'text-[#A25F88]',
        'Transaction In': 'text-[#10B981]',
        'Transaction Out': 'text-[#F59E0B]',
        'Successful Orders': 'text-[#3B82F6]',
    }[card.label] ?? 'text-[#6B7280]';
}

function primaryBadgeClass(card) {
    return {
        'Total Balance': 'bg-[#F6EAF1] text-[#A25F88]',
        'Transaction In': 'bg-[#ECFDF5] text-[#10B981]',
        'Transaction Out': 'bg-[#FFF7E8] text-[#F59E0B]',
        'Successful Orders': 'bg-[#EFF6FF] text-[#3B82F6]',
    }[card.label] ?? 'bg-[#F8FAFC] text-[#6B7280]';
}

function compactLabelClass(card) {
    return {
        'Balance KHR': 'text-[#8E4F76]',
        'Total Transactions': 'text-[#3B82F6]',
        'Failed Orders': 'text-[#EF4444]',
        'Successful Payments': 'text-[#10B981]',
        'Failed Payments': 'text-[#EF4444]',
    }[card.label] ?? 'text-[#6B7280]';
}
</script>
