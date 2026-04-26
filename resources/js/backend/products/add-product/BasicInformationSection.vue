<template>
    <section class="admin-form-section rounded-[24px] p-5 sm:p-6">
        <div>
            <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Basic information</p>
            <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Product details</h4>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-2">
            <label class="block lg:col-span-2">
                <span class="chatgpt-label text-sm">Product Name</span>
                <input
                    id="product-name"
                    v-model="form.name"
                    type="text"
                    placeholder="Premium Cotton Shirt"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('name') ? 'border-rose-300' : 'border-slate-200'"
                />
                <p v-if="fieldError('name')" class="mt-2 text-sm text-rose-600">{{ fieldError('name') }}</p>
            </label>

            <label class="block">
                <span class="chatgpt-label text-sm">Category</span>
                <select
                    v-model="form.category_id"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('category_id') ? 'border-rose-300' : 'border-slate-200'"
                >
                    <option value="">Select category</option>
                    <option v-for="category in dashboard.form.categories" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>
                <p v-if="fieldError('category_id')" class="mt-2 text-sm text-rose-600">{{ fieldError('category_id') }}</p>
            </label>

            <label class="block">
                <span class="chatgpt-label text-sm">Type</span>
                <select
                    v-model="form.type"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('type') ? 'border-rose-300' : 'border-slate-200'"
                >
                    <option v-for="type in dashboard.form.types" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <p v-if="fieldError('type')" class="mt-2 text-sm text-rose-600">{{ fieldError('type') }}</p>
            </label>

            <div class="lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="chatgpt-label text-sm">Description</span>
                    <span class="text-xs text-slate-400">{{ stripHtml(form.description).trim().length }} chars</span>
                </div>
                <div class="mt-3 overflow-hidden rounded-[24px] border bg-white" :class="fieldError('description') ? 'border-rose-300' : 'border-slate-200'">
                    <div class="flex flex-wrap gap-2 border-b border-slate-200/80 px-4 py-3">
                        <button
                            v-for="action in editorActions"
                            :key="action.command"
                            type="button"
                            class="chatgpt-pill rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs uppercase text-slate-500 transition hover:border-[#3457ff] hover:text-[#3457ff]"
                            @click="applyDescriptionFormat(action.command, action.value)"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                    <div ref="descriptionEditor" contenteditable="true" class="min-h-[150px] px-4 py-4 text-sm leading-7 text-slate-700 outline-none" @input="handleDescriptionInput"></div>
                </div>
                <p v-if="fieldError('description')" class="mt-2 text-sm text-rose-600">{{ fieldError('description') }}</p>
            </div>
        </div>
    </section>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

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
    resetToken: {
        type: Number,
        required: true,
    },
});

const descriptionEditor = ref(null);

onMounted(() => {
    syncDescriptionEditor();
});

watch(
    () => props.resetToken,
    async () => {
        await nextTick();
        syncDescriptionEditor();
        document.getElementById('product-name')?.focus();
    },
);

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}

function syncDescriptionEditor() {
    if (!descriptionEditor.value) {
        return;
    }

    descriptionEditor.value.innerHTML = props.form.description || '';
}

function handleDescriptionInput() {
    const editor = descriptionEditor.value;

    if (!editor) {
        return;
    }

    const text = editor.textContent?.trim() ?? '';
    props.form.description = text ? editor.innerHTML : '';
}

function applyDescriptionFormat(command, value = null) {
    descriptionEditor.value?.focus();
    document.execCommand(command, false, value);
    handleDescriptionInput();
}

function stripHtml(value) {
    return String(value ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ');
}
</script>
