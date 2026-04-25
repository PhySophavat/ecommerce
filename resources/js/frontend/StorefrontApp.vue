<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100">
        <!-- Header with Category Navigation -->
        <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <!-- Brand Logo -->
                    <a href="/frontend" class="flex items-center gap-2">
                        <span class="text-2xl font-bold text-slate-900">{{ meta?.brand || 'Store' }}</span>
                    </a>

                    <!-- Category Navigation -->
                    <nav class="hidden items-center gap-1 md:flex">
                        <a
                            v-for="category in categories"
                            :key="category.id"
                            :href="`/frontend?category=${category.slug}`"
                            class="rounded-full px-4 py-2 text-sm font-medium transition"
                            :class="activeCategory === category.slug 
                                ? 'bg-slate-900 text-white' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        >
                            {{ category.name }}
                        </a>
                    </nav>

                    <!-- Mobile Menu Toggle -->
                    <button
                        type="button"
                        class="rounded-full p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Category Menu -->
                <div v-if="isMobileMenuOpen" class="mt-4 flex flex-wrap gap-2 pb-2 md:hidden">
                    <a
                        v-for="category in categories"
                        :key="category.id"
                        :href="`/frontend?category=${category.slug}`"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="activeCategory === category.slug 
                            ? 'bg-slate-900 text-white' 
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    >
                        {{ category.name }}
                    </a>
                </div>
            </div>
        </header>

        <!-- Hero Section with Slides -->
        <section v-if="slides.length > 0" class="relative overflow-hidden bg-slate-900">
            <div class="relative h-[60vh] min-h-[400px] max-w-7xl mx-auto">
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    class="absolute inset-0 transition-opacity duration-700"
                    :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
                >
                    <!-- Slide Image -->
                    <div
                        v-if="slide.image_url"
                        class="absolute inset-0 bg-cover bg-center"
                        :style="{ backgroundImage: `url(${slide.image_url})` }"
                    >
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 to-slate-900/20"></div>
                    </div>
                    <div v-else class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"></div>

                    <!-- Slide Content -->
                    <div class="relative flex h-full items-center px-4 sm:px-6 lg:px-8">
                        <div class="max-w-2xl">
                            <p v-if="slide.eyebrow" class="mb-3 text-sm font-medium uppercase tracking-wider text-orange-400">
                                {{ slide.eyebrow }}
                            </p>
                            <h1 class="mb-4 text-4xl font-bold text-white sm:text-5xl lg:text-6xl">
                                {{ slide.title }}
                            </h1>
                            <p v-if="slide.description" class="mb-6 text-lg text-slate-200">
                                {{ slide.description }}
                            </p>
                            <a
                                v-if="slide.button_text"
                                :href="slide.button_url"
                                class="inline-block rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-600 hover:-translate-y-0.5"
                            >
                                {{ slide.button_text }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Slide Navigation -->
                <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                    <button
                        v-for="(slide, index) in slides"
                        :key="`dot-${slide.id}`"
                        type="button"
                        class="h-2 w-2 rounded-full transition"
                        :class="index === currentSlide ? 'bg-white' : 'bg-white/40 hover:bg-white/60'"
                        @click="currentSlide = index"
                    />
                </div>
            </div>
        </section>

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
        }, 5000);
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
