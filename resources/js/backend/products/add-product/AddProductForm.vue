<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <section class="overflow-hidden rounded-[30px] border border-[#E5E7EB] bg-white shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
            <div class="flex flex-col gap-4 border-b border-[#E5E7EB] px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6 sm:py-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.26em] text-[#6B7280]">{{ mode === 'edit' ? 'Product update' : 'Catalog creation' }}</p>
                    <h2 class="mt-2 text-2xl font-semibold text-[#111827]">
                        {{ mode === 'edit' ? `Edit ${productName || 'product'}` : 'Add a new product' }}
                    </h2>
                    
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[16px] border border-[#E5E7EB] bg-white px-4 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                    @click="$emit('reset')"
                >
                    Reset form
                </button>
            </div>

            <div class="grid gap-6 bg-[#F8FAFC] px-4 py-4 sm:px-6 sm:py-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)]">
                <div class="space-y-6">
                    <section class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6B7280]">Details</p>
                            <h3 class="mt-2 text-xl font-semibold text-[#111827]">Product information</h3>
                        </div>

                        <div class="mt-5 grid gap-5">
                            <FormInput
                                id="product-name"
                                :error="fieldError('name')"
                                label="Product Name"
                                :model-value="form.name"
                                placeholder="Premium Cotton Shirt"
                                required
                                @update:modelValue="form.name = $event"
                            />

                            <FormSelect
                                id="product-category"
                                :error="fieldError('category_id')"
                                label="Category"
                                :model-value="form.category_id"
                                :options="categoryOptions"
                                placeholder="Select category"
                                required
                                @update:modelValue="handleCategoryChange"
                            />

                            <FormInput
                                id="product-description"
                                as="textarea"
                                :error="fieldError('description')"
                                :hint="descriptionHint"
                                label="Description"
                                :model-value="form.description"
                                placeholder="Describe the product, material, and what makes it useful for customers."
                                required
                                :rows="6"
                                @update:modelValue="form.description = $event"
                            />
                        </div>
                    </section>

                    <VariantFields
                        :errors="errors"
                        :form="form"
                        :selected-category-name="selectedCategoryName"
                        :selected-category-slug="selectedCategorySlug"
                    />

                    <ImageUploadPreview :errors="errors" :form="form" :reset-token="resetToken" />
                </div>

                <div class="space-y-6">
                    <section class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6B7280]">Commerce</p>
                            <h3 class="mt-2 text-xl font-semibold text-[#111827]">Price and stock</h3>
                        </div>

                        <div class="mt-5 grid gap-5">
                            <FormInput
                                id="product-price"
                                :error="fieldError('price')"
                                inputmode="decimal"
                                label="Price"
                                min="0"
                                :model-value="form.price"
                                placeholder="79.99"
                                required
                                step="0.01"
                                type="number"
                                @update:modelValue="form.price = $event"
                            />

                            <FormInput
                                id="product-stock"
                                :error="fieldError('stock_quantity')"
                                inputmode="numeric"
                                label="Stock"
                                min="0"
                                :model-value="form.stock_quantity"
                                placeholder="24"
                                required
                                step="1"
                                type="number"
                                @update:modelValue="form.stock_quantity = $event"
                            />
                        </div>
                    </section>

                    <section class="rounded-[26px] border border-[#E5E7EB] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.05)] sm:p-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#6B7280]">Publishing</p>
                            <h3 class="mt-2 text-xl font-semibold text-[#111827]">Status</h3>
                        </div>

                        <div class="mt-5">
                            <FormSelect
                                id="product-status"
                                :error="fieldError('status')"
                                label="Product Status"
                                :model-value="form.status"
                                :options="statusOptions"
                                placeholder="Select status"
                                required
                                @update:modelValue="form.status = $event"
                            />
                        </div>

                        <div class="mt-6 grid gap-4">
                            <div class="rounded-[20px] border border-[#E5E7EB] bg-[#EEF2F7] px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Category</p>
                                <p class="mt-2 text-sm font-semibold text-[#111827]">{{ selectedCategoryName || 'Select category' }}</p>
                            </div>
                            <div class="rounded-[20px] border border-[#E5E7EB] bg-[#EEF2F7] px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Variant fields</p>
                                <p class="mt-2 text-sm font-semibold text-[#111827]">{{ variantFieldCount }} required option{{ variantFieldCount === 1 ? '' : 's' }}</p>
                            </div>
                            <div class="rounded-[20px] border border-[#E5E7EB] bg-[#EEF2F7] px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Images</p>
                                <p class="mt-2 text-sm font-semibold text-[#111827]">{{ totalImageCount }} file{{ totalImageCount === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-[#E5E7EB] bg-white px-5 py-5 sm:flex-row sm:justify-end sm:px-6">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-[16px] border border-[#E5E7EB] bg-white px-5 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                    @click="$emit('reset')"
                >
                    Reset
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-[16px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isSaving"
                >
                    {{ submitLabel }}
                </button>
            </div>
        </section>
    </form>
</template>

<script setup>
import { computed } from 'vue';

import { categoryFieldsForSlug, emptyVariantValues } from './categoryConfig.js';
import FormInput from './components/FormInput.vue';
import FormSelect from './components/FormSelect.vue';
import ImageUploadPreview from './components/ImageUploadPreview.vue';
import VariantFields from './components/VariantFields.vue';

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

const categoryOptions = computed(() => (props.dashboard.form?.categories ?? []).map((category) => ({
    label: category.name,
    value: String(category.id),
})));

const selectedCategory = computed(() => (props.dashboard.form?.categories ?? [])
    .find((category) => String(category.id) === String(props.form.category_id)) ?? null);

const selectedCategoryName = computed(() => selectedCategory.value?.name ?? '');
const selectedCategorySlug = computed(() => selectedCategory.value?.slug ?? '');
const variantFieldCount = computed(() => categoryFieldsForSlug(selectedCategorySlug.value).length);
const totalImageCount = computed(() => (props.form.images?.length ?? 0) + (props.form.existing_images?.length ?? 0));
const descriptionHint = computed(() => `${String(props.form.description ?? '').trim().length} chars`);
const submitLabel = computed(() => {
    if (props.isSaving) {
        return props.mode === 'edit' ? 'Saving changes...' : 'Saving product...';
    }

    return props.mode === 'edit' ? 'Save changes' : 'Create product';
});

const statusOptions = computed(() => {
    const options = [
        { label: 'Active', value: 'active' },
        { label: 'Draft', value: 'draft' },
    ];

    if (props.form.status === 'scheduled') {
        options.push({ label: 'Scheduled', value: 'scheduled' });
    }

    return options;
});

function handleCategoryChange(value) {
    if (String(value) !== String(props.form.category_id)) {
        props.form.variant_values = emptyVariantValues();
    }

    props.form.category_id = value;
}

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}
</script>
