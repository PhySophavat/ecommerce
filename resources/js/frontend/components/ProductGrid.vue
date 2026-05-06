<template>
    <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
            :wishlist="wishlistIds.includes(product.id)"
            @add-to-cart="$emit('add-to-cart', $event)"
            @toggle-wishlist="$emit('toggle-wishlist', $event)"
        />
    </div>
</template>

<script setup>
import ProductCard from './ProductCard.vue';

defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    wishlistIds: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['add-to-cart', 'toggle-wishlist']);
</script>
