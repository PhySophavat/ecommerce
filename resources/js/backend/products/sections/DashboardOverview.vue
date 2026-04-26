<template>
    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.16fr)_minmax(300px,0.84fr)]">
        <article class="admin-card rounded-[32px] px-6 py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Recent activity</p>
                    <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Latest catalog movement</h3>
                    <p class="chatgpt-copy mt-3 max-w-2xl text-sm">
                        Follow the newest edits, featured updates, and inventory shifts without opening the full table first.
                    </p>
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
import { computed } from 'vue';

defineEmits(['open-add-product', 'open-products']);

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
});

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

function ratio(value) {
    if (value <= 0) {
        return 0;
    }

    return Math.max(8, Math.min(100, Math.round((value / totalProducts.value) * 100)));
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
