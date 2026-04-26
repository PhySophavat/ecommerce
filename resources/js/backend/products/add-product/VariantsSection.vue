<template>
    <section class="admin-form-section rounded-[24px] p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Variants</p>
                <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Variant setup</h4>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-if="selectedCategory && form.variant_groups.length"
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="resetVariantGroups"
                >
                    Clear
                </button>
            </div>
        </div>

        <div v-if="!selectedCategory" class="mt-5 rounded-[24px] border border-dashed border-slate-200 bg-slate-50 px-5 py-5 text-sm text-slate-500">
            Select category first.
        </div>

        <template v-else>
            <!-- Single Form for Adding Types -->
            <article class="mt-5 rounded-[24px] border border-slate-200 bg-[#fbfcff] p-4">
                <div class="grid gap-3 xl:grid-cols-[220px_220px_minmax(0,1fr)_auto]">
                    <label class="block">
                        <span class="chatgpt-label text-sm">Type</span>
                        <select
                            v-model="formState.selectedType"
                            class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                        >
                            <option value="">Select type</option>
                            <option v-for="preset in availableFormTypes" :key="preset.name" :value="preset.name">
                                {{ preset.name }}
                            </option>
                        </select>
                    </label>

                    <label class="block relative">
                        <span class="chatgpt-label text-sm">Option</span>
                        <input
                            v-model="formState.selectedOption"
                            type="text"
                            placeholder="Type or select option"
                            class="mt-3 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :disabled="!formState.selectedType"
                            @focus="formState.showSuggestions = true"
                            @blur="setTimeout(() => { formState.showSuggestions = false }, 150)"
                        />
                        <div
                            v-if="formState.showSuggestions && formState.selectedType && filteredOptionSuggestions.length"
                            class="absolute top-full left-0 right-0 z-10 mt-1 max-h-48 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-lg"
                        >
                            <button
                                v-for="option in filteredOptionSuggestions"
                                :key="option"
                                type="button"
                                class="w-full px-4 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-100"
                                @click="selectOptionFromDropdown(option)"
                            >
                                {{ option }}
                            </button>
                        </div>
                    </label>

                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="!formState.selectedOption"
                            @click="addTypeOption"
                        >
                            Add
                        </button>
                    </div>
                </div>
            </article>

            <!-- Added Items Table -->
            <div v-if="allAddedItems.length" class="mt-6">
                <h5 class="mb-4 text-sm font-semibold text-slate-950">Added Type & Options</h5>
                <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                    <table class="w-full">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Type</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Option</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-slate-900">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in allAddedItems"
                                :key="`${item.groupIndex}-${item.option}`"
                                class="border-b border-slate-200 transition hover:bg-slate-50"
                            >
                                <td class="px-6 py-3 text-sm text-slate-700">{{ item.type }}</td>
                                <td class="px-6 py-3 text-sm text-slate-700">{{ item.option }}</td>
                                <td class="px-6 py-3 text-center">
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                                        @click="deleteTableItem(item.groupIndex, item.option)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3 rounded-[22px] border border-slate-200 bg-[#fbfcff] px-4 py-3 text-sm text-slate-600">
                <span>{{ form.variant_groups.length }} group{{ form.variant_groups.length === 1 ? '' : 's' }}</span>
                <span>{{ generatedVariants.length }} variant{{ generatedVariants.length === 1 ? '' : 's' }}</span>
                <span v-if="isCombinationLimitReached" class="text-amber-600">Limit {{ maxVariantCount }}</span>
            </div>
        </template>

        <div v-if="form.variants.length" class="mt-6 space-y-3">
            <article
                v-for="(variant, index) in form.variants"
                :key="variantKey(variant.attributes)"
                class="rounded-[24px] border border-slate-200 bg-white p-4"
            >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <h5 class="truncate text-base font-semibold text-slate-950">{{ variant.label }}</h5>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="attribute in variant.attributes"
                                :key="`${variant.label}-${attribute.name}`"
                                class="rounded-full border border-slate-200 bg-[#fbfcff] px-3 py-1 text-xs text-slate-600"
                            >
                                {{ attribute.value }}
                            </span>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <div v-if="variantImageUrl(variant)" class="h-14 w-14 overflow-hidden rounded-2xl border border-slate-200 bg-[#fbfcff]">
                            <img :src="variantImageUrl(variant)" :alt="variant.label" class="h-full w-full object-cover" />
                        </div>
                        <label
                            :for="variantImageInputId(index)"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                        >
                            Image
                        </label>
                        <button
                            v-if="hasVariantImage(variant)"
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-rose-600"
                            @click="clearVariantImage(index)"
                        >
                            Remove
                        </button>
                        <input
                            :id="variantImageInputId(index)"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleVariantImageSelection(index, $event)"
                        />
                    </div>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-3">
                    <label v-if="showsVariantSku" class="block">
                        <span class="chatgpt-label text-sm">SKU</span>
                        <input
                            v-model="variant.variant_sku"
                            type="text"
                            placeholder="SKU-BLK-M"
                            class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :class="variantFieldError(index, 'variant_sku') ? 'border-rose-300' : 'border-slate-200'"
                        />
                        <p v-if="variantFieldError(index, 'variant_sku')" class="mt-2 text-sm text-rose-600">{{ variantFieldError(index, 'variant_sku') }}</p>
                    </label>

                    <label class="block">
                        <span class="chatgpt-label text-sm">Price</span>
                        <input
                            v-model="variant.price"
                            type="number"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            placeholder="69.00"
                            class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :class="variantFieldError(index, 'price') ? 'border-rose-300' : 'border-slate-200'"
                        />
                        <p v-if="variantFieldError(index, 'price')" class="mt-2 text-sm text-rose-600">{{ variantFieldError(index, 'price') }}</p>
                    </label>

                    <label class="block">
                        <span class="chatgpt-label text-sm">Stock</span>
                        <input
                            v-model="variant.stock"
                            type="number"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            placeholder="12"
                            class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :class="variantFieldError(index, 'stock') ? 'border-rose-300' : 'border-slate-200'"
                        />
                        <p v-if="variantFieldError(index, 'stock')" class="mt-2 text-sm text-rose-600">{{ variantFieldError(index, 'stock') }}</p>
                    </label>
                </div>

                <p v-if="variantFieldError(index, 'image')" class="mt-3 text-sm text-rose-600">{{ variantFieldError(index, 'image') }}</p>
            </article>
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, watch, reactive } from 'vue';

