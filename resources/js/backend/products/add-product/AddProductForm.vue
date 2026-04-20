<template>
    <form class="admin-card rounded-[28px] px-5 py-5 sm:px-6 sm:py-6" @submit.prevent="$emit('submit')">
        <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">New product</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950 sm:text-3xl">Create product</h3>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <span class="chatgpt-pill rounded-full border border-[#d7defe] bg-[#eef3ff] px-3 py-1 text-xs uppercase text-[#3457ff]">
                    {{ selectedStatusLabel() }}
                </span>
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('reset')"
                >
                    Reset form
                </button>
            </div>
        </div>

        <div class="mt-6 space-y-6">
            <BasicInformationSection
                :dashboard="dashboard"
                :editor-actions="editorActions"
                :errors="errors"
                :form="form"
                :reset-token="resetToken"
            />

            <section class="grid gap-6 lg:grid-cols-2">
                <PricingSection :errors="errors" :form="form" />
                <InventorySection :errors="errors" :form="form" />
            </section>

            <ImagesSection :errors="errors" :form="form" :reset-token="resetToken" />
            <VariantsSection :dashboard="dashboard" :errors="errors" :form="form" />
            <StatusSection :dashboard="dashboard" :errors="errors" :form="form" />
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-slate-200/80 pt-5 sm:flex-row sm:justify-end">
            <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('reset')"
                >
                    Clear
                </button>
                <button
                    type="submit"
                    class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.28)] transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="isSaving"
                >
                    {{ isSaving ? 'Saving product...' : 'Save product' }}
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
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
    resetToken: {
        type: Number,
        required: true,
    },
});

defineEmits(['reset', 'submit']);

function selectedStatusLabel() {
    const status = (props.dashboard.form?.statuses ?? []).find((item) => item.value === props.form.status);

    return status?.label ?? 'Status';
}
</script>
