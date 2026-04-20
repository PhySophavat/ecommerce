<template>
    <section class="rounded-[24px] border border-slate-200/80 bg-white p-5 sm:p-6">
        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Pricing</p>
        <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Price</h4>
        <div class="mt-5 grid gap-5">
            <label class="block">
                <span class="chatgpt-label text-sm">Price</span>
                <input
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="79.80"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('price') ? 'border-rose-300' : 'border-slate-200'"
                />
                <p v-if="fieldError('price')" class="mt-2 text-sm text-rose-600">{{ fieldError('price') }}</p>
            </label>

            <label class="block">
                <span class="chatgpt-label text-sm">Discount Price</span>
                <input
                    v-model="form.discount_price"
                    type="number"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="59.00"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('discount_price') ? 'border-rose-300' : 'border-slate-200'"
                />
                <p v-if="fieldError('discount_price')" class="mt-2 text-sm text-rose-600">{{ fieldError('discount_price') }}</p>
            </label>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
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