const maxVariantCount = 60;

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

const formState = reactive({
    selectedType: '',
    selectedOption: '',
    showSuggestions: false,
});

const selectedCategory = computed(() => (props.dashboard.form?.categories ?? [])
    .find((category) => String(category.id) === String(props.form.category_id)) ?? null);
const selectedPresetGroups = computed(() => props.dashboard.form?.variant_presets?.[selectedCategory.value?.slug ?? ''] ?? []);
const parsedVariantGroups = computed(() => (props.form.variant_groups ?? [])
    .map((group) => ({
        name: group.name,
        options: groupOptions(group),
    }))
    .filter((group) => group.name && group.options.length));
const allAddedItems = computed(() => {
    const items = [];
    (props.form.variant_groups ?? []).forEach((group, groupIndex) => {
        groupOptions(group).forEach((option) => {
            items.push({
                groupIndex,
                type: group.name,
                option,
            });
        });
    });
    return items;
});
const generatedVariantState = computed(() => buildVariantCombinations(parsedVariantGroups.value, maxVariantCount));
const generatedVariants = computed(() => generatedVariantState.value.items);
const isCombinationLimitReached = computed(() => generatedVariantState.value.truncated);
const showsVariantSku = computed(() => ['fashion', 'electronic'].includes(selectedCategory.value?.slug ?? ''));
const hasAvailableGroups = computed(() => availableGroupChoices('').length > 0);
const availableFormTypes = computed(() => selectedPresetGroups.value.filter((preset) => {
    const selectedNames = new Set(
        (props.form.variant_groups ?? [])
            .map((group) => group.name)
            .filter((name) => name),
    );
    return !selectedNames.has(preset.name);
}));
const formOptionChoices = computed(() => {
    if (!formState.selectedType) return [];
    const preset = selectedPresetGroups.value.find((p) => p.name === formState.selectedType);
    return preset?.suggested_options ?? [];
});
const filteredOptionSuggestions = computed(() => {
    const suggestions = formOptionChoices.value;
    if (!formState.selectedOption) return suggestions;
    
    const input = formState.selectedOption.toLowerCase();
    return suggestions.filter((option) => option.toLowerCase().includes(input));
});

