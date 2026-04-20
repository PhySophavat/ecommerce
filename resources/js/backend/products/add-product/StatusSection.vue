<template>
    <section class="rounded-[24px] border border-slate-200/80 bg-white p-5 sm:p-6">
        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Status</p>
        <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Visibility</h4>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <label
                v-for="status in dashboard.form.statuses"
                :key="status.value"
                class="flex cursor-pointer items-center gap-3 rounded-[20px] border bg-slate-50 px-4 py-4 transition"
                :class="form.status === status.value ? 'border-[#3457ff] shadow-[0_12px_24px_rgba(52,87,255,0.08)]' : 'border-slate-200'"
            >
                <input v-model="form.status" type="radio" :value="status.value" class="h-4 w-4 border-slate-300 text-[#3457ff]" />
                <div>
                    <p class="chatgpt-label text-sm text-slate-900">{{ status.label }}</p>
                </div>
            </label>
        </div>
        <p v-if="fieldError('status')" class="mt-3 text-sm text-rose-600">{{ fieldError('status') }}</p>
    </section>
</template>

<script setup>
const props = defineProps({
    dashboard: {
        type: Object,
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
});

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}
</script>
