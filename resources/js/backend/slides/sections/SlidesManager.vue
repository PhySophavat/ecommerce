<template>
    <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px] text-sm">

        <!-- Slides List -->
        <section class="space-y-3">
            <article
                v-for="slide in slides"
                :key="`slide-${slide.id}`"
                class="slide-card rounded-xl px-4 py-3"
            >
                <div class="flex items-center gap-3">
                    <!-- Thumbnail -->
                    <div class="relative h-11 w-18 min-w-[4.5rem] overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        <div
                            v-if="slide.image_url"
                            class="absolute inset-0 bg-cover bg-center"
                            :style="{ backgroundImage: `url(${slide.image_url})` }"
                        ></div>
                        <div v-else class="flex h-full items-center justify-center text-[9px] text-slate-400 font-medium tracking-wide uppercase">
                            No img
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span
                                class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                :class="slide.is_active
                                    ? 'bg-[#f3eaf0] text-[#A25F88]'
                                    : 'bg-slate-100 text-slate-400'"
                            >
                                {{ slide.is_active ? 'Active' : 'Draft' }}
                            </span>
                            <span class="rounded-full border border-slate-200 px-2 py-0.5 text-[10px] text-slate-400">
                                #{{ slide.sort_order }}
                            </span>
                        </div>
                        <div class="truncate font-semibold text-xs text-slate-800">{{ slide.title }}</div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="btn-primary rounded-lg px-3 py-1.5 text-xs font-semibold text-white"
                            @click="$emit('edit-slide', slide)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-500 hover:bg-rose-100 transition-colors"
                            :disabled="deletingSlideId === slide.id"
                            @click="$emit('delete-slide', slide)"
                        >
                            {{ deletingSlideId === slide.id ? '···' : 'Del' }}
                        </button>
                    </div>
                </div>
            </article>

            <!-- Empty state -->
            <section
                v-if="slides.length === 0"
                class="slide-card rounded-xl border border-dashed border-[#d4a8c7] px-6 py-14 text-center text-sm text-slate-400"
            >
                <div class="mb-1 text-2xl">🖼️</div>
                No slides yet.
            </section>
        </section>

        <!-- Form Panel -->
        <form
            id="slides-form"
            class="slide-card rounded-xl px-4 py-4 text-sm self-start"
            @submit.prevent="$emit('submit')"
        >
            <!-- Header -->
            <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-100">
                <h3 class="font-bold text-sm text-slate-900">
                    {{ mode === 'edit' ? 'Edit Slide' : 'New Slide' }}
                </h3>
                <button
                    type="button"
                    class="rounded-lg px-3 py-1 text-xs font-semibold text-[#A25F88] border border-[#A25F88] hover:bg-[#f3eaf0] transition-colors"
                    @click="$emit('reset')"
                >
                    Reset
                </button>
            </div>

            <!-- Fields -->
            <div class="space-y-2.5">
                <input
                    v-model="form.title"
                    type="text"
                    placeholder="Slide title"
                    class="field w-full"
                    :class="fieldError('title') ? 'border-rose-300 focus:border-rose-400' : ''"
                >

                <select
                    v-model="form.category_id"
                    class="field w-full"
                    :class="fieldError('category_id') ? 'border-rose-300 focus:border-rose-400' : ''"
                >
                    <option value="">All categories</option>
                    <option
                        v-for="category in dashboard.form.categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </option>
                </select>

                <div class="flex items-center gap-3">
                    <input
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                        placeholder="Order"
                        class="field w-20"
                        :class="fieldError('sort_order') ? 'border-rose-300' : ''"
                    >
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <div class="relative">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="sr-only peer"
                            >
                            <div class="toggle-track peer-checked:bg-[#A25F88]"></div>
                            <div class="toggle-thumb peer-checked:translate-x-4"></div>
                        </div>
                        <span
                            class="text-xs font-medium"
                            :class="form.is_active ? 'text-[#A25F88]' : 'text-slate-400'"
                        >
                            {{ form.is_active ? 'Active' : 'Draft' }}
                        </span>
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 pt-3 border-t border-slate-100">
                <button
                    type="submit"
                    class="btn-primary w-full rounded-lg py-2 text-xs font-semibold text-white disabled:opacity-50"
                    :disabled="isSaving"
                >
                    {{ submitLabel }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    dashboard: { type: Object, required: true },
    deletingSlideId: { type: [Number, String, null], default: null },
    errors: { type: Object, required: true },
    form: { type: Object, required: true },
    isSaving: { type: Boolean, required: true },
    mode: { type: String, default: 'create' },
    resetToken: { type: Number, required: true },
    slideTitle: { type: String, default: '' },
});

