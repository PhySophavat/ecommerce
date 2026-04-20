<template>
    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
        <article class="admin-card rounded-[30px] px-6 py-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Recent products</p>
                    <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Latest catalog activity</h3>
                    <p class="chatgpt-copy mt-3 max-w-2xl text-sm">Use the products area when you need full table controls or want to create a new item.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                        @click="$emit('open-products')"
                    >
                        View products
                    </button>
                    <button
                        type="button"
                        class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.24)] transition hover:-translate-y-0.5"
                        @click="$emit('open-add-product')"
                    >
                        + Add product
                    </button>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <article
                    v-for="product in recentProducts"
                    :key="`dashboard-recent-${product.id}`"
                    class="flex items-start gap-3 rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold text-white" :class="themeClass(product.theme)">
                        {{ product.initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-slate-900">{{ product.name }}</p>
                                <p class="chatgpt-copy mt-1 truncate text-sm">{{ product.category }}</p>
                            </div>
                            <span class="chatgpt-pill rounded-full border px-2.5 py-1 text-[10px] uppercase" :class="statusClass(product.status)">
                                {{ product.status }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-500">
                            <span>{{ product.price }}</span>
                            <span>{{ product.updated_at }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </article>

        <div class="space-y-6">
            <article class="admin-card rounded-[30px] px-6 py-6">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Inventory watch</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Low stock products</h3>

                <div class="mt-6 space-y-3">
                    <div
                        v-for="product in lowStockProducts"
                        :key="`dashboard-stock-${product.id}`"
                        class="flex items-center justify-between gap-4 rounded-[22px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ product.name }}</p>
                            <p class="chatgpt-copy mt-1 text-sm">{{ product.category }}</p>
                        </div>
                        <div class="text-right">
                            <p class="chatgpt-title text-lg text-slate-950">{{ product.stock }}</p>
                            <p class="chatgpt-kicker mt-1 text-[10px] uppercase text-slate-400">units left</p>
                        </div>
                    </div>
                </div>
            </article>

            <article class="admin-card rounded-[30px] px-6 py-6">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Publishing mix</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Status breakdown</h3>

                <div class="mt-6 grid gap-3">
                    <div
                        v-for="item in statusSummary"
                        :key="item.label"
                        class="flex items-center justify-between rounded-[22px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4"
                    >
                        <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                        <span class="chatgpt-title text-lg text-slate-950">{{ item.value }}</span>
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

const recentProducts = computed(() => props.products.slice(0, 5));

const lowStockProducts = computed(() =>
    [...props.products]
        .sort((left, right) => Number.parseInt(left.stock, 10) - Number.parseInt(right.stock, 10))
        .slice(0, 5),
);

const statusSummary = computed(() => [
    { label: 'Active', value: props.products.filter((product) => product.status === 'active').length },
    { label: 'Draft', value: props.products.filter((product) => product.status === 'draft').length },
    { label: 'Scheduled', value: props.products.filter((product) => product.status === 'scheduled').length },
]);

function statusClass(status) {
    return {
        active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        scheduled: 'border-sky-200 bg-sky-50 text-sky-700',
        draft: 'border-amber-200 bg-amber-50 text-amber-700',
    }[status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
}

function themeClass(theme) {
    return {
        cobalt: 'bg-[linear-gradient(135deg,#3457ff,#6ea8ff)]',
        forest: 'bg-[linear-gradient(135deg,#0f766e,#34d399)]',
        sand: 'bg-[linear-gradient(135deg,#a16207,#fbbf24)]',
        graphite: 'bg-[linear-gradient(135deg,#0f172a,#475569)]',
        midnight: 'bg-[linear-gradient(135deg,#111827,#4338ca)]',
        sky: 'bg-[linear-gradient(135deg,#38bdf8,#bfdbfe)]',
        ink: 'bg-[linear-gradient(135deg,#1e293b,#334155)]',
        plum: 'bg-[linear-gradient(135deg,#7c3aed,#c084fc)]',
        denim: 'bg-[linear-gradient(135deg,#1d4ed8,#60a5fa)]',
        lilac: 'bg-[linear-gradient(135deg,#8b5cf6,#e9d5ff)]',
    }[theme] ?? 'bg-[linear-gradient(135deg,#334155,#94a3b8)]';
}
</script>
