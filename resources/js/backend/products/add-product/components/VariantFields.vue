<template>
    <section class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6B7280]">Variants</p>
                <h3 class="mt-2 text-xl font-semibold text-[#111827]">Category options</h3>
                <p class="mt-2 text-sm text-[#6B7280]">Choose the variant details that match the selected category.</p>
            </div>
            <span
                v-if="selectedCategoryName"
                class="inline-flex rounded-full bg-[#EEF2F7] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-[#6B7280]"
            >
                {{ selectedCategoryName }}
            </span>
        </div>

        <div v-if="!selectedCategorySlug" class="mt-5 rounded-[22px] border border-dashed border-[#D7DEE7] bg-[#F8FAFC] px-5 py-5 text-sm text-[#6B7280]">
            Select a category first to show the matching variant dropdowns.
        </div>

        <div v-else-if="!fields.length" class="mt-5 rounded-[22px] border border-dashed border-[#D7DEE7] bg-[#F8FAFC] px-5 py-5 text-sm text-[#6B7280]">
            No variant options are configured for this category yet.
        </div>

        <div v-else class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <FormSelect
                v-for="field in fields"
                :key="field.key"
                :id="`variant-${field.key}`"
                :error="fieldError(field.key)"
                :label="field.label"
                :model-value="form.variant_values[field.key]"
                :options="field.options"
                :placeholder="field.placeholder"
                required
                @update:modelValue="updateField(field.key, $event)"
            />
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

import { categoryFieldsForSlug } from '../categoryConfig.js';
import FormSelect from './FormSelect.vue';

const props = defineProps({
    errors: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    selectedCategoryName: {
        type: String,
        default: '',
    },
    selectedCategorySlug: {
        type: String,
        default: '',
    },
});

const fields = computed(() => categoryFieldsForSlug(props.selectedCategorySlug));

function updateField(key, value) {
    props.form.variant_values = {
        ...props.form.variant_values,
        [key]: value,
    };
}

function fieldError(key) {
    return props.errors[`variant_values.${key}`]?.[0] ?? '';
}
</script>
