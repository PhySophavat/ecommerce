<template>
    <SectionSummaryCardsGrid v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionSummaryCardsGrid from '../sections/SummaryCardsGrid.vue';

defineProps({
    cards: {
        type: Array,
        required: true,
    },
});
</script>
