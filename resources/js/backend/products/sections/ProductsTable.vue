<template>
    <section class="admin-card mt-6 overflow-hidden rounded-[32px]">
        <div class="flex flex-col gap-2 border-b border-[#e2e7f6] px-3 py-3">
            <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                <h3 class="font-bold text-base text-slate-950">{{ sectionTitle }}</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="selectedCategory"
                        class="min-w-[120px] rounded border border-[#dde4f7] bg-white px-2 py-1 text-xs font-semibold text-slate-700 outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                            {{ category.name }}
                        </option>
                    </select>
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-semibold text-[#2563eb] border border-[#dbeafe] bg-[#f0f6ff] hover:bg-[#e0eaff]"
                        @click="$emit('scroll-add-product')"
                    >
                        + Add
                    </button>
                </div>
            </div>
        </div>

        <div id="inventory" class="soft-scroll overflow-x-auto px-2 pb-2 pt-2">
            <table class="w-full min-w-[600px] text-xs">
                <thead class="text-left text-[10px] uppercase text-slate-400">
                    <tr>
                        <th class="px-2 py-1">Product</th>
                        <th class="px-2 py-1">Price</th>
                        <th class="px-2 py-1">Stock</th>
                        <th class="px-2 py-1">Status</th>
                        <th class="px-2 py-1 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filteredProducts.length">
                        <td colspan="5" class="px-2 py-4 text-center text-xs text-slate-400">
                            No products.
                        </td>
                    </tr>
                    <tr
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="border-b border-[#f1f5fa]"
                    >
                        <td class="px-2 py-2 font-bold text-slate-900">{{ product.name }}</td>
                        <td class="px-2 py-2">{{ product.price }}</td>
                        <td class="px-2 py-2">{{ product.stock }}</td>
                        <td class="px-2 py-2">
                            <span class="rounded px-2 py-1 text-[10px] font-bold uppercase" :class="statusClass(product.status)">
                                {{ product.status }}
                            </span>
                        </td>
                        <td class="px-2 py-2 text-right">
                            <button
                                type="button"
                                class="rounded bg-[#2563eb] px-2 py-1 text-xs font-semibold text-white hover:bg-[#174ea6]"
                                @click="$emit('edit-product', product)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-200 ml-1"
                                :disabled="deletingProductId === product.id"
                                @click="$emit('delete-product', product)"
                            >
                                {{ deletingProductId === product.id ? '...' : 'Del' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-[#e2e7f6] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">{{ footerMessage }}</p>
            <button
                v-if="selectedCategory"
                type="button"
                class="rounded-2xl border border-[#d8e0f5] bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-900"
                @click="selectedCategory = ''"
            >
                Clear filter
            </button>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';

defineEmits(['delete-product', 'edit-product', 'scroll-add-product', 'scroll-categories', 'scroll-inventory']);

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    deletingProductId: {
        type: Number,
        default: null,
    },
    products: {
        type: Object,
        required: true,
    },
    screen: {
        type: String,
        default: 'products',
    },
});

const sectionKicker = computed(() => props.screen === 'featured-products' ? 'Storefront highlights' : 'Product list');
const sectionTitle = computed(() => props.screen === 'featured-products' ? 'Featured products' : 'All products');
const itemLabel = computed(() => props.screen === 'featured-products' ? 'featured items' : 'items');
const selectedCategory = ref('');
const filteredProducts = computed(() => {
    if (!selectedCategory.value) {
        return props.products.items ?? [];
    }

    return (props.products.items ?? []).filter((product) => String(product.category_id ?? '') === selectedCategory.value);
});
const selectedCategoryLabel = computed(() => (
    props.categories.find((category) => String(category.id) === selectedCategory.value)?.name ?? ''
));
const summaryCopy = computed(() => {
    const total = props.products.pagination?.total ?? props.products.items?.length ?? 0;

    if (selectedCategoryLabel.value) {
        return `Showing ${filteredProducts.value.length} of ${total} ${itemLabel.value} in ${selectedCategoryLabel.value}.`;
    }

    if (!total) {
        return `Showing 0 ${itemLabel.value}.`;
    }

    return `Showing 1-${filteredProducts.value.length} of ${total} ${itemLabel.value}.`;
});
const emptyStateMessage = computed(() => {
    if (selectedCategoryLabel.value) {
        return props.screen === 'featured-products'
            ? `No featured products found in ${selectedCategoryLabel.value}.`
            : `No products found in ${selectedCategoryLabel.value}.`;
    }

    return props.screen === 'featured-products'
        ? 'No featured products yet. Open a product and mark it as featured to show it here.'
        : 'No products found.';
});
const footerMessage = computed(() => {
    if (selectedCategoryLabel.value) {
        return `${filteredProducts.value.length} ${filteredProducts.value.length === 1 ? 'item matches' : 'items match'} ${selectedCategoryLabel.value}.`;
    }

    return 'All records for this view are loaded.';
});

function statusClass(status) {
    return {
        active: 'bg-[#e8fbf4] text-[#1fb586]',
        scheduled: 'bg-[#eef4ff] text-[#5462ea]',
        draft: 'bg-[#fff4df] text-[#ee9d15]',
    }[status] ?? 'bg-[#eef2f8] text-[#64748b]';
}

function themeClass(theme) {
    return {
        cobalt: 'bg-[linear-gradient(135deg,#5865f2,#8791ff)]',
        forest: 'bg-[linear-gradient(135deg,#1fb586,#72dbbb)]',
        sand: 'bg-[linear-gradient(135deg,#d18c17,#f4c15f)]',
        graphite: 'bg-[linear-gradient(135deg,#303a66,#6473a1)]',
        midnight: 'bg-[linear-gradient(135deg,#2b3d8f,#5967ea)]',
        sky: 'bg-[linear-gradient(135deg,#37a4f5,#82d2ff)]',
        ink: 'bg-[linear-gradient(135deg,#27314f,#536387)]',
        plum: 'bg-[linear-gradient(135deg,#7a56e8,#bb9eff)]',
        denim: 'bg-[linear-gradient(135deg,#315be8,#73a8ff)]',
        lilac: 'bg-[linear-gradient(135deg,#8f63f4,#dbc8ff)]',
    }[theme] ?? 'bg-[linear-gradient(135deg,#445078,#9aa6cb)]';
}
</script>
