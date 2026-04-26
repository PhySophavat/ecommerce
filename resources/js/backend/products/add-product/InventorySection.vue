<template>
    <section class="admin-form-section rounded-[24px] p-5 sm:p-6">
        <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Inventory</p>
        <h4 class="chatgpt-title mt-2 text-lg text-slate-950">Stock</h4>
        <div class="mt-5 grid gap-5">
            <label class="block">
                <span class="chatgpt-label text-sm">Stock Quantity</span>
                <input
                    v-model="form.stock_quantity"
                    type="number"
                    min="0"
                    step="1"
                    inputmode="numeric"
                    placeholder="58"
                    class="mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('stock_quantity') ? 'border-rose-300' : 'border-slate-200'"
                />
                <p v-if="fieldError('stock_quantity')" class="mt-2 text-sm text-rose-600">{{ fieldError('stock_quantity') }}</p>
            </label>

            <label class="block">
                <span class="chatgpt-label text-sm">SKU</span>
                <input
                    v-model="form.sku"
                    type="text"
                    placeholder="SPD-PR-011"
                    class="chatgpt-mono mt-3 w-full rounded-2xl border bg-white px-4 py-3 text-sm uppercase tracking-[0.08em] text-slate-900 outline-none transition focus:border-[#3457ff] focus:ring-4 focus:ring-[#3457ff]/10"
                    :class="fieldError('sku') ? 'border-rose-300' : 'border-slate-200'"
                />
                <p v-if="fieldError('sku')" class="mt-2 text-sm text-rose-600">{{ fieldError('sku') }}</p>
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
