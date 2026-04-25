<template>
    <section class="admin-card mt-6 overflow-hidden rounded-[30px]">
        <div class="flex flex-col gap-4 border-b border-slate-200/80 px-6 py-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Products list</p>
                <h3 class="chatgpt-title mt-2 text-3xl text-slate-950">All products</h3>
                <p class="chatgpt-copy mt-3 text-sm">
                    Showing {{ products.pagination.from }}-{{ products.pagination.to }}
                    of {{ products.pagination.total }} items
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('scroll-categories')"
                >
                    Filter
                </button>
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('scroll-inventory')"
                >
                    See all
                </button>
                <button
                    type="button"
                    class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.28)] transition hover:-translate-y-0.5"
                    @click="$emit('scroll-add-product')"
                >
                    + Add product
                </button>
            </div>
        </div>

        <div id="inventory" class="soft-scroll overflow-x-auto">
            <table class="w-full min-w-[920px]">
                <thead class="chatgpt-table-head bg-[#fafbff] text-left text-[11px] uppercase text-slate-400">
                    <tr>
                        <th class="px-6 py-4"><input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#3457ff]" /></th>
                        <th class="px-2 py-4">Product name</th>
                        <th class="px-4 py-4">Category</th>
                        <th class="px-4 py-4">Price</th>
                        <th class="px-4 py-4">Stock</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="product in products.items" :key="product.id" class="border-t border-slate-200/80 bg-white transition hover:bg-[#f8faff]">
                        <td class="px-6 py-4 align-top"><input type="checkbox" class="mt-3 h-4 w-4 rounded border-slate-300 text-[#3457ff]" /></td>
                        <td class="px-2 py-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold text-white" :class="themeClass(product.theme)">
                                    {{ product.initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-slate-900">{{ product.name }}</p>
                                    <p class="mt-1 truncate text-sm text-slate-500">{{ product.tagline }}</p>
                                    <p class="chatgpt-kicker mt-2 text-[11px] uppercase text-slate-300">{{ product.sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-slate-600">{{ product.category }}</td>
                        <td class="px-4 py-4 text-sm font-medium text-slate-700">
                            <div class="space-y-1">
                                <p>{{ product.price }}</p>
                                <p v-if="product.base_price" class="text-xs text-slate-400 line-through">{{ product.base_price }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm font-medium text-slate-700">{{ product.stock }}</td>
                        <td class="px-4 py-4">
                            <span class="chatgpt-pill rounded-full border px-3 py-1 text-xs capitalize" :class="statusClass(product.status)">
                                {{ product.status }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-[#3457ff] transition hover:text-[#2543b8]"
                                    @click="$emit('edit-product', product)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-semibold text-rose-600 transition hover:text-rose-700 disabled:cursor-not-allowed disabled:text-rose-300"
                                    :disabled="deletingProductId === product.id"
                                    @click="$emit('delete-product', product)"
                                >
                                    {{ deletingProductId === product.id ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-4 border-t border-slate-200/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <button type="button" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950 sm:w-auto">
                Previous
            </button>
            <div class="flex items-center justify-center gap-2 text-sm font-medium text-slate-500">
                <button class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#eef3ff] text-[#3457ff]">1</button>
                <button class="flex h-10 w-10 items-center justify-center rounded-2xl transition hover:bg-slate-100">2</button>
                <button class="flex h-10 w-10 items-center justify-center rounded-2xl transition hover:bg-slate-100">3</button>
                <span class="px-1">...</span>
                <button class="flex h-10 w-10 items-center justify-center rounded-2xl transition hover:bg-slate-100">10</button>
            </div>
            <button type="button" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950 sm:w-auto">
                Next
            </button>
        </div>
    </section>
</template>

<script setup>
defineEmits(['delete-product', 'edit-product', 'scroll-add-product', 'scroll-categories', 'scroll-inventory']);

defineProps({
    deletingProductId: {
        type: Number,
        default: null,
    },
    products: {
        type: Object,
        required: true,
    },
});

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