watch(
    () => selectedCategory.value?.slug ?? '',
    (slug) => {
        if (!slug) {
            revokeAllVariantPreviewUrls();
            props.form.variant_groups = [];
            props.form.variant_group_source = null;
            props.form.variants = [];
            formState.selectedType = '';
            formState.selectedOption = '';

            return;
        }

        if (props.form.variant_group_source !== slug) {
            revokeAllVariantPreviewUrls();
            props.form.variant_groups = [];
            props.form.variant_group_source = slug;
            props.form.variants = [];
            formState.selectedType = '';
            formState.selectedOption = '';

            return;
        }

        if (!Array.isArray(props.form.variant_groups) || props.form.variant_groups.length === 0) {
            props.form.variant_groups = [];
        } else {
            props.form.variant_groups = props.form.variant_groups
                .filter((group, index, groups) => group.name && groups.findIndex((item) => item.name === group.name) === index)
                .map((group) => ({
                    ...makeGroup(group.name),
                    ...group,
                }));
        }

        props.form.variant_group_source = slug;
    },
    { immediate: true },
);

watch(
    parsedVariantGroups,
    () => {
        syncVariantsFromGroups();
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => {
    revokeAllVariantPreviewUrls();
});

function makeGroup(name = '') {
    return {
        name,
        options_text: '',
        selected_option: '',
    };
}

function availableGroupChoices(currentName = '') {
    const selectedNames = new Set(
        (props.form.variant_groups ?? [])
            .map((group) => group.name)
            .filter((name) => name && name !== currentName),
    );

    return selectedPresetGroups.value.filter((group) => group.name === currentName || !selectedNames.has(group.name));
}

function addGroup() {
    const nextGroup = availableGroupChoices('').find(Boolean);

    if (!nextGroup) {
        return;
    }

    props.form.variant_groups.push(makeGroup(nextGroup.name));
}

function removeGroup(index) {
    props.form.variant_groups.splice(index, 1);
}

function resetVariantGroups() {
    revokeAllVariantPreviewUrls();
    props.form.variant_groups = selectedPresetGroups.value.length ? [makeGroup(selectedPresetGroups.value[0].name)] : [];
    props.form.variants = [];
    props.form.variant_group_source = selectedCategory.value?.slug ?? null;
}

function addTypeOption() {
    if (!formState.selectedType || !formState.selectedOption) {
        return;
    }

    // Find or create group with this type
    let group = props.form.variant_groups.find((g) => g.name === formState.selectedType);
    
    if (!group) {
        group = makeGroup(formState.selectedType);
        props.form.variant_groups.push(group);
    }

    // Add option to group
    const options = groupOptions(group);
    if (!options.includes(formState.selectedOption)) {
        options.push(formState.selectedOption);
        group.options_text = options.join(', ');
    }

    // Clear form for next entry
    formState.selectedType = '';
    formState.selectedOption = '';
}

function selectOptionFromDropdown(option) {
    formState.selectedOption = option;
    formState.showSuggestions = false;
}

function handleGroupNameChange(group) {
    group.options_text = '';
    group.selected_option = '';
}

function groupMeta(name) {
    return selectedPresetGroups.value.find((group) => group.name === name) ?? null;
}

function groupOptions(group) {
    return String(group?.options_text ?? '')
        .split(',')
        .map((option) => option.trim())
        .filter(Boolean);
}

function groupOptionChoices(group) {
    const presetOptions = groupMeta(group.name)?.suggested_options ?? [];
    const selectedOptions = groupOptions(group);

    return Array.from(new Set([...presetOptions, ...selectedOptions]));
}

function appendGroupOption(index) {
    const group = props.form.variant_groups[index];

    if (!group?.selected_option) {
        return;
    }

    const options = Array.from(new Set([...groupOptions(group), group.selected_option]));
    group.options_text = options.join(', ');
    group.selected_option = '';
}

function removeGroupOption(index, optionToRemove) {
    const group = props.form.variant_groups[index];

    if (!group) {
        return;
    }

    group.options_text = groupOptions(group)
        .filter((option) => option !== optionToRemove)
        .join(', ');
}

function deleteTableItem(groupIndex, optionToDelete) {
    removeGroupOption(groupIndex, optionToDelete);
}

function syncVariantsFromGroups() {
    const existingVariants = Array.isArray(props.form.variants) ? props.form.variants : [];
    const existingMap = new Map(existingVariants.map((variant) => [variantKey(variant.attributes), variant]));
    const nextKeys = new Set(generatedVariants.value.map((attributes) => variantKey(attributes)));

    existingVariants.forEach((variant) => {
        if (!nextKeys.has(variantKey(variant.attributes))) {
            revokeVariantPreview(variant);
        }
    });

    props.form.variants = generatedVariants.value.map((attributes) => {
        const key = variantKey(attributes);
        const existing = existingMap.get(key);

        return {
            ...emptyVariantRow(),
            ...(existing ?? {}),
            label: buildVariantLabel(attributes),
            attributes: attributes.map((attribute) => ({
                name: attribute.name,
                value: attribute.value,
            })),
        };
    });
}

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}

