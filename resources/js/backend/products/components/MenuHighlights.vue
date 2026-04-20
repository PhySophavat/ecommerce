<template>
    <SectionMenuHighlights v-bind="$props" v-bind="$attrs" />
</template>

<script setup>
defineOptions({
    inheritAttrs: false,
});

import SectionMenuHighlights from '../sections/MenuHighlights.vue';

defineProps({
    highlights: {
        type: Array,
        required: true,
    },
});
</script>
