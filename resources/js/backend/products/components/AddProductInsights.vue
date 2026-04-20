<template>
    <SectionAddProductInsights v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionAddProductInsights from '../sections/AddProductInsights.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
});
</script>
