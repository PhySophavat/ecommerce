<template>
    <section class="mt-6 overflow-hidden rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
        <div class="border-b border-[#E5E7EB] px-4 py-4 sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">{{ sectionKicker }}</p>
                    <h3 class="mt-1 text-[1.2rem] font-bold tracking-[-0.03em] text-[#111827]">{{ sectionTitle }}</h3>
                    <p class="mt-1 text-sm text-[#6B7280]">{{ summaryCopy }}</p>
                </div>

                <button
                    type="button"
                    class="inline-flex min-h-[42px] items-center justify-center rounded-[12px] bg-[#A25F88] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                    @click="$emit('scroll-add-product')"
                >
                    + Add product
                </button>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1.3fr)_180px_180px_auto]">
                <label class="flex min-h-[44px] items-center gap-2 rounded-[14px] border border-[#E5E7EB] bg-[#F8FAFC] px-3">
                    <svg class="h-4 w-4 shrink-0 text-[#9CA3AF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path stroke-linecap="round" d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input
                        v-model.trim="searchQuery"
                        type="text"
                        placeholder="Search product name"
                        class="w-full bg-transparent text-sm text-[#111827] outline-none placeholder:text-[#9CA3AF]"
                    >
                </label>

                <select
                    v-model="selectedStatus"
                    class="min-h-[44px] rounded-[14px] border border-[#E5E7EB] bg-[#F8FAFC] px-3 text-sm text-[#111827] outline-none"
                >
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="approved">Approved</option>
                    <option value="draft">Draft</option>
                    <option value="rejected">Rejected</option>
                    <option value="pending">Pending</option>
                </select>

                <select
                    v-model="selectedCategory"
                    class="min-h-[44px] rounded-[14px] border border-[#E5E7EB] bg-[#F8FAFC] px-3 text-sm text-[#111827] outline-none"
                >
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] border border-[#E5E7EB] bg-white px-4 text-sm font-semibold text-[#6B7280] transition hover:border-[#D8C0CF] hover:text-[#A25F88]"
                    @click="clearFilters"
                >
                    Clear filters
                </button>
            </div>
        </div>

        <div class="soft-scroll overflow-x-auto">
            <table class="min-w-[860px] w-full text-sm">
                <thead class="sticky top-0 z-10 bg-[#F8FAFC] text-left">
                    <tr>
                        <th class="px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Product</th>
                        <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Category</th>
                        <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Price</th>
                        <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Stock</th>
                        <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Status</th>
                        <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!filteredProducts.length">
                        <td colspan="6" class="px-6 py-14 text-center">
                            <div class="mx-auto max-w-sm">
                                <p class="text-base font-semibold text-[#111827]">No products found</p>
                                <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ emptyStateMessage }}</p>
                            </div>
                        </td>
                    </tr>
                    <tr
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]"
                    >
                        <td class="px-5 py-4">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-[#111827]">{{ product.name }}</p>
                                <p class="mt-1 text-xs text-[#6B7280]">SKU: {{ product.sku || 'No SKU' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-[#6B7280]">{{ product.category_name || 'Uncategorized' }}</td>
                        <td class="px-4 py-4 font-semibold text-[#111827]">{{ product.price }}</td>
                        <td class="px-4 py-4 text-[#111827]">{{ product.stock }}</td>
                        <td class="px-4 py-4">
                            <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]" :class="statusClass(product.status)">
                                {{ product.status }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[36px] items-center justify-center rounded-[10px] border border-[#BFDBFE] bg-[#EFF6FF] px-3 text-xs font-semibold text-[#2563EB] transition hover:border-[#93C5FD] hover:bg-[#DBEAFE]"
                                    @click="$emit('edit-product', product)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex min-h-[36px] items-center justify-center rounded-[10px] border border-[#FECACA] bg-[#FEF2F2] px-3 text-xs font-semibold text-[#DC2626] transition hover:border-[#FCA5A5] hover:bg-[#FEE2E2]"
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

        <div class="flex flex-col gap-2 border-t border-[#E5E7EB] px-5 py-4 text-sm text-[#6B7280] sm:flex-row sm:items-center sm:justify-between">
            <p>{{ footerMessage }}</p>
            <p class="text-xs uppercase tracking-[0.16em] text-[#9CA3AF]">Responsive table view</p>
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
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedStatus = ref('');

const filteredProducts = computed(() => {
    return (props.products.items ?? []).filter((product) => {
        const matchesCategory = !selectedCategory.value || String(product.category_id ?? '') === selectedCategory.value;
        const matchesStatus = !selectedStatus.value || String(product.status ?? '').toLowerCase() === selectedStatus.value;
        const matchesSearch = !searchQuery.value || String(product.name ?? '').toLowerCase().includes(searchQuery.value.toLowerCase());

        return matchesCategory && matchesStatus && matchesSearch;
    });
});

const selectedCategoryLabel = computed(() => (
    props.categories.find((category) => String(category.id) === selectedCategory.value)?.name ?? ''
));

const hasActiveFilters = computed(() => Boolean(searchQuery.value || selectedCategory.value || selectedStatus.value));

const summaryCopy = computed(() => {
    const total = props.products.pagination?.total ?? props.products.items?.length ?? 0;

    if (!total) {
        return `Showing 0 ${itemLabel.value}.`;
    }

    return `${filteredProducts.value.length} of ${total} ${itemLabel.value} visible in this view.`;
});

const emptyStateMessage = computed(() => {
    if (selectedCategoryLabel.value || selectedStatus.value || searchQuery.value) {
        return 'Try changing the search text or clearing one of the active filters.';
    }

    return props.screen === 'featured-products'
        ? 'No featured products yet. Mark a product as featured to display it here.'
        : 'Products will appear here once this merchant starts creating listings.';
});

const footerMessage = computed(() => {
    if (hasActiveFilters.value) {
        return `${filteredProducts.value.length} ${filteredProducts.value.length === 1 ? 'item matches' : 'items match'} the current filters.`;
    }

    return 'All records for this view are loaded.';
});

function clearFilters() {
    searchQuery.value = '';
    selectedCategory.value = '';
    selectedStatus.value = '';
}

function statusClass(status) {
    return {
        active: 'bg-[#ECFDF5] text-[#10B981]',
        approved: 'bg-[#EEF2FF] text-[#64748B]',
        draft: 'bg-[#FFF7ED] text-[#D97706]',
        rejected: 'bg-[#FEF2F2] text-[#DC2626]',
        pending: 'bg-[#F5F3FF] text-[#8B5CF6]',
    }[String(status ?? '').toLowerCase()] ?? 'bg-[#F3F4F6] text-[#6B7280]';
}
</script>