function variantFieldError(index, field) {
    return fieldError(`variants.${index}.${field}`);
}

function buildVariantCombinations(groups, limit) {
    if (!groups.length) {
        return {
            items: [],
            truncated: false,
        };
    }

    const items = [];
    let truncated = false;

    function visit(groupIndex, current) {
        if (items.length >= limit) {
            truncated = true;

            return;
        }

        if (groupIndex >= groups.length) {
            items.push(current);

            return;
        }

        groups[groupIndex].options.forEach((option) => {
            if (items.length >= limit) {
                truncated = true;

                return;
            }

            visit(groupIndex + 1, [
                ...current,
                {
                    name: groups[groupIndex].name,
                    value: option,
                },
            ]);
        });
    }

    visit(0, []);

    return {
        items,
        truncated,
    };
}

function buildVariantLabel(attributes) {
    return attributes.map((attribute) => attribute.value).join(' / ');
}

function variantKey(attributes) {
    return (attributes ?? [])
        .map((attribute) => `${attribute.name}:${attribute.value}`)
        .join('|');
}

function emptyVariantRow() {
    return {
        label: '',
        attributes: [],
        variant_sku: '',
        price: '',
        stock: '',
        image: null,
        image_preview_url: '',
        existing_image_url: '',
        existing_image_path: '',
        remove_existing_image: false,
    };
}

function variantImageInputId(index) {
    return `variant-image-${index}`;
}

function handleVariantImageSelection(index, event) {
    const file = event.target?.files?.[0];
    const variant = props.form.variants[index];

    if (!file || !variant) {
        return;
    }

    revokeVariantPreview(variant);
    variant.image = file;
    variant.image_preview_url = URL.createObjectURL(file);
    variant.remove_existing_image = false;

    if (event.target) {
        event.target.value = '';
    }
}

function clearVariantImage(index) {
    const variant = props.form.variants[index];

    if (!variant) {
        return;
    }

    revokeVariantPreview(variant);
    variant.image = null;
    variant.image_preview_url = '';

    if (variant.existing_image_path) {
        variant.remove_existing_image = true;
        variant.existing_image_url = '';
    }
}

function revokeVariantPreview(variant) {
    if (variant?.image_preview_url) {
        URL.revokeObjectURL(variant.image_preview_url);
    }
}

function revokeAllVariantPreviewUrls() {
    (props.form.variants ?? []).forEach((variant) => {
        revokeVariantPreview(variant);
    });
}

function hasVariantImage(variant) {
    return Boolean(variant.image_preview_url || variant.existing_image_url);
}

function variantImageUrl(variant) {
    return variant.image_preview_url || variant.existing_image_url || '';
}
</script>
