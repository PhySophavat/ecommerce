<template>
    <FeatureAddProductForm
        :dashboard="dashboard"
        :editor-actions="editorActions"
        :errors="errors"
        :form="form"
        :is-saving="isSaving"
        :reset-token="resetToken"
        @reset="$emit('reset')"
        @submit="$emit('submit')"
    />
</template>

<script setup>
import FeatureAddProductForm from '../add-product/AddProductForm.vue';

defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    editorActions: {
        type: Array,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    isSaving: {
        type: Boolean,
        required: true,
    },
    resetToken: {
        type: Number,
        required: true,
    },
});

defineEmits(['reset', 'submit']);
</script>
