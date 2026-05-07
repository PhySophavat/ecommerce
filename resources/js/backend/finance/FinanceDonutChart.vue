<template>
    <section class="rounded-[28px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-xl font-bold tracking-[-0.03em] text-[#111827]">{{ title }}</h3>
            </div>
            <span class="rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#6B7280]">{{ total }}</span>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[220px_1fr] lg:items-center">
            <div class="relative mx-auto flex h-[220px] w-[220px] items-center justify-center">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#F3F4F6" stroke-width="14" />
                    <circle
                        v-for="segment in segments"
                        :key="segment.label"
                        cx="60"
                        cy="60"
                        r="42"
                        fill="none"
                        :stroke="segment.color"
                        stroke-width="14"
                        stroke-linecap="round"
                        :stroke-dasharray="segment.dasharray"
                        :stroke-dashoffset="segment.dashoffset"
                    />
                </svg>
                <div class="pointer-events-none absolute text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#6B7280]">Total</p>
                    <p class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">{{ total }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div
                    v-for="segment in normalizedItems"
                    :key="segment.label"
                    class="flex items-center justify-between rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC] px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: segment.color }" />
                        <div>
                            <p class="text-sm font-semibold text-[#111827]">{{ segment.label }}</p>
                            <p class="text-xs text-[#6B7280]">{{ segment.percent }}%</p>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-[#111827]">{{ segment.valueLabel }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    eyebrow: {
        type: String,
        default: 'Distribution',
    },
    title: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    format: {
        type: String,
        default: 'number',
    },
});

const totalValue = computed(() => props.items.reduce((sum, item) => sum + Number(item.value || 0), 0));
const circumference = 2 * Math.PI * 42;

const normalizedItems = computed(() => props.items.map((item) => {
    const value = Number(item.value || 0);
    const percent = totalValue.value <= 0 ? 0 : Math.round((value / totalValue.value) * 100);

    return {
        ...item,
        numericValue: value,
        percent,
        valueLabel: props.format === 'currency' ? currency(value) : String(value),
    };
}));

const segments = computed(() => {
    let offset = 0;

    return normalizedItems.value.map((item) => {
        const ratio = totalValue.value <= 0 ? 0 : item.numericValue / totalValue.value;
        const dashLength = ratio * circumference;
        const segment = {
            label: item.label,
            color: item.color,
            dasharray: `${dashLength} ${circumference - dashLength}`,
            dashoffset: -offset,
        };

        offset += dashLength;

        return segment;
    });
});

const total = computed(() => props.format === 'currency' ? currency(totalValue.value) : String(totalValue.value));

function currency(value) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
}
</script>
