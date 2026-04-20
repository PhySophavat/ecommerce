<template>
    <section class="rounded-[24px] border border-slate-200/80 bg-white p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Variants</p>
                <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Size and color</h4>
            </div>
            <button
                type="button"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                @click="addVariant"
            >
                + Add variant
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <article v-for="(variant, index) in form.variants" :key="`variant-${index}`" class="rounded-[24px] border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="chatgpt-kicker text-sm uppercase text-slate-400">Variant {{ index + 1 }}</p>
                    <button
                        type="button"
                        class="chatgpt-pill rounded-full border border-slate-200 px-3 py-1.5 text-xs uppercase text-slate-500 transition hover:border-rose-200 hover:text-rose-600"
                        @click="removeVariant(index)"
                    >
                        Remove
                    </button>
                </div>

                <div class="mt-4 grid gap-4 xl:grid-cols-4">
                    <label class="block">
                        <span class="chatgpt-label text-sm">Size</span>
                        <input
                            v-model="variant.size"
                            list="product-size-options"
                            type="text"
                            placeholder="M"
                            class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :class="variantFieldError(index, 'size') ? 'border-rose-300' : 'border-slate-200'"
                        />
                        <p v-if="variantFieldError(index, 'size')" class="mt-2 text-sm text-rose-600">{{ variantFieldError(index, 'size') }}</p>
                    </label>

                    <label class="block">
                        <span class="chatgpt-label text-sm">Color</span>
                        <input
                            v-model="variant.color"
                            list="product-color-options"
                            type="text"
                            placeholder="Black"
                            class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                            :class="variantFieldError(index, 'color') ? 'border-rose-300' : 'border-slate-200'"
                        />
                        <p v-if="variantFieldError(index, 'color')" class="mt-2 text-sm text-rose-600">{{ variantFieldError(index, 'color') }}</p>
                    </label>

                    <label class="block">
                        <span class="chatgpt-label text-sm">Variant Price</span>
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
                        <span class="chatgpt-label text-sm">Variant Stock</span>
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
            </article>
        </div>

        <datalist id="product-size-options">
            <option v-for="size in dashboard.form.sizes" :key="`size-${size}`" :value="size" />
        </datalist>

        <datalist id="product-color-options">
            <option v-for="color in dashboard.form.colors" :key="`color-${color}`" :value="color" />
        </datalist>
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

function addVariant() {
    props.form.variants.push({
        size: '',
        color: '',
        price: '',
        stock: '',
    });
}

function removeVariant(index) {
    props.form.variants.splice(index, 1);
}

function fieldError(field) {
    return props.errors[field]?.[0] ?? '';
}

function variantFieldError(index, field) {
    return fieldError(`variants.${index}.${field}`);
}
</script>