defineEmits(['delete-slide', 'edit-slide', 'reset', 'submit']);

const fileInput = ref(null);

const slides = computed(() => props.dashboard.slides?.items ?? []);

const submitLabel = computed(() => {
    if (props.isSaving) {
        return props.mode === 'edit' ? 'Saving changes...' : 'Saving slide...';
    }
    return props.mode === 'edit' ? 'Save changes' : 'Save slide';
});

watch(() => props.resetToken, () => {
    if (fileInput.value) fileInput.value.value = '';
});

onBeforeUnmount(() => {
    if (props.form.image_preview_url) {
        URL.revokeObjectURL(props.form.image_preview_url);
    }
});

function fieldError(field) {
    const value = props.errors[field];
    if (Array.isArray(value)) return value[0] ?? '';
    return value ?? '';
}

function handleImageChange(event) {
    const [file] = event.target.files ?? [];
    if (props.form.image_preview_url) URL.revokeObjectURL(props.form.image_preview_url);
    if (!file) {
        props.form.image = null;
        props.form.image_preview_url = '';
        return;
    }
    props.form.image = file;
    props.form.image_preview_url = URL.createObjectURL(file);
    props.form.remove_existing_image = false;
}

function removeImage() {
    if (props.form.image_preview_url) URL.revokeObjectURL(props.form.image_preview_url);
    props.form.image = null;
    props.form.image_preview_url = '';
    props.form.existing_image_url = '';
    props.form.existing_image_name = '';
    props.form.remove_existing_image = true;
    if (fileInput.value) fileInput.value.value = '';
}
</script>

<style scoped>
/* Card */
.slide-card {
    background: #ffffff;
    border: 1px solid #f0e6ed;
    box-shadow: 0 1px 3px 0 rgba(162, 95, 136, 0.06), 0 1px 2px -1px rgba(162, 95, 136, 0.04);
    transition: box-shadow 0.2s;
}
.slide-card:hover {
    box-shadow: 0 4px 12px 0 rgba(162, 95, 136, 0.1);
}

/* Primary button */
.btn-primary {
    background: #A25F88;
    transition: background 0.2s;
}
.btn-primary:hover:not(:disabled) {
    background: #8a4f74;
}
.btn-primary:disabled {
    cursor: not-allowed;
}

/* Input / Select field */
.field {
    border: 1px solid #e8d5e3;
    border-radius: 0.5rem;
    background: #fff;
    padding: 0.375rem 0.625rem;
    font-size: 0.75rem;
    color: #374151;
    outline: none;
    transition: border-color 0.15s;
}
.field:focus {
    border-color: #A25F88;
    box-shadow: 0 0 0 3px rgba(162, 95, 136, 0.12);
}

/* Toggle switch */
.toggle-track {
    width: 2.25rem;
    height: 1.25rem;
    background: #e2d5de;
    border-radius: 9999px;
    transition: background 0.2s;
}
.toggle-thumb {
    position: absolute;
    top: 0.125rem;
    left: 0.125rem;
    width: 1rem;
    height: 1rem;
    background: #fff;
    border-radius: 9999px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    transition: transform 0.2s;
}
</style>