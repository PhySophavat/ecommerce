<template>
    <form class="admin-card rounded-2xl px-3 py-3 text-sm" @submit.prevent="$emit('submit')">
        <div class="flex flex-col gap-2 border-b border-[#e2e7f6] pb-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-bold text-base text-slate-950">{{ formTitle }}</h3>
            </div>
            <button
                type="button"
                class="rounded px-3 py-1 text-xs font-semibold text-[#2563eb] border border-[#dbeafe] bg-[#f0f6ff] hover:bg-[#e0eaff]"
                @click="$emit('reset')"
            >
                Reset
            </button>
        </div>

        <div class="mt-3 space-y-3">
            <BasicInformationSection :dashboard="dashboard" :errors="errors" :form="form" :reset-token="resetToken" />
            <PricingSection :errors="errors" :form="form" />
            <InventorySection :errors="errors" :form="form" />
            <ImagesSection :errors="errors" :form="form" :reset-token="resetToken" />
        </div>

        <div class="mt-3 flex flex-row-reverse gap-2 border-t border-[#e2e7f6] pt-3">
            <button
                type="submit"
                class="rounded px-4 py-2 text-xs font-semibold text-white bg-[#2563eb] hover:bg-[#174ea6] disabled:opacity-60"
                :disabled="isSaving"
            >
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { computed } from 'vue';

import BasicInformationSection from './BasicInformationSection.vue';
import ImagesSection from './ImagesSection.vue';
import InventorySection from './InventorySection.vue';
import PricingSection from './PricingSection.vue';
import StatusSection from './StatusSection.vue';
import VariantsSection from './VariantsSection.vue';

const props = defineProps({
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
    mode: {
        type: String,
        default: 'create',
    },
    productName: {
        type: String,
        default: '',
    },
    resetToken: {
        type: Number,
        required: true,
    },
});

defineEmits(['reset', 'submit']);

const formKicker = computed(() => (props.mode === 'edit' ? 'Product editor' : 'New product'));
const formTitle = computed(() => (props.mode === 'edit' ? 'Edit product' : 'Create product'));
const submitLabel = computed(() => {
    if (props.isSaving) {
        return props.mode === 'edit' ? 'Saving changes...' : 'Saving product...';
    }

    return props.mode === 'edit' ? 'Save changes' : 'Save product';
});

function selectedStatusLabel() {
    const status = (props.dashboard.form?.statuses ?? []).find((item) => item.value === props.form.status);

    return status?.label ?? 'Status';
}
</script>
