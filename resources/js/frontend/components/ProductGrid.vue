<template>
    <div :class="gridClass">
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
import { computed } from 'vue';
import ProductCard from './ProductCard.vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    wishlistIds: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Number,
        default: 4,
    },
});

defineEmits(['add-to-cart', 'toggle-wishlist']);

const gridClass = computed(() => (
    props.columns === 3
        ? 'grid gap-6 sm:grid-cols-2 xl:grid-cols-3'
        : 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
));
</script>
