<template>
    <section class="min-w-0 rounded-[24px] border border-[#E5E7EB] bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-1.5 text-base font-bold tracking-[-0.03em] text-[#111827] sm:text-lg">{{ title }}</h3>
            </div>
            <span class="shrink-0 rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#6B7280]">{{ total }}</span>
        </div>

        <div class="mt-5 grid gap-4 2xl:grid-cols-[180px_minmax(0,1fr)] 2xl:items-center">
            <div class="relative mx-auto flex h-[170px] w-[170px] items-center justify-center sm:h-[190px] sm:w-[190px]">
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
                    <p class="mt-1.5 text-2xl font-black tracking-[-0.05em] text-[#111827] sm:text-[2rem]">{{ total }}</p>
                </div>
            </div>

            <div class="min-w-0 space-y-2.5">
                <div
                    v-for="segment in normalizedItems"
                    :key="segment.label"
                    class="flex items-center justify-between gap-3 rounded-[18px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-2.5"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }" />
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-[#111827]">{{ segment.label }}</p>
                            <p class="text-xs text-[#6B7280]">{{ segment.percent }}%</p>
                        </div>
                    </div>
                    <p class="shrink-0 text-[13px] font-bold text-[#111827]">{{ segment.valueLabel }}</p>
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
