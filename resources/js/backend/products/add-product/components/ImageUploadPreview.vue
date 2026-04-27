<template>
    <section class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6B7280]">Media</p>
                <h3 class="mt-2 text-xl font-semibold text-[#111827]">Product images</h3>
                <p class="mt-2 text-sm text-[#6B7280]">Upload one or more product images and preview them before saving.</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-[16px] bg-[#A25F88] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                @click="openImagePicker"
            >
                Add images
            </button>
        </div>

        <input ref="imageInput" type="file" accept="image/*" multiple class="hidden" @change="handleImageSelection" />

        <div class="mt-5 rounded-[22px] border border-dashed border-[#D7DEE7] bg-[#F8FAFC] px-5 py-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-[#111827]">{{ displayedImages.length }} image{{ displayedImages.length === 1 ? '' : 's' }} selected</p>
                    <p class="mt-1 text-sm text-[#6B7280]">PNG, JPG, or WEBP up to 4MB each.</p>
                </div>
                <button
                    type="button"
                    class="rounded-[16px] border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-medium text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                    @click="openImagePicker"
                >
                    Browse files
                </button>
            </div>
        </div>

        <p v-if="imagesError()" class="mt-3 text-sm text-rose-600">{{ imagesError() }}</p>

        <div v-if="displayedImages.length" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="image in displayedImages"
                :key="image.key"
                class="overflow-hidden rounded-[22px] border border-[#E5E7EB] bg-[#F8FAFC]"
            >
                <img :src="image.url" :alt="image.name" class="h-40 w-full object-cover" />
                <div class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-[#111827]">{{ image.name }}</p>
                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#6B7280]">{{ image.meta }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full border border-[#E5E7EB] bg-white px-3 py-1.5 text-xs font-semibold text-[#6B7280] transition hover:border-rose-200 hover:text-rose-600"
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

watch(
    () => props.resetToken,
    () => {
        clearImageSelection();
    },
);

onBeforeUnmount(() => {
    clearPreviewUrls();
});

function imagesError() {
    return fieldError('images') || firstErrorStartingWith('images.');
}

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}

function firstErrorStartingWith(prefix) {
    const key = Object.keys(props.errors).find((item) => item.startsWith(prefix));

    return key ? props.errors[key]?.[0] ?? '' : '';
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
