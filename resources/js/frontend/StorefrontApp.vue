<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100">
        <!-- Header Component -->
        <Header
            :meta="meta"
            :categories="categories"
            :activeCategory="activeCategory"
            :isMobileMenuOpen="isMobileMenuOpen"
            @toggle-mobile-menu="isMobileMenuOpen = !isMobileMenuOpen"
        />

        <!-- Slides Component -->
        <Slides :slides="slides" :currentSlide="currentSlide" @update:currentSlide="currentSlide = $event" />

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Page Meta -->
            <div class="mb-8 text-center">
                <p v-if="meta?.eyebrow" class="mb-2 text-sm font-medium uppercase tracking-wider text-slate-500">
                    {{ meta.eyebrow }}
                </p>
                <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl">
                    {{ meta?.headline || 'Shop the catalog' }}
                </h2>
                <p v-if="meta?.subheadline" class="mt-2 text-slate-600">
                    {{ meta.subheadline }}
                </p>
            </div>

            <!-- Stats -->
            <div v-if="meta?.stats" class="mb-12 flex justify-center gap-8 sm:gap-12">
                <div v-for="stat in meta.stats" :key="stat.label" class="text-center">
                    <div class="text-2xl font-bold text-slate-900">{{ stat.value }}</div>
                    <div class="text-sm text-slate-500">{{ stat.label }}</div>
                </div>
            </div>

            <!-- Products Grid -->
            <section v-if="filteredProducts.length > 0">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-slate-900">
                        {{ activeCategory ? getCategoryName(activeCategory) : 'All Products' }}
                    </h3>
                    <span class="text-sm text-slate-500">{{ filteredProducts.length }} products</span>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <article
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="group rounded-3xl bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                    >
                        <!-- Product Image -->
                        <div class="relative aspect-square overflow-hidden rounded-2xl bg-slate-100">
                            <div class="absolute inset-0 bg-cover bg-center transition group-hover:scale-105" :style="getProductBackground(product)"></div>
                            <div v-if="product.is_featured" class="absolute left-3 top-3 rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold text-white">
                                Featured
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="mt-4">
                            <p class="text-xs text-slate-500">{{ product.category }}</p>
                            <h4 class="mt-1 text-lg font-semibold text-slate-900">{{ product.name }}</h4>
                            <p class="mt-1 text-sm text-slate-600">{{ product.tagline }}</p>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="text-lg font-bold text-slate-900">{{ product.price }}</span>
                                <span v-if="product.compare_at_price" class="text-sm text-slate-500 line-through">
                                    {{ product.compare_at_price }}
                                </span>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <p class="text-slate-500">No products found.</p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-8">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <p class="text-sm text-slate-500">&copy; {{ new Date().getFullYear() }} {{ meta?.brand || 'Store' }}. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import Header from './components/header/Header.vue';
import Slides from './components/slides/Slides.vue';
import { computed, onMounted, ref } from 'vue';

const isLoading = ref(true);
const isMobileMenuOpen = ref(false);
const currentSlide = ref(0);
const activeCategory = ref('');
const meta = ref(null);
const categories = ref([]);
const slides = ref([]);
const products = ref([]);

// Get active category from URL
onMounted(async () => {
    const params = new URLSearchParams(window.location.search);
    activeCategory.value = params.get('category') || '';

    await loadData();
    startSlideRotation();
});

async function loadData() {
    try {
        const response = await window.axios.get('/api/frontend/home');
        const data = response.data;

        meta.value = data.meta;
        categories.value = data.categories || [];
        slides.value = data.slides || [];
        products.value = data.products?.items || [];
    } catch (error) {
        console.error('Failed to load storefront data:', error);
    } finally {
        isLoading.value = false;
    }
}

function startSlideRotation() {
    if (slides.value.length > 1) {
        setInterval(() => {
            currentSlide.value = (currentSlide.value + 1) % slides.value.length;
        }, 2000);
    }
}

const filteredProducts = computed(() => {
    if (!activeCategory.value) {
        return products.value;
    }

    return products.value.filter(
        (p) => p.category_slug === activeCategory.value || p.category?.toLowerCase() === activeCategory.value.toLowerCase()
    );
});

function getCategoryName(slug) {
    const category = categories.value.find((c) => c.slug === slug);
    return category?.name || slug;
}

function getProductBackground(product) {
    // Placeholder - in real app, product would have image_url
    return {
        backgroundImage: 'linear-gradient(135deg, #f1f5f9, #e2e8f0)',
    };
}
</script>
