<template>
    <div class="py-8">
        <Slides
            v-if="store.slides.length"
            :slides="store.slides"
            :current-slide="currentSlide"
            @update:current-slide="currentSlide = $event"
        />

        <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="overflow-hidden rounded-[36px] border border-[#D8E7F4] bg-white p-8 shadow-[0_24px_60px_rgba(20,149,232,0.08)] sm:p-10">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#1495E8]">{{ store.meta?.eyebrow || 'Clean modern storefront' }}</p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-black tracking-[-0.06em] text-[#111827] sm:text-6xl">
                        Friendly shopping with trusted merchant products.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-8 text-[#6B7280]">
                        Discover curated products, compare favorite finds, and checkout in a clean customer-first experience built for modern commerce.
                    </p>

                    <div class="mt-8 max-w-xl">
                        <SearchBar v-model="searchTerm" placeholder="Search products, brands, and merchants" />
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Button to="/shop" size="lg">Start shopping</Button>
                        <Button to="/wishlist" variant="secondary" size="lg">View wishlist</Button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[36px] border border-[#D8E7F4] bg-[linear-gradient(145deg,#1495E8,#0F172A)] p-8 text-white shadow-[0_24px_60px_rgba(15,23,42,0.14)]">
                    <div class="flex h-full flex-col justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">Today&apos;s feature</p>
                            <h2 class="mt-3 text-3xl font-black tracking-[-0.05em]">Soft, polished storefront cards with merchant-ready products.</h2>
                        </div>
                        <div class="mt-10 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[28px] bg-white/10 p-5 backdrop-blur">
                                <div class="text-3xl font-black">{{ store.categories.length }}</div>
                                <div class="mt-2 text-sm text-white/70">Live categories</div>
                            </div>
                            <div class="rounded-[28px] bg-white/10 p-5 backdrop-blur">
                                <div class="text-3xl font-black">{{ store.products.length }}</div>
                                <div class="mt-2 text-sm text-white/70">Storefront products</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-14">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Browse by category</p>
                        <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Shop the catalog your way</h2>
                    </div>
                    <Button to="/shop" variant="secondary">View all</Button>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                    <CategoryCard v-for="category in store.categories.slice(0, 4)" :key="category.id" :category="category" />
                </div>
            </section>

            <section class="mt-14">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Featured products</p>
                        <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Customer favorites right now</h2>
                    </div>
                    <Button to="/shop" variant="secondary">Shop all</Button>
                </div>

                <ProductGrid :products="filteredFeatured" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
            </section>

            <section class="mt-14 rounded-[36px] border border-[#D8E7F4] bg-[linear-gradient(145deg,#0F172A,#1495E8)] px-8 py-10 text-white shadow-[0_28px_70px_rgba(15,23,42,0.14)]">
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#BFDBFE]">Promotion banner</p>
                        <h2 class="mt-2 text-3xl font-black tracking-[-0.05em]">Save on curated storefront drops this week.</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                            Explore new arrivals, merchant-approved products, and featured deals in a cleaner shopping experience designed for customer confidence.
                        </p>
                    </div>
                    <Button to="/shop" variant="primary" size="lg">Explore promotions</Button>
                </div>
            </section>

            <section class="mt-14">
                <div class="mb-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">New arrivals</p>
                    <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Fresh picks from active merchants</h2>
                </div>

                <ProductGrid :products="store.newArrivals" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
            </section>

            <section class="mt-14 grid gap-5 md:grid-cols-3">
                <article v-for="item in whyChooseUs" :key="item.title" class="rounded-[30px] border border-[#D8E7F4] bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F3F9FD] text-[#1495E8]">
                        <span class="text-lg font-black">{{ item.icon }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-black tracking-[-0.03em] text-[#111827]">{{ item.title }}</h3>
                    <p class="mt-3 text-sm leading-7 text-[#6B7280]">{{ item.text }}</p>
                </article>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '../components/Button.vue';
import CategoryCard from '../components/CategoryCard.vue';
import ProductGrid from '../components/ProductGrid.vue';
import SearchBar from '../components/SearchBar.vue';
import Slides from '../components/slides/Slides.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const searchTerm = ref('');
const currentSlide = ref(0);
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

const whyChooseUs = [
    { icon: 'OK', title: 'Trusted merchants', text: 'Every product on the storefront comes from managed merchants and a cleaner admin workflow.' },
    { icon: 'UI', title: 'Friendly shopping', text: 'The interface is designed to feel soft, simple, and easy for customers on every screen size.' },
    { icon: 'GO', title: 'Smooth checkout', text: 'Cart, checkout, and merchant payment flow are connected so buying and payout tracking feel immediate.' },
];

function startSlideRotation() {
    if (slideTimer) {
        window.clearInterval(slideTimer);
    }

    if (store.slides.length < 2) {
        return;
    }

    slideTimer = window.setInterval(() => {
        currentSlide.value = (currentSlide.value + 1) % store.slides.length;
    }, 4000);
}
</script>
