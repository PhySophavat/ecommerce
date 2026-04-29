<template>
    <div class="min-h-screen w-full bg-[linear-gradient(180deg,#f8fafc_0%,#eef4ff_100%)]">
        <main class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <section class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <button
                    type="button"
                    class="flex items-center gap-3 text-left text-[1.85rem] font-semibold uppercase tracking-[0.2em] text-slate-950"
                    @click="selectCategory('all')"
                >
                    <img
                        :src="logoUrl"
                        alt="Store logo"
                        class="h-12 w-12 rounded-2xl bg-white p-1 shadow-sm"
                    >
                    <span>{{ logoLabel }}</span>
                </button>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="item in navigationCategories"
                        :key="`category-${item.slug}`"
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition"
                        :class="categoryButtonClass(item.slug)"
                        @click="selectCategory(item.slug)"
                    >
                        {{ item.name }}
                    </button>
                </div>
            </section>

            <section v-if="activeSlide" class="space-y-5">
                <div class="relative overflow-hidden rounded-[36px] border border-slate-200/80 bg-white shadow-[0_28px_70px_rgba(15,23,42,0.12)]">
                    <img
                        v-if="activeSlide.image_url"
                        :src="activeSlide.image_url"
                        :alt="slideAlt(activeSlide)"
                        class="h-[260px] w-full object-cover sm:h-[420px] lg:h-[620px]"
                    >
                    <div
                        v-else
                        class="flex h-[260px] items-center justify-center bg-[linear-gradient(135deg,#f8fafc,#e2e8f0)] text-sm font-medium text-slate-400 sm:h-[420px] lg:h-[620px]"
                    >
                        No slide image
                    </div>

                    <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,42,0.02),rgba(15,23,42,0.16))]"></div>

                    <button
                        type="button"
                        class="absolute left-4 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/70 bg-white/85 text-slate-900 shadow-[0_12px_28px_rgba(15,23,42,0.12)] transition hover:scale-[1.03] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="filteredSlides.length <= 1"
                        @click="previousSlide"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m15 6-6 6 6 6"></path>
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="absolute right-4 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-white/70 bg-white/85 text-slate-900 shadow-[0_12px_28px_rgba(15,23,42,0.12)] transition hover:scale-[1.03] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="filteredSlides.length <= 1"
                        @click="nextSlide"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="m9 6 6 6-6 6"></path>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-center gap-3">
                    <button
                        v-for="(slide, index) in filteredSlides"
                        :key="`dot-${slide.id}`"
                        type="button"
                        class="h-3 rounded-full transition"
                        :class="index === activeSlideIndex ? 'w-10 bg-[#3457ff]' : 'w-3 bg-slate-300'"
                        @click="activeSlideIndex = index"
                    ></button>
                </div>
            </section>

            <section
                v-else
                class="rounded-[32px] border border-dashed border-slate-300 bg-white px-8 py-20 text-center text-slate-500"
            >
                <h2 class="font-display text-4xl text-slate-950">No slide images available.</h2>
                <p class="mt-4 text-lg">
                    Upload an active slide image in the admin slider manager to show it here.
                </p>
                <a
                    :href="adminSlidersLink"
                    class="mt-8 inline-flex rounded-2xl bg-slate-950 px-6 py-4 text-sm font-semibold uppercase tracking-[0.08em] text-white"
                >
                    Open slide manager
                </a>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const storefront = ref(initialStorefront());
const activeCategorySlug = ref('all');
const activeSlideIndex = ref(0);

let autoplayTimer = null;
const logoUrl = '/logo.jpg';

const categories = computed(() => storefront.value.categories ?? []);
const slides = computed(() => (
    (storefront.value.slides ?? []).filter((slide) => Boolean(slide.image_url))
));
const navigationCategories = computed(() => {
    const availableCategories = new Set(
        slides.value
            .map((slide) => slide.category_slug)
            .filter(Boolean),
    );

    return [
        { slug: 'all', name: 'All' },
        ...categories.value.filter((category) => availableCategories.has(category.slug)),
    ];
});
const logoLabel = computed(() => {
    const brand = String(storefront.value.meta?.brand ?? 'Northstar');

    return brand.replace(/\s+Goods$/i, '').toUpperCase();
});
const adminSlidersLink = computed(() => storefront.value.links?.admin_sliders ?? storefront.value.links?.admin_products ?? '/admin/sliders');
const filteredSlides = computed(() => slides.value.filter((slide) => (
    activeCategorySlug.value === 'all' || slide.category_slug === activeCategorySlug.value
)));
const activeSlide = computed(() => filteredSlides.value[activeSlideIndex.value] ?? null);

watch(navigationCategories, (items) => {
    if (!items.some((item) => item.slug === activeCategorySlug.value)) {
        activeCategorySlug.value = 'all';
    }
}, { immediate: true });

watch(filteredSlides, (nextSlides) => {
    if (activeSlideIndex.value >= nextSlides.length) {
        activeSlideIndex.value = 0;
    }

    restartAutoplay();
}, { immediate: true });

onMounted(async () => {
    await loadStorefront();
    restartAutoplay();
});

onBeforeUnmount(() => {
    stopAutoplay();
});

async function loadStorefront() {
    try {
        const response = await window.axios.get('/api/frontend/home');
        storefront.value = response.data;
    } catch {
        // Keep the page shell available even if the API request fails.
    }
}

function selectCategory(slug) {
    activeCategorySlug.value = slug;
    activeSlideIndex.value = 0;
}

function categoryButtonClass(slug) {
    return activeCategorySlug.value === slug
        ? 'border-[#3457ff] bg-[#eef3ff] text-[#3457ff]'
        : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:text-slate-950';
}

function slideAlt(slide) {
    return slide.title || slide.category || 'Storefront slide image';
}

function nextSlide() {
    if (filteredSlides.value.length <= 1) {
        return;
    }

    activeSlideIndex.value = (activeSlideIndex.value + 1) % filteredSlides.value.length;
}

function previousSlide() {
    if (filteredSlides.value.length <= 1) {
        return;
    }

    activeSlideIndex.value = (activeSlideIndex.value - 1 + filteredSlides.value.length) % filteredSlides.value.length;
}

function restartAutoplay() {
    stopAutoplay();

    if (filteredSlides.value.length <= 1) {
        return;
    }

    autoplayTimer = window.setInterval(() => {
        nextSlide();
    }, 6000);
}

function stopAutoplay() {
    if (autoplayTimer) {
        window.clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
}

function initialStorefront() {
    return {
        meta: {
            brand: 'Northstar Goods',
        },
        links: {
            frontend: '/frontend',
            admin_sliders: '/admin/sliders',
            admin_products: '/admin/sliders',
        },
        categories: [],
        slides: [],
    };
}
</script>
