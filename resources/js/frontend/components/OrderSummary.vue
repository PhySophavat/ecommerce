<template>
    <section class="rounded-[30px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">Order summary</p>
        <h3 class="mt-1 text-xl font-black tracking-[-0.03em] text-[#111827]">{{ title }}</h3>

        <div class="mt-6 space-y-4 text-sm text-[#6B7280]">
            <div class="flex items-center justify-between">
                <span>Subtotal</span>
                <span class="font-semibold text-[#111827]">{{ lines.subtotal }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span>Shipping</span>
                <span class="font-semibold text-[#111827]">{{ lines.shipping }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span>Tax</span>
                <span class="font-semibold text-[#111827]">{{ lines.tax }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-[#E5E7EB] pt-4 text-base">
                <span class="font-semibold text-[#111827]">Total</span>
                <span class="text-2xl font-black tracking-[-0.03em] text-[#111827]">{{ lines.total }}</span>
            </div>
        </div>

        <div v-if="$slots.default" class="mt-6">
            <slot />
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Your total',
    },
    lines: {
        type: Object,
        required: true,
    },
});
</script>
