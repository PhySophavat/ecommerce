<template>
    <div class="bg-[#F8FAFC] py-5 sm:py-6">
        <Slides
            v-if="highlightSlides.length"
            :slides="highlightSlides"
            :current-slide="currentSlide"
            @update:current-slide="currentSlide = $event"
        />

        <div class="mx-auto mt-5 w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
           

           
            <section class="mt-10">
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#A25F88]">Featured products</p>
                        <h2 class="mt-2 text-[2rem] font-bold tracking-[-0.04em] text-[#111827] sm:text-[2.35rem]">Customer favorites right now</h2>
                    </div>
                    <Button to="/shop" variant="secondary" size="sm">Shop all</Button>
                </div>

                <ProductGrid :products="filteredFeatured" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
            </section>

          
            <OurPartnersSection />

            <section class="mt-10">
                <div class="mb-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#A25F88]">New arrivals</p>
                        <h2 class="mt-2 text-[2rem] font-bold tracking-[-0.04em] text-[#111827] sm:text-[2.35rem]">Fresh picks from active merchants</h2>
                </div>

                <ProductGrid :products="store.newArrivals" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
            </section>

            <section class="mt-10 grid gap-4 md:grid-cols-3">
                <article v-for="item in whyChooseUs" :key="item.title" class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_10px_24px_rgba(17,24,39,0.04)]">
                    <div class="flex h-11 w-11 items-center justify-center rounded-[16px] bg-[#FCF7FA] text-[#A25F88]">
                        <span class="text-lg font-bold">{{ item.icon }}</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold tracking-[-0.02em] text-[#111827]">{{ item.title }}</h3>
                    <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ item.text }}</p>
                </article>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Button from '../components/Button.vue';
import OurPartnersSection from '../components/OurPartnersSection.vue';
import ProductGrid from '../components/ProductGrid.vue';
import SearchBar from '../components/SearchBar.vue';
import Slides from '../components/slides/Slides.vue';
import { RouterLink } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const searchTerm = ref('');
const currentSlide = ref(0);
const selectedCategoryId = ref(null);
let slideTimer = null;

onMounted(async () => {
    await store.initialize();
    startSlideRotation();
});

onBeforeUnmount(() => {
    if (slideTimer) {
        window.clearInterval(slideTimer);
    }
});

const highlightSlides = computed(() => store.slides.filter((slide) => Boolean(slide.image_url)));

const filteredFeatured = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    const list = store.featuredProducts.length ? store.featuredProducts : store.products.slice(0, 8);

    if (!term) {
        return list;
    }

    return list.filter((product) =>
        [product.name, product.category, product.merchant_name].some((value) => String(value || '').toLowerCase().includes(term))
    );
});

const categoryShowcase = computed(() => store.categories.map((category) => {
    const matchingProducts = store.products.filter((product) => product.category_slug === category.slug);
    const matchingSlide = store.slides.find((slide) => slide.category_slug === category.slug && slide.image_url);
    const featuredProduct = matchingProducts.find((product) => product.is_featured && product.image_url) ?? matchingProducts.find((product) => product.image_url) ?? matchingProducts[0] ?? null;

    return {
        ...category,
        image_url: matchingSlide?.image_url ?? featuredProduct?.image_url ?? null,
        promo_text: featuredProduct?.tagline || category.description || 'Curated picks for your storefront.',
    };
}));

const activeCategory = computed(() => {
    if (!categoryShowcase.value.length) {
        return null;
    }

    return categoryShowcase.value.find((category) => category.id === selectedCategoryId.value) ?? categoryShowcase.value[0];
});

watch(highlightSlides, () => {
    if (currentSlide.value >= highlightSlides.value.length) {
        currentSlide.value = 0;
    }

    startSlideRotation();
});

watch(categoryShowcase, (categories) => {
    if (!categories.length) {
        selectedCategoryId.value = null;
        return;
    }

    if (!categories.some((category) => category.id === selectedCategoryId.value)) {
        selectedCategoryId.value = categories[0].id;
    }
}, { immediate: true });

const whyChooseUs = [
    { icon: 'OK', title: 'Trusted merchants', text: 'Every product on the storefront comes from managed merchants and a cleaner admin workflow.' },
    { icon: 'UI', title: 'Friendly shopping', text: 'The interface is designed to feel soft, simple, and easy for customers on every screen size.' },
    { icon: 'GO', title: 'Smooth checkout', text: 'Cart, checkout, and merchant payment flow are connected so buying and payout tracking feel immediate.' },
];

function startSlideRotation() {
    if (slideTimer) {
        window.clearInterval(slideTimer);
    }

    if (highlightSlides.value.length < 2) {
        currentSlide.value = 0;
        return;
    }

    slideTimer = window.setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % highlightSlides.value.length;
    }, 3000);
}
</script>
