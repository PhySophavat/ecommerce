<template>
    <section class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1.16fr)_minmax(320px,0.84fr)]">
        <article class="admin-card rounded-[32px] px-6 py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <!-- <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Recent activity</p> -->
                    <!-- <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Latest catalog movement</h3> -->
                    <!-- <p class="chatgpt-copy mt-3 max-w-2xl text-sm">
                        Follow the newest edits, featured updates, and inventory shifts without opening the full table first.
                    </p> -->
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-900"
                        @click="$emit('open-products')"
                    >
                        View all
                    </button>
                    <button
                        type="button"
                        class="admin-primary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5"
                        @click="$emit('open-add-product')"
                    >
                        + Add product
                    </button>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <article
                    v-for="item in activityFeed"
                    :key="`dashboard-activity-${item.id}`"
                    class="admin-muted-panel flex items-start gap-4 rounded-[26px] px-4 py-4"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl" :class="activityBadgeClass(item.tone)">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="activityIcon(item.kind)" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-base font-bold tracking-[-0.03em] text-slate-950">{{ item.title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ item.subtitle }}</p>
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ item.meta }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </article>

        <div class="space-y-6">
            <article v-if="paymentMetrics" class="admin-card rounded-[32px] px-6 py-6">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Payment mix</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">{{ paymentMetrics.label }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ paymentMetrics.bank_methods }}</p>

                <div class="mt-6 grid gap-5 xl:grid-cols-[220px_minmax(0,1fr)] xl:items-center">
                    <div class="group relative mx-auto flex h-48 w-48 items-center justify-center xl:mx-0">
                        <svg class="h-48 w-48 -rotate-90" viewBox="0 0 160 160">
                            <circle cx="80" cy="80" r="62" fill="none" stroke="#edf1fb" stroke-width="16" />
                            <circle
                                cx="80"
                                cy="80"
                                r="62"
                                fill="none"
                                stroke="url(#bankPaymentGradient)"
                                stroke-linecap="round"
                                stroke-width="16"
                                :stroke-dasharray="circleDasharray"
                            />
                            <defs>
                                <linearGradient id="bankPaymentGradient" x1="0%" x2="100%" y1="0%" y2="100%">
                                    <stop offset="0%" stop-color="#5a67f2" />
                                    <stop offset="100%" stop-color="#21b889" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute text-center">
                            <p class="text-5xl font-bold tracking-[-0.05em] text-slate-950">{{ paymentMetrics.bank_percent }}%</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.22em] text-slate-400">Bank pay</p>
                        </div>
                        <div class="pointer-events-none absolute left-1/2 top-full z-10 mt-3 w-52 -translate-x-1/2 rounded-[18px] bg-slate-950 px-4 py-3 text-left text-white opacity-0 shadow-[0_18px_40px_rgba(15,23,42,0.28)] transition duration-200 group-hover:opacity-100">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-300">Payment count</p>
                            <p class="mt-2 text-sm font-semibold">Bank: {{ paymentMetrics.bank_orders }}</p>
                            <p class="mt-1 text-sm font-semibold">Cash: {{ paymentMetrics.cash_orders }}</p>
                            <p class="mt-1 text-sm font-semibold">Total: {{ paymentMetrics.total_orders }}</p>
                        </div>
                    </div>

                    <div class="min-w-0 space-y-4">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[24px] border border-[#dbe6ff] bg-[linear-gradient(180deg,#f8fbff_0%,#edf4ff_100%)] px-5 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Bank orders</p>
                                <p class="mt-2 text-2xl font-bold tracking-[-0.04em] text-slate-950">{{ paymentMetrics.bank_orders }}</p>
                                <p class="mt-2 text-sm text-slate-500">Customers paid using bank methods.</p>
                            </div>
                            <div class="rounded-[24px] border border-[#d8f0e6] bg-[linear-gradient(180deg,#f8fffb_0%,#ebfaf3_100%)] px-5 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Cash orders</p>
                                <p class="mt-2 text-2xl font-bold tracking-[-0.04em] text-slate-950">{{ paymentMetrics.cash_orders }}</p>
                                <p class="mt-2 text-sm text-slate-500">Orders paid with cash or manual settlement.</p>
                            </div>
                            <div class="rounded-[24px] border border-[#f3e1b8] bg-[linear-gradient(180deg,#fffdf8_0%,#fff6e3_100%)] px-5 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Total orders</p>
                                <p class="mt-2 text-2xl font-bold tracking-[-0.04em] text-slate-950">{{ paymentMetrics.total_orders }}</p>
                                <p class="mt-2 text-sm text-slate-500">All customer orders tied to this merchant.</p>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-[#e4e9f8] bg-[#f8faff] px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Bank payment performance</p>
                                    <p class="mt-2 text-lg font-bold tracking-[-0.03em] text-slate-950">
                                        {{ paymentMetrics.bank_percent }}% of customer orders used bank payment
                                    </p>
                                </div>
                                <div class="h-3 w-40 overflow-hidden rounded-full bg-[#e7ecfb]">
                                    <div class="h-full rounded-full bg-[linear-gradient(90deg,#5a67f2,#21b889)]" :style="{ width: `${paymentMetrics.bank_percent}%` }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article v-if="orderAnalytics" class="admin-card rounded-[32px] px-6 py-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Trade analyst</p>
                        <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">{{ orderAnalytics.label }}</h3>
                        <p class="mt-2 text-sm text-slate-500">Track customer orders using a quick timeframe filter.</p>
                    </div>

                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                        <span>Filter</span>
                        <select
                            v-model="selectedOrderFilter"
                            class="rounded-2xl border border-[#d8e7f4] bg-[#f8fbff] px-4 py-2.5 text-sm font-semibold text-slate-700 outline-none transition focus:border-[#1495e8]"
                        >
                            <option
                                v-for="option in orderAnalytics.options"
                                :key="`order-filter-${option.value}`"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </label>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-[24px] border border-[#dbe6ff] bg-[linear-gradient(180deg,#f8fbff_0%,#edf4ff_100%)] px-5 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Orders</p>
                        <p class="mt-2 text-3xl font-bold tracking-[-0.04em] text-slate-950">{{ activeOrderDataset.total }}</p>
                        <p class="mt-2 text-sm text-slate-500">Customer orders in the selected range.</p>
                    </div>
                    <div class="rounded-[24px] border border-[#d8f0e6] bg-[linear-gradient(180deg,#f8fffb_0%,#ebfaf3_100%)] px-5 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Highest bucket</p>
                        <p class="mt-2 text-3xl font-bold tracking-[-0.04em] text-slate-950">{{ highestOrderBar.value }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ highestOrderBar.label }} has the strongest order activity.</p>
                    </div>
                    <div class="rounded-[24px] border border-[#f3e1b8] bg-[linear-gradient(180deg,#fffdf8_0%,#fff6e3_100%)] px-5 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Average bucket</p>
                        <p class="mt-2 text-3xl font-bold tracking-[-0.04em] text-slate-950">{{ averageOrderValue }}</p>
                        <p class="mt-2 text-sm text-slate-500">Average orders across the visible buckets.</p>
                    </div>
                </div>

                <div class="mt-6 rounded-[28px] border border-[#e4edf7] bg-[#fbfdff] px-5 py-5">
                    <div class="grid grid-cols-4 gap-3 sm:gap-4">
                        <div
                            v-for="bar in activeOrderDataset.bars"
                            :key="`order-bar-${selectedOrderFilter}-${bar.label}`"
                            class="flex min-w-0 flex-col items-center gap-3"
                        >
                            <div class="flex h-44 w-full items-end rounded-[22px] bg-[linear-gradient(180deg,#f3f9fd_0%,#eef5ff_100%)] px-3 py-3">
                                <div
                                    class="w-full rounded-[18px] bg-[linear-gradient(180deg,#1495e8_0%,#0d86d6_100%)] transition-all duration-200"
                                    :style="{ height: `${barHeight(bar.value)}%` }"
                                ></div>
                            </div>
                            <div class="text-center">
                                <p class="text-base font-bold text-slate-950">{{ bar.value }}</p>
                                <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ bar.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="admin-card rounded-[32px] px-6 py-6">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Quick stats</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Operational signals</h3>

                <div class="mt-6 space-y-5">
                    <div v-for="item in quickStats" :key="item.label" class="space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ item.label }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ item.value }} items</p>
                            </div>
                            <span class="text-sm font-bold" :class="item.textClass">{{ item.percent }}%</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-[#edf1fb]">
                            <div class="h-full rounded-full" :class="item.barClass" :style="{ width: `${item.percent}%` }"></div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="admin-card rounded-[32px] px-6 py-6">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Category ratios</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Catalog distribution</h3>

                <div class="mt-6 space-y-3">
                    <div
                        v-for="item in categoryMix"
                        :key="item.label"
                        class="admin-muted-panel flex items-center justify-between gap-4 rounded-[24px] px-4 py-3.5"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-white text-sm font-bold text-[#5462ea]">
                                {{ item.label[0] }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ item.label }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-400">{{ item.percent }}% of catalog</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-slate-900">{{ item.value }}</span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';

defineEmits(['open-add-product', 'open-products']);

const props = defineProps({
    orderAnalytics: {
        type: Object,
        default: null,
    },
    paymentMetrics: {
        type: Object,
        default: null,
    },
    products: {
        type: Array,
        required: true,
    },
});

const selectedOrderFilter = ref(props.orderAnalytics?.selected ?? 'today');

const totalProducts = computed(() => props.products.length || 1);
const recentProducts = computed(() => props.products.slice(0, 5));
const featuredCount = computed(() => props.products.filter((product) => product.is_featured).length);
const activeCount = computed(() => props.products.filter((product) => product.status === 'active').length);
const scheduledCount = computed(() => props.products.filter((product) => product.status === 'scheduled').length);
const lowStockCount = computed(() => props.products.filter((product) => Number.parseInt(product.stock, 10) <= 60).length);

const activityFeed = computed(() => recentProducts.value.map((product) => {
    const isLowStock = Number.parseInt(product.stock, 10) <= 60;

    if (product.is_featured) {
        return {
            id: product.id,
            title: 'Featured product refreshed',
            subtitle: `${product.name} is highlighted in ${product.category} and ready for storefront placement.`,
            meta: product.updated_at || 'Today',
            tone: 'violet',
            kind: 'featured',
        };
    }

    if (isLowStock) {
        return {
            id: product.id,
            title: 'Inventory watch triggered',
            subtitle: `${product.name} in ${product.category} is down to ${product.stock} units.`,
            meta: product.updated_at || 'Today',
            tone: 'amber',
            kind: 'inventory',
        };
    }

    if (product.status === 'draft') {
        return {
            id: product.id,
            title: 'Draft product updated',
            subtitle: `${product.name} was revised and remains in draft for another pass.`,
            meta: product.updated_at || 'Today',
            tone: 'slate',
            kind: 'draft',
        };
    }

    return {
        id: product.id,
        title: 'Catalog entry updated',
        subtitle: `${product.name} was updated in ${product.category} and is visible in the active catalog.`,
        meta: product.updated_at || 'Today',
        tone: 'emerald',
        kind: 'catalog',
    };
}));

const quickStats = computed(() => [
    {
        label: 'Featured placements',
        value: featuredCount.value,
        percent: ratio(featuredCount.value),
        barClass: 'bg-[linear-gradient(90deg,#5a67f2,#7b86ff)]',
        textClass: 'text-[#5a67f2]',
    },
    {
        label: 'Active listings',
        value: activeCount.value,
        percent: ratio(activeCount.value),
        barClass: 'bg-[linear-gradient(90deg,#22b889,#6fd8b7)]',
        textClass: 'text-[#22b889]',
    },
    {
        label: 'Scheduled launches',
        value: scheduledCount.value,
        percent: ratio(scheduledCount.value),
        barClass: 'bg-[linear-gradient(90deg,#f0a31f,#f6c35f)]',
        textClass: 'text-[#f0a31f]',
    },
    {
        label: 'Low stock watch',
        value: lowStockCount.value,
        percent: ratio(lowStockCount.value),
        barClass: 'bg-[linear-gradient(90deg,#ef6b73,#f9a2a6)]',
        textClass: 'text-[#ef6b73]',
    },
]);

const categoryMix = computed(() => {
    const counts = props.products.reduce((collection, product) => {
        const key = product.category || 'Uncategorized';
        collection.set(key, (collection.get(key) ?? 0) + 1);

        return collection;
    }, new Map());

    return [...counts.entries()]
        .map(([label, value]) => ({
            label,
            value,
            percent: ratio(value),
        }))
        .sort((left, right) => right.value - left.value)
        .slice(0, 4);
});

const circleDasharray = computed(() => {
    const radius = 62;
    const circumference = 2 * Math.PI * radius;
    const percent = Math.max(0, Math.min(100, Number(props.paymentMetrics?.bank_percent ?? 0)));
    const filled = (percent / 100) * circumference;

    return `${filled} ${circumference}`;
});

const activeOrderDataset = computed(() => {
    const fallback = { total: 0, bars: [] };

    return props.orderAnalytics?.datasets?.[selectedOrderFilter.value] ?? fallback;
});

const highestOrderBar = computed(() => {
    const bars = activeOrderDataset.value.bars ?? [];

    if (!bars.length) {
        return { label: '-', value: 0 };
    }

    return bars.reduce((winner, current) => current.value > winner.value ? current : winner, bars[0]);
});

const averageOrderValue = computed(() => {
    const bars = activeOrderDataset.value.bars ?? [];

    if (!bars.length) {
        return '0';
    }

    const total = bars.reduce((sum, bar) => sum + Number(bar.value || 0), 0);

    return (total / bars.length).toFixed(1);
});

function ratio(value) {
    if (value <= 0) {
        return 0;
    }

    return Math.max(8, Math.min(100, Math.round((value / totalProducts.value) * 100)));
}

function barHeight(value) {
    const bars = activeOrderDataset.value.bars ?? [];
    const max = Math.max(...bars.map((bar) => Number(bar.value || 0)), 1);

    if (max <= 0) {
        return 12;
    }

    return Math.max(12, Math.round((Number(value || 0) / max) * 100));
}

function activityBadgeClass(tone) {
    return {
        violet: 'bg-[#eef1ff] text-[#5764ee]',
        emerald: 'bg-[#e8fbf4] text-[#1fb586]',
        amber: 'bg-[#fff4df] text-[#ec9d18]',
        slate: 'bg-[#eef2f8] text-[#64748b]',
    }[tone] ?? 'bg-[#eef1ff] text-[#5764ee]';
}

function activityIcon(kind) {
    return {
        featured: 'M12 17.25 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.25Z',
        inventory: 'M20 7 9 18l-5-5',
        draft: 'M4 19.5V15l9.5-9.5 4.5 4.5-9.5 9.5H4Z',
        catalog: 'M4 7.5 12 3l8 4.5-8 4.5L4 7.5ZM4 7.5V16.5L12 21l8-4.5V7.5',
    }[kind] ?? 'M12 5v14M5 12h14';
}
</script>
