<template>
    <div class="mx-auto w-full bg-[#F8FAFC] px-4 py-8 lg:w-[80%] sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-4 rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-[0_12px_30px_rgba(17,24,39,0.05)] lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#A78B9A]">Shop page</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Browse products with clean filters</h1>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <SearchBar v-model="search" placeholder="Search by product, category, merchant" />
                <select v-model="sortBy" class="rounded-full border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none transition duration-200 focus:border-[#E8B4CF] focus:shadow-[0_0_0_4px_rgba(162,95,136,0.10)]">
                    <option value="featured">Sort: Featured</option>
                    <option value="price-low">Price: Low to high</option>
                    <option value="price-high">Price: High to low</option>
                    <option value="rating">Top rating</option>
                    <option value="new">Newest</option>
                </select>
            </div>
        </div>

        <div>
            <div class="mb-5 flex items-center justify-between text-sm text-[#6B7280]">
                <span>{{ filteredProducts.length }} results</span>
                <span>Responsive product grid</span>
            </div>

            <ProductGrid :products="filteredProducts" :wishlist-ids="store.wishlist" :columns="4" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ProductGrid from '../components/ProductGrid.vue';
import SearchBar from '../components/SearchBar.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const route = useRoute();
const router = useRouter();
const search = ref('');
const sortBy = ref('featured');
const filters = ref(defaultFilters());

watch(
    () => route.query,
    () => {
        syncRouteFilters();
    },
    { deep: true, immediate: true }
);

watch(
    () => filters.value.category,
    (category) => {
        const normalizedCategory = typeof category === 'string' ? category : '';
        const routeCategory = typeof route.query.category === 'string' ? route.query.category : '';

        if (normalizedCategory === routeCategory) {
            return;
        }

        const nextQuery = { ...route.query };

        if (normalizedCategory) {
            nextQuery.category = normalizedCategory;
        } else {
            delete nextQuery.category;
        }

        router.replace({
            path: route.path,
            query: nextQuery,
        });
    }
);

const categoryProducts = computed(() => {
    if (!filters.value.category) {
        return store.products;
    }

    return store.products.filter((product) => product.category_slug === filters.value.category);
});

const availableColors = computed(() => uniqueOptions(categoryProducts.value.flatMap((product) => product.color_options ?? [])));
const availableSizes = computed(() => uniqueOptions(categoryProducts.value.flatMap((product) => product.size_options ?? [])));

const availableMerchants = computed(() => {
    const items = categoryProducts.value;

    if (!filters.value.color && !filters.value.size) {
        return uniqueOptions(items.map((product) => product.merchant_name).filter(Boolean));
    }

    return uniqueOptions(
        items
            .filter((product) => matchesOption(product.color_options, filters.value.color))
            .filter((product) => matchesOption(product.size_options, filters.value.size))
            .map((product) => product.merchant_name)
            .filter(Boolean)
    );
});

watch(
    [availableColors, availableSizes, availableMerchants],
    ([colors, sizes, merchants]) => {
        if (filters.value.color && !colors.includes(filters.value.color)) {
            filters.value.color = '';
        }

        if (filters.value.size && !sizes.includes(filters.value.size)) {
            filters.value.size = '';
        }

        if (filters.value.merchant && !merchants.includes(filters.value.merchant)) {
            filters.value.merchant = '';
        }
    },
    { immediate: true }
);

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

    if (filters.value.color) {
        items = items.filter((product) => matchesOption(product.color_options, filters.value.color));
    }

    if (filters.value.size) {
        items = items.filter((product) => matchesOption(product.size_options, filters.value.size));
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
            items.sort((a, b) => {
                const featuredDiff = Number(b.is_featured) - Number(a.is_featured);

                if (featuredDiff !== 0) {
                    return featuredDiff;
                }

                return Number(b.is_admin_owned) - Number(a.is_admin_owned);
            });
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
    filters.value = {
        ...defaultFilters(),
        category: '',
    };
}

function syncRouteFilters() {
    const category = route.query.category;
    const routeSearch = route.query.search;

    filters.value.category = typeof category === 'string' ? category : '';
    search.value = typeof routeSearch === 'string' ? routeSearch : '';
}

function uniqueOptions(values) {
    return [...new Set(values.filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
}

function matchesOption(options, selected) {
    if (!selected) {
        return true;
    }

    return Array.isArray(options) && options.includes(selected);
}
</script>
