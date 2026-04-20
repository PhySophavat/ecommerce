<template>
    <SectionProductHeader v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionProductHeader from '../sections/ProductHeader.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
});
</script>
