<template>
    <SectionProductsTable v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionProductsTable from '../sections/ProductsTable.vue';

defineProps({
    products: {
        type: Object,
        required: true,
    },
});
</script>
