<template>
    <div class="bg-[#F8FAFC] py-5 sm:py-6">
        <Slides
            v-if="highlightSlides.length"
            :slides="highlightSlides"
            :current-slide="currentSlide"
            @update:current-slide="currentSlide = $event"
        />

        <div class="mx-auto mt-5 w-full max-w-[1280px] px-4 sm:px-6 lg:px-8">
            <section class="grid gap-5 lg:grid-cols-[minmax(0,1.18fr)_minmax(320px,0.82fr)] lg:items-stretch">
                <div class="overflow-hidden rounded-[32px] border border-[#E5E7EB] bg-white px-6 py-7 shadow-[0_16px_40px_rgba(17,24,39,0.05)] sm:px-8 sm:py-8 lg:px-9 lg:py-9">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#A25F88]">{{ store.meta?.eyebrow || 'Clean modern storefront' }}</p>
                    <h1 class="mt-4 max-w-2xl text-[2.4rem] font-bold leading-[1.02] tracking-[-0.05em] text-[#111827] sm:text-[3.2rem] lg:text-[4.1rem]">
                        Friendly shopping with trusted merchant products.
                    </h1>
                    <p class="mt-4 max-w-2xl text-[15px] leading-7 text-[#6B7280] sm:text-base">
                        Discover curated products, compare favorite finds, and checkout in a clean customer-first experience built for modern commerce.
                    </p>

                    <div class="mt-6 max-w-[620px]">
                        <SearchBar v-model="searchTerm" placeholder="Search products, brands, and merchants" />
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Button to="/shop" size="lg">Start shopping</Button>
                        <Button to="/wishlist" variant="secondary" size="lg">View wishlist</Button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[32px] border border-[#E5E7EB] bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.22),_transparent_34%),linear-gradient(155deg,#B6789A_0%,#A25F88_46%,#7B3F63_100%)] p-6 text-white shadow-[0_18px_40px_rgba(162,95,136,0.18)] sm:p-7">
                    <div class="flex h-full flex-col justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">Today&apos;s feature</p>
                            <h2 class="mt-3 max-w-md text-3xl font-semibold leading-tight tracking-[-0.04em] text-white">
                                Soft, polished storefront cards with merchant-ready products.
                            </h2>
                            <p class="mt-3 max-w-md text-sm leading-7 text-white/72">
                                Curated browsing, trusted merchants, and a calmer storefront rhythm designed to feel premium without excess.
                            </p>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[24px] border border-white/15 bg-white/14 p-5 backdrop-blur">
                                <div class="text-3xl font-semibold">{{ store.categories.length }}</div>
                                <div class="mt-1 text-sm text-white/72">Live categories</div>
                            </div>
                            <div class="rounded-[24px] border border-white/15 bg-white/14 p-5 backdrop-blur">
                                <div class="text-3xl font-semibold">{{ store.products.length }}</div>
                                <div class="mt-1 text-sm text-white/72">Storefront products</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

           
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

            <section class="mt-10 rounded-[32px] border border-[#E5E7EB] bg-[linear-gradient(145deg,#FCF7FA,#FFFFFF)] px-6 py-7 shadow-[0_16px_36px_rgba(17,24,39,0.04)] sm:px-8">
                <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#A25F88]">Promotion banner</p>
                        <h2 class="mt-2 text-[1.85rem] font-bold tracking-[-0.04em] text-[#111827] sm:text-[2.15rem]">Save on curated storefront drops this week.</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-[#6B7280]">
                            Explore new arrivals, merchant-approved products, and featured deals in a cleaner shopping experience designed for customer confidence.
                        </p>
                    </div>
                    <Button to="/shop" variant="primary" size="lg">Explore promotions</Button>
                </div>
            </section>

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
