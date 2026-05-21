<template>
    <section class="space-y-5 bg-[#F8FAFC]">
        <article class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] px-5 py-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:px-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">{{ merchantDashboard.hero.eyebrow }}</p>
                    <h2 class="mt-1.5 text-[1.8rem] font-bold tracking-[-0.04em] text-[#111827] sm:text-[2rem]">{{ merchantDashboard.hero.title }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">Track balances, order health, payment mix, and product performance from one merchant workspace.</p>
                </div>

                <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                    <label class="flex min-h-[44px] items-center gap-2 rounded-[14px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-2.5 text-sm font-semibold text-[#111827]">
                        <span class="text-[#6B7280]">Range</span>
                        <select v-model="selectedRange" class="bg-transparent pr-6 outline-none">
                            <option v-for="item in merchantDashboard.date_ranges" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>
                    <button type="button" class="inline-flex min-h-[44px] min-w-[132px] items-center justify-center rounded-[14px] border border-[#E5E7EB] bg-white px-4 py-2.5 text-sm font-semibold text-[#6B7280] transition hover:border-[#D8C0CF] hover:text-[#A25F88]" @click="$emit('open-balance')">
                        Open balance
                    </button>
                    <button type="button" class="inline-flex min-h-[44px] min-w-[148px] items-center justify-center rounded-[14px] bg-[#A25F88] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]" @click="$emit('open-products')">
                        Open products
                    </button>
                </div>
            </div>
        </article>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in primarySummaryCards"
                :key="card.label"
                class="flex min-h-[148px] flex-col justify-between rounded-[20px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" :class="primaryLabelClass(card)">{{ card.label }}</p>
                        <p class="mt-3 text-[1.9rem] font-black tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px]" :class="primaryIconWrapClass(card)">
                        <span class="text-sm font-bold" :class="primaryIconClass(card)">{{ primaryIcon(card.label) }}</span>
                    </span>
                </div>
                <p class="mt-4 text-sm leading-6 text-[#6B7280]">{{ card.description }}</p>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Finance overview</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Balance and wallet overview</h3>
                    </div>
                    <span class="rounded-full bg-[#F6ECF2] px-3 py-1 text-[11px] font-semibold text-[#A25F88]">{{ financeCompactCards.length }} stats</span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="card in financeCompactCards"
                        :key="card.label"
                        class="rounded-[18px] border border-[#E5E7EB] bg-[#F8FAFC] p-4"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" :class="compactLabelClass(card)">{{ card.label }}</p>
                        <p class="mt-2 text-[1.15rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ card.description }}</p>
                    </article>
                </div>
            </article>

            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Operations snapshot</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Orders and payments</h3>
                    </div>
                    <span class="rounded-full bg-[#F6ECF2] px-3 py-1 text-[11px] font-semibold text-[#A25F88]">{{ orderCompactCards.length }} stats</span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="card in orderCompactCards"
                        :key="card.label"
                        class="rounded-[18px] border border-[#E5E7EB] bg-[#F8FAFC] p-4"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" :class="compactLabelClass(card)">{{ card.label }}</p>
                        <p class="mt-2 text-[1.15rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ card.description }}</p>
                    </article>
                </div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-3">
            <MerchantDonutChart title="Order status" eyebrow="Order quality" :items="activeDataset.order_status_summary" />
            <MerchantDonutChart title="Payment count by bank" eyebrow="Payment mix" :items="activeDataset.payment_count_by_bank" />
            <MerchantDonutChart title="Transaction flow" eyebrow="Finance movement" :items="activeDataset.transaction_flow" value-type="currency" />
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.95fr)]">
            <MerchantMixedChart
                title="Sales over time"
                eyebrow="Sales analytics"
                :points="activeDataset.sales_over_time"
                bar-label="Total sales"
                line-label="Total orders"
            />
            <MerchantAreaChart title="Cumulative sales over time" eyebrow="Revenue trend" :points="activeDataset.cumulative_sales" />
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Product sales</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Top 5 product sales</h3>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-[18px] border border-[#E5E7EB]">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-[#6B7280]">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Product name</th>
                                <th class="px-4 py-3 font-semibold">Sold quantity</th>
                                <th class="px-4 py-3 text-right font-semibold">Total sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in topProductRows" :key="item.key" class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]">
                                <td class="px-4 py-3.5 font-semibold" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#111827]'">
                                    {{ item.product_name }}
                                </td>
                                <td class="px-4 py-3.5" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#6B7280]'">
                                    {{ item.sold_quantity }}
                                </td>
                                <td class="px-4 py-3.5 text-right font-semibold" :class="item.isPlaceholder ? 'text-[#9CA3AF]' : 'text-[#111827]'">
                                    {{ item.total_sales }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                <article v-for="card in activeDataset.extra_cards" :key="card.label" class="rounded-[20px] border border-[#E5E7EB] bg-[#FFFFFF] px-4 py-4 shadow-[0_8px_22px_rgba(15,23,42,0.04)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#A25F88]">{{ card.label }}</p>
                    <p class="mt-2 text-[1.35rem] font-black tracking-[-0.04em] text-[#111827]">{{ card.value }}</p>
                </article>
            </div>
        </section>

        <article class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Latest finance transactions</p>
                    <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Outgoing and incoming flow</h3>
                </div>
                <span class="rounded-full bg-[#F8FAFC] px-3 py-1 text-[11px] font-semibold text-[#6B7280]">{{ activeDataset.recent_transactions?.length ?? 0 }} records</span>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-[920px] w-full text-left text-sm">
                    <thead class="bg-[#F8FAFC] text-[#6B7280]">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Transaction ID</th>
                            <th class="px-4 py-3 font-semibold">Order ID</th>
                            <th class="px-4 py-3 font-semibold">Type</th>
                            <th class="px-4 py-3 text-right font-semibold">Amount</th>
                            <th class="px-4 py-3 font-semibold">Currency</th>
                            <th class="px-4 py-3 font-semibold">Payment method</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Description</th>
                            <th class="px-4 py-3 font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!activeDataset.recent_transactions?.length">
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-[#6B7280]">No finance transactions available yet.</td>
                        </tr>
                        <tr
                            v-for="item in activeDataset.recent_transactions"
                            :key="item.transaction_id"
                            class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]"
                        >
                            <td class="px-4 py-3.5 font-semibold text-[#111827]">{{ item.transaction_id }}</td>
                            <td class="px-4 py-3.5 text-[#6B7280]">{{ item.order_id }}</td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold" :class="item.type === 'IN' ? 'bg-[#F6EAF1] text-[#A25F88]' : 'bg-[#FFF4EC] text-[#C56A54]'">
                                    {{ item.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-[#111827]">{{ item.amount }}</td>
                            <td class="px-4 py-3.5 text-[#6B7280]">{{ item.currency }}</td>
                            <td class="px-4 py-3.5 text-[#111827]">{{ item.payment_method }}</td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold" :class="statusClass(item.status)">{{ item.status }}</span>
                            </td>
                            <td class="max-w-[220px] px-4 py-3.5 text-[#6B7280]">
                                <span class="block truncate">{{ item.description || `${item.payment_method} transaction for ${item.order_id}` }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-[#6B7280]">{{ item.date }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>
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
        Pending: 'bg-[#FFF7ED] text-[#D97706]',
        Cancelled: 'bg-[#F3F4F6] text-[#9CA3AF]',
    }[status] ?? 'bg-[#F3F4F6] text-[#9CA3AF]';
}

function primaryIcon(label) {
    return {
        'Total Balance': '$',
        'Transaction In': 'IN',
        'Transaction Out': 'OUT',
        'Successful Orders': 'OK',
    }[label] ?? '•';
}

function primaryIconWrapClass(card) {
    return {
        'Total Balance': 'bg-[#F6EAF1]',
        'Transaction In': 'bg-[#ECFDF5]',
        'Transaction Out': 'bg-[#FFF7ED]',
        'Successful Orders': 'bg-[#EFF6FF]',
    }[card.label] ?? 'bg-[#F8FAFC]';
}

function primaryIconClass(card) {
    return {
        'Total Balance': 'text-[#A25F88]',
        'Transaction In': 'text-[#10B981]',
        'Transaction Out': 'text-[#D97706]',
        'Successful Orders': 'text-[#2563EB]',
    }[card.label] ?? 'text-[#6B7280]';
}

function primaryLabelClass(card) {
    return {
        'Total Balance': 'text-[#A25F88]',
        'Transaction In': 'text-[#10B981]',
        'Transaction Out': 'text-[#D97706]',
        'Successful Orders': 'text-[#2563EB]',
    }[card.label] ?? 'text-[#6B7280]';
}

function compactLabelClass(card) {
    return {
        'Balance KHR': 'text-[#A25F88]',
        'Total Transactions': 'text-[#2563EB]',
        'Failed Orders': 'text-[#EF4444]',
        'Successful Payments': 'text-[#10B981]',
        'Failed Payments': 'text-[#EF4444]',
    }[card.label] ?? 'text-[#6B7280]';
}
</script>
