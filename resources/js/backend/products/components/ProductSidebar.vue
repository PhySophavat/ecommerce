<template>
    <SectionProductSidebar v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionProductSidebar from '../sections/ProductSidebar.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    isMenuOpen: {
        type: Function,
        required: true,
    },
});
</script>
