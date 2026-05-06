<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Wishlist page</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Saved products for later</h1>
            </div>
            <Button to="/shop" variant="secondary">Continue shopping</Button>
        </div>

        <ProductGrid :products="store.wishlistProducts" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />

        <div v-if="!store.wishlistProducts.length" class="rounded-[30px] border border-dashed border-[#D8B4C7] bg-white px-6 py-16 text-center text-[#6B7280]">
            Your wishlist is empty.
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import Button from '../components/Button.vue';
import ProductGrid from '../components/ProductGrid.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();

onMounted(() => {
    store.initialize();
});
</script>
