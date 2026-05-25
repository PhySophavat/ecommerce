<template>
    <section class="space-y-6">
        <form id="slides-form" class="admin-card rounded-[32px] px-6 py-6" @submit.prevent="$emit('submit')">
            <div class="flex flex-col gap-4 border-b border-[#e2e7f6] pb-5">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="chatgpt-title text-2xl text-slate-950">{{ mode === 'edit' ? 'Edit slide' : 'Create slide' }}</h3>
                    <button
                        type="button"
                        class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-950"
                        @click="$emit('reset')"
                    >
                        {{ mode === 'edit' ? 'Reset changes' : 'Reset form' }}
                    </button>
                </div>
                <p v-if="mode === 'edit' && slideTitle" class="text-sm text-slate-500">{{ slideTitle }}</p>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_320px]">
                <div class="space-y-4">
                    <div>
                        <label class="chatgpt-label text-sm text-slate-700" for="slide-title">Title</label>
                        <input
                            id="slide-title"
                            v-model="form.title"
                            type="text"
                            placeholder="Slide title"
                            class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-[#3457ff]"
                            :class="fieldError('title') ? 'border-rose-300' : 'border-slate-200'"
                        >
                        <p v-if="fieldError('title')" class="mt-2 text-sm text-rose-600">{{ fieldError('title') }}</p>
                    </div>

                    <div>
                        <label class="chatgpt-label text-sm text-slate-700" for="slide-category">Category</label>
                        <select
                            id="slide-category"
                            v-model="form.category_id"
                            class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-[#3457ff]"
                            :class="fieldError('category_id') ? 'border-rose-300' : 'border-slate-200'"
                        >
                            <option value="">All categories</option>
                            <option v-for="category in dashboard.form.categories" :key="category.id" :value="String(category.id)">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="fieldError('category_id')" class="mt-2 text-sm text-rose-600">{{ fieldError('category_id') }}</p>
                    </div>

                    <div class="max-w-[560px]">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <label class="chatgpt-label text-sm text-slate-700">Image</label>
                            <p class="max-w-[200px] text-right text-xs text-slate-500">{{ imageNameLabel }}</p>
                        </div>

                        <div class="admin-muted-panel mt-2 rounded-[24px] border border-dashed border-slate-300 p-3.5">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleImageChange"
                            >
                            <div class="space-y-3">
                                <div class="relative overflow-hidden rounded-[18px] border border-slate-200 bg-[linear-gradient(135deg,#f8fafc,#eef2ff)]">
                                    <div class="h-44 w-full bg-cover bg-center md:h-48" :style="previewStyle"></div>
                                    <div v-if="!hasImage" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-slate-400">
                                        No image
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        class="admin-primary-button rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5"
                                        @click="fileInput?.click()"
                                    >
                                        {{ hasImage ? 'Replace image' : 'Upload image' }}
                                    </button>
                                    <button
                                        v-if="hasImage"
                                        type="button"
                                        class="admin-secondary-button rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-950"
                                        @click="removeImage"
                                    >
                                        Remove image
                                    </button>
                                </div>

                                <p v-if="fieldError('image')" class="text-sm text-rose-600">{{ fieldError('image') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="String(form.is_active)"
                            class="relative inline-flex h-8 w-14 items-center rounded-full border transition"
                            :class="form.is_active ? 'border-emerald-300 bg-emerald-500/20' : 'border-slate-300 bg-white'"
                            @click="form.is_active = !form.is_active"
                        >
                            <span class="sr-only">Toggle slide status</span>
                            <span
                                class="inline-block h-6 w-6 rounded-full bg-white shadow-sm transition"
                                :class="form.is_active ? 'translate-x-7' : 'translate-x-1'"
                            ></span>
                        </button>
                    </div>

                    <div>
                        <label class="chatgpt-label text-sm text-slate-700" for="slide-order">Sort order</label>
                        <input
                            id="slide-order"
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-[#3457ff]"
                            :class="fieldError('sort_order') ? 'border-rose-300' : 'border-slate-200'"
                        >
                        <p v-if="fieldError('sort_order')" class="mt-2 text-sm text-rose-600">{{ fieldError('sort_order') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-[#e2e7f6] pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('reset')"
                >
                    {{ mode === 'edit' ? 'Discard edits' : 'Clear' }}
                </button>
                <button
                    type="submit"
                    class="admin-primary-button rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="isSaving"
                >
                    {{ submitLabel }}
                </button>
            </div>
        </form>

        <section id="slides-table" class="admin-card overflow-hidden rounded-[32px]">
            <div class="flex flex-col gap-2 border-b border-[#e2e7f6] px-3 py-3">
                <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                    <h3 class="font-bold text-base text-slate-950">Slides table</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            id="slide-table-category"
                            v-model="selectedTableCategory"
                            class="min-w-[120px] rounded border border-[#dde4f7] bg-white px-2 py-1 text-xs font-semibold text-slate-700 outline-none"
                        >
                            <option value="">All</option>
                            <option v-for="category in dashboard.form.categories" :key="category.id" :value="String(category.id)">
                                {{ category.name }}
                            </option>
                        </select>
                        <span class="rounded border border-[#dde4f7] bg-white px-2 py-1 text-[10px] font-semibold uppercase text-slate-500">
                            {{ filteredSlides.length }} shown
                        </span>
                    </div>
                </div>
            </div>

            <div class="soft-scroll overflow-x-auto px-2 pb-2 pt-2">
                <table class="w-full min-w-[760px] text-xs">
                    <thead class="text-left text-[10px] uppercase text-slate-400">
                        <tr>
                            <th class="px-2 py-1">Preview</th>
                            <th class="px-2 py-1">Slide</th>
                            <th class="px-2 py-1">Category</th>
                            <th class="px-2 py-1">Order</th>
                            <th class="px-2 py-1">Status</th>
                            <th class="px-2 py-1 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-if="!filteredSlides.length">
                            <td colspan="6" class="px-2 py-4 text-center text-xs text-slate-400">
                                {{ tableEmptyMessage }}
                            </td>
                        </tr>

                        <tr
                            v-for="slide in filteredSlides"
                            :key="`slide-${slide.id}`"
                            class="border-b border-[#f1f5fa]"
                        >
                            <td class="px-2 py-2">
                                <div class="relative h-12 w-16 overflow-hidden rounded border border-slate-200 bg-[linear-gradient(135deg,#f8fafc,#eef2ff)]">
                                    <img
                                        v-if="slide.image_url"
                                        :src="slide.image_url"
                                        :alt="slide.title"
                                        class="h-full w-full object-cover"
                                    >
                                    <div v-else class="flex h-full items-center justify-center text-[10px] font-medium text-slate-400">
                                        No image
                                    </div>
                                </div>
                            </td>
                            <td class="px-2 py-2">
                                <p class="font-bold text-slate-900">{{ slide.title }}</p>
                                <p v-if="slide.eyebrow" class="mt-0.5 text-[10px] uppercase tracking-[0.14em] text-slate-400">
                                    {{ slide.eyebrow }}
                                </p>
                                <p v-else class="mt-0.5 text-[10px] text-slate-400">
                                    {{ slide.button_text || 'No button text' }}
                                </p>
                            </td>
                            <td class="px-2 py-2">
                                <p class="font-semibold text-slate-700">{{ slide.category }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ slide.updated_at || 'Recently updated' }}</p>
                            </td>
                            <td class="px-2 py-2 font-semibold text-slate-700">
                                #{{ slide.sort_order }}
                            </td>
                            <td class="px-2 py-2">
                                <span class="rounded px-2 py-1 text-[10px] font-bold uppercase" :class="slideStatusClass(slide.is_active)">
                                    {{ slide.is_active ? 'Active' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-right">
                                <button
                                    type="button"
                                    class="rounded bg-[#2563eb] px-2 py-1 text-xs font-semibold text-white hover:bg-[#174ea6]"
                                    @click="$emit('edit-slide', slide)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="ml-1 rounded bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-200"
                                    :disabled="deletingSlideId === slide.id"
                                    @click="$emit('delete-slide', slide)"
                                >
                                    {{ deletingSlideId === slide.id ? '...' : 'Del' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-3 border-t border-[#e2e7f6] px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">{{ tableFooterMessage }}</p>
                <button
                    v-if="selectedTableCategory"
                    type="button"
                    class="rounded-2xl border border-[#d8e0f5] bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-900"
                    @click="selectedTableCategory = ''"
                >
                    Clear filter
                </button>
            </div>
        </section>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    deletingSlideId: {
        type: [Number, String, null],
        default: null,
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
    resetToken: {
        type: Number,
        required: true,
    },
    slideTitle: {
        type: String,
        default: '',
    },
});

defineEmits(['delete-slide', 'edit-slide', 'reset', 'submit']);

const fileInput = ref(null);
const slides = computed(() => props.dashboard.slides?.items ?? []);
const selectedTableCategory = ref('');
const filteredSlides = computed(() => {
    if (!selectedTableCategory.value) {
        return slides.value;
    }

    return slides.value.filter((slide) => String(slide.category_id ?? '') === selectedTableCategory.value);
});
const selectedTableCategoryLabel = computed(() => (
    props.dashboard.form?.categories?.find((category) => String(category.id) === selectedTableCategory.value)?.name ?? ''
));
const submitLabel = computed(() => {
    if (props.isSaving) {
        return props.mode === 'edit' ? 'Saving changes...' : 'Saving slide...';
    }

    return props.mode === 'edit' ? 'Save changes' : 'Save slide';
});
const hasImage = computed(() => Boolean(props.form.image_preview_url || props.form.existing_image_url));
const imageNameLabel = computed(() => (
    props.form.image?.name
    || props.form.existing_image_name
    || 'No image selected yet.'
));
const tableEmptyMessage = computed(() => (
    selectedTableCategoryLabel.value
        ? `No slides found in ${selectedTableCategoryLabel.value}.`
        : 'No slides yet.'
));
const tableFooterMessage = computed(() => (
    selectedTableCategoryLabel.value
        ? `${filteredSlides.value.length} ${filteredSlides.value.length === 1 ? 'slide matches' : 'slides match'} ${selectedTableCategoryLabel.value}.`
        : 'Create or edit slides above, then manage them from this table.'
));
const previewStyle = computed(() => {
    const previewUrl = props.form.image_preview_url || props.form.existing_image_url;

    if (previewUrl) {
        return {
            backgroundImage: `url(${previewUrl})`,
        };
    }

    return {};
});

watch(() => props.resetToken, () => {
    if (fileInput.value) {
        fileInput.value.value = '';
    }
});

onBeforeUnmount(() => {
    if (props.form.image_preview_url) {
        URL.revokeObjectURL(props.form.image_preview_url);
    }
});

function fieldError(field) {
    const value = props.errors[field];

    if (Array.isArray(value)) {
        return value[0] ?? '';
    }

    return value ?? '';
}

function slideStatusClass(isActive) {
    return isActive
        ? 'bg-[#e8fbf4] text-[#1fb586]'
        : 'bg-[#fff4df] text-[#ee9d15]';
}

function handleImageChange(event) {
    const [file] = event.target.files ?? [];

    if (props.form.image_preview_url) {
        URL.revokeObjectURL(props.form.image_preview_url);
    }

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
    if (props.form.image_preview_url) {
        URL.revokeObjectURL(props.form.image_preview_url);
    }

    props.form.image = null;
    props.form.image_preview_url = '';
    props.form.existing_image_url = '';
    props.form.existing_image_name = '';
    props.form.remove_existing_image = true;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}
</script>
