<template>
    <section class="space-y-6">
        <form id="slides-form" class="admin-card rounded-[24px] px-6 py-6" @submit.prevent="$emit('submit')">
            <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-5">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="chatgpt-title text-2xl text-slate-950">{{ mode === 'edit' ? 'Edit slide' : 'Create slide' }}</h3>
                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950"
                        @click="$emit('reset')"
                    >
                        {{ mode === 'edit' ? 'Reset changes' : 'Reset form' }}
                    </button>
                </div>
                <p v-if="mode === 'edit' && slideTitle" class="text-sm text-slate-500">{{ slideTitle }}</p>
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-5">
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

                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <label class="chatgpt-label text-sm text-slate-700">Image</label>
                            <p class="max-w-[220px] text-right text-xs text-slate-500">{{ imageNameLabel }}</p>
                        </div>

                        <div class="mt-2 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 p-4">
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleImageChange"
                            >
                            <div class="space-y-4">
                                <div class="relative overflow-hidden rounded-[20px] border border-slate-200 bg-[linear-gradient(135deg,#f8fafc,#eef2ff)]">
                                    <div class="aspect-[16/9] bg-cover bg-center" :style="previewStyle"></div>
                                    <div v-if="!hasImage" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-slate-400">
                                        No image
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.28)] transition hover:-translate-y-0.5"
                                        @click="fileInput?.click()"
                                    >
                                        {{ hasImage ? 'Replace image' : 'Upload image' }}
                                    </button>
                                    <button
                                        v-if="hasImage"
                                        type="button"
                                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
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

                <div class="space-y-5">
                    <div class="rounded-[24px] border border-slate-200 bg-slate-50/80 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="chatgpt-label text-sm text-slate-700">Status</p>
                                <p class="mt-1 text-sm text-slate-500">{{ statusLabel }}</p>
                            </div>
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

            <div class="mt-6 flex flex-col gap-3 border-t border-slate-200/80 pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('reset')"
                >
                    {{ mode === 'edit' ? 'Discard edits' : 'Clear' }}
                </button>
                <button
                    type="submit"
                    class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.28)] transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="isSaving"
                >
                    {{ submitLabel }}
                </button>
            </div>
        </form>

        <section class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="chatgpt-title text-xl text-slate-950">Slide information</h3>
                    <p class="text-sm text-slate-500">View, edit, or delete existing slides below.</p>
                </div>
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.18em] text-slate-500">
                    {{ slides.length }} {{ slides.length === 1 ? 'slide' : 'slides' }}
                </span>
            </div>

            <article
                v-for="slide in slides"
                :key="`slide-${slide.id}`"
                class="admin-card rounded-[24px] px-5 py-5"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                    <div class="relative h-24 w-full overflow-hidden rounded-[20px] border border-slate-200 bg-[linear-gradient(135deg,#f8fafc,#eef2ff)] sm:w-32 sm:shrink-0">
                        <div
                            v-if="slide.image_url"
                            class="absolute inset-0 bg-cover bg-center"
                            :style="{ backgroundImage: `url(${slide.image_url})` }"
                        ></div>
                        <div v-else class="flex h-full items-center justify-center text-xs font-medium text-slate-400">
                            No image
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="rounded-full border px-3 py-1 text-xs uppercase tracking-[0.18em]"
                                :class="slide.is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500'"
                            >
                                {{ slide.is_active ? 'Active' : 'Draft' }}
                            </span>
                            <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs uppercase tracking-[0.18em] text-slate-500">
                                Sort {{ slide.sort_order }}
                            </span>
                        </div>

                        <h4 class="mt-3 text-xl font-semibold text-slate-950">{{ slide.title }}</h4>
                        <p class="mt-1 text-sm text-slate-500">{{ slide.category }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                            @click="$emit('edit-slide', slide)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-600 transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="deletingSlideId === slide.id"
                            @click="$emit('delete-slide', slide)"
                        >
                            {{ deletingSlideId === slide.id ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </article>

            <section
                v-if="slides.length === 0"
                class="admin-card rounded-[24px] border border-dashed px-6 py-14 text-center text-sm text-slate-500"
            >
                No slides yet.
            </section>
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
const submitLabel = computed(() => {
    if (props.isSaving) {
        return props.mode === 'edit' ? 'Saving changes...' : 'Saving slide...';
    }

    return props.mode === 'edit' ? 'Save changes' : 'Save slide';
});
const statusLabel = computed(() => (props.form.is_active ? 'Active on frontend' : 'Saved as draft'));
const hasImage = computed(() => Boolean(props.form.image_preview_url || props.form.existing_image_url));
const imageNameLabel = computed(() => (
    props.form.image?.name
    || props.form.existing_image_name
    || 'No image selected yet.'
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
