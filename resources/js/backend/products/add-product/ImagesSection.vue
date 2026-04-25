<template>
    <section class="rounded-[24px] border border-slate-200/80 bg-white p-5 sm:p-6">
        <div>
            <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Images</p>
            <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Product images</h4>
        </div>

        <input ref="imageInput" type="file" accept="image/*" multiple class="hidden" @change="handleImageSelection" />

        <div class="mt-5 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-5 py-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="chatgpt-title text-base text-slate-900">Upload images</p>
                    <p class="mt-1 text-sm text-slate-500">{{ displayedImages.length }} file(s)</p>
                </div>
                <button type="button" class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5" @click="openImagePicker">
                    Select files
                </button>
            </div>
        </div>

        <p v-if="imagesError()" class="mt-3 text-sm text-rose-600">{{ imagesError() }}</p>

        <div v-if="displayedImages.length" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article v-for="image in displayedImages" :key="image.key" class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                <img :src="image.url" :alt="image.name" class="h-44 w-full object-cover" />
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ image.name }}</p>
                        <p class="chatgpt-kicker mt-1 text-xs uppercase text-slate-400">{{ image.meta }}</p>
                    </div>
                    <button
                        type="button"
                        class="chatgpt-pill rounded-full border border-slate-200 px-3 py-1.5 text-xs uppercase text-slate-500 transition hover:border-rose-200 hover:text-rose-600"
                        @click="removeImage(image)"
                    >
                        Remove
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
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

const imageInput = ref(null);
const imagePreviews = ref([]);
const displayedImages = computed(() => [
    ...(props.form.existing_images ?? []).map((image) => ({
        kind: 'existing',
        key: `existing-${image.id}`,
        id: image.id,
        name: image.name,
        url: image.url,
        meta: 'Saved image',
    })),
    ...imagePreviews.value.map((preview, index) => ({
        kind: 'new',
        key: preview.id,
        index,
        name: preview.name,
        url: preview.url,
        meta: formatFileSize(preview.size),
    })),
]);

onBeforeUnmount(() => {
    clearPreviewUrls();
});

watch(
    () => props.resetToken,
    () => {
        clearImageSelection();
    },
);

function imagesError() {
    return fieldError('images') || firstErrorStartingWith('images.');
}

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}

function firstErrorStartingWith(prefix) {
    const match = Object.keys(props.errors).find((key) => key.startsWith(prefix));

    return match ? props.errors[match]?.[0] ?? '' : '';
}

function openImagePicker() {
    imageInput.value?.click();
}

function handleImageSelection(event) {
    const files = Array.from(event.target?.files ?? []);

    if (!files.length) {
        return;
    }

    files.forEach((file) => {
        props.form.images.push(file);
        imagePreviews.value.push({
            id: `${file.name}-${file.lastModified}-${imagePreviews.value.length}`,
            name: file.name,
            size: file.size,
            url: URL.createObjectURL(file),
        });
    });

    if (imageInput.value) {
        imageInput.value.value = '';
    }
}

function removeImage(image) {
    if (image.kind === 'existing') {
        props.form.removed_image_ids = Array.from(new Set([
            ...(props.form.removed_image_ids ?? []),
            image.id,
        ]));
        props.form.existing_images = (props.form.existing_images ?? []).filter((item) => item.id !== image.id);

        return;
    }

    const preview = imagePreviews.value[image.index];

    if (preview) {
        URL.revokeObjectURL(preview.url);
    }

    imagePreviews.value.splice(image.index, 1);
    props.form.images.splice(image.index, 1);
}

function clearPreviewUrls() {
    imagePreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.url);
    });
}

function clearImageSelection() {
    clearPreviewUrls();
    imagePreviews.value = [];
    props.form.images = [];
    props.form.removed_image_ids = [];

    if (imageInput.value) {
        imageInput.value.value = '';
    }
}

function formatFileSize(bytes) {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}
</script>
