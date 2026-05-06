<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 rounded-[32px] bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Shop page</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Browse products with clean filters</h1>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <SearchBar v-model="search" placeholder="Search by product, category, merchant" />
                <select v-model="sortBy" class="rounded-full border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none focus:border-[#A25F88]">
                    <option value="featured">Sort: Featured</option>
                    <option value="price-low">Price: Low to high</option>
                    <option value="price-high">Price: High to low</option>
                    <option value="rating">Top rating</option>
                    <option value="new">Newest</option>
                </select>
                <Button variant="secondary" class="lg:hidden" @click="mobileFilters = true">Filters</Button>
            </div>
        </div>

        <div class="grid gap-8 xl:grid-cols-[300px_minmax(0,1fr)]">
            <div class="hidden xl:block">
                <FilterSidebar v-model="filters" :categories="store.categories" :merchants="store.merchants" @reset="resetFilters" />
            </div>

            <div>
                <div class="mb-5 flex items-center justify-between text-sm text-[#6B7280]">
                    <span>{{ filteredProducts.length }} results</span>
                    <span>Responsive product grid</span>
                </div>

                <ProductGrid :products="filteredProducts" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
            </div>
        </div>

        <div v-if="mobileFilters" class="fixed inset-0 z-50 bg-[#111827]/35 px-4 py-6 xl:hidden" @click.self="mobileFilters = false">
            <div class="mx-auto max-w-md">
                <FilterSidebar v-model="filters" :categories="store.categories" :merchants="store.merchants" @reset="resetFilters" />
                <div class="mt-4">
                    <Button block @click="mobileFilters = false">Apply filters</Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Button from '../components/Button.vue';
import FilterSidebar from '../components/FilterSidebar.vue';
import ProductGrid from '../components/ProductGrid.vue';
import SearchBar from '../components/SearchBar.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const route = useRoute();
const mobileFilters = ref(false);
const search = ref('');
const sortBy = ref('featured');
const filters = ref(defaultFilters());

onMounted(async () => {
    await store.initialize();
    const category = route.query.category;
    if (typeof category === 'string') {
        filters.value.category = category;
    }
});

const filteredProducts = computed(() => {
    let items = [...store.products];
    const term = search.value.trim().toLowerCase();

    if (term) {
        items = items.filter((product) =>
            [product.name, product.category, product.merchant_name].some((value) => String(value || '').toLowerCase().includes(term))
        );
    }

    if (filters.value.category) {
        items = items.filter((product) => product.category_slug === filters.value.category);
    }

    if (filters.value.merchant) {
        items = items.filter((product) => product.merchant_name === filters.value.merchant);
    }

    if (filters.value.rating) {
        items = items.filter((product) => Number(product.rating_value || 0) >= Number(filters.value.rating));
    }

    if (filters.value.minPrice) {
        items = items.filter((product) => Number(product.price_value) >= Number(filters.value.minPrice));
    }

    if (filters.value.maxPrice) {
        items = items.filter((product) => Number(product.price_value) <= Number(filters.value.maxPrice));
    }

    switch (sortBy.value) {
        case 'price-low':
            items.sort((a, b) => Number(a.price_value) - Number(b.price_value));
            break;
        case 'price-high':
            items.sort((a, b) => Number(b.price_value) - Number(a.price_value));
            break;
        case 'rating':
            items.sort((a, b) => Number(b.rating_value) - Number(a.rating_value));
            break;
        case 'new':
            items.reverse();
            break;
        default:
            items.sort((a, b) => Number(b.is_featured) - Number(a.is_featured));
            break;
    }

    return items;
});

function defaultFilters() {
    return {
        category: '',
        minPrice: '',
        maxPrice: '',
        rating: '',
        color: '',
        size: '',
        merchant: '',
    };
}

function resetFilters() {
    filters.value = defaultFilters();
}
</script>
