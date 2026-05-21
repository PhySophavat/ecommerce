<template>
    <article class="rounded-[22px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-1 text-[1.75rem] font-bold tracking-[-0.04em] text-[#111827]">{{ title }}</h3>
            </div>
            <span class="rounded-full border border-[#EDF2F7] bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#64748B]">{{ total }}</span>
        </div>

        <div v-if="normalizedItems.length === 0" class="mt-6 flex h-[220px] items-center justify-center rounded-[18px] border border-dashed border-[#CBD5E1] bg-[#F8FAFC] text-sm text-[#94A3B8]">
            No data for this period.
        </div>

        <div v-else class="mt-6 grid gap-5 lg:grid-cols-[152px_minmax(0,1fr)] lg:items-center">
            <div class="relative mx-auto h-[152px] w-[152px]">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#EDF2F7" stroke-width="12" />
                    <circle
                        v-for="segment in segments"
                        :key="segment.label"
                        cx="60"
                        cy="60"
                        r="42"
                        fill="none"
                        :stroke="segment.color"
                        stroke-width="12"
                        stroke-linecap="round"
                        :stroke-dasharray="segment.dasharray"
                        :stroke-dashoffset="segment.dashoffset"
                    />
                </svg>

                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">Total</p>
                    <p class="mt-1 text-[1.7rem] font-black tracking-[-0.05em] text-[#111827]">{{ total }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div v-for="item in normalizedItems" :key="item.label" class="rounded-[18px] border border-[#E5E7EB] bg-[#FBFCFE] px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full shadow-[0_0_0_4px_rgba(248,250,252,1)]" :style="{ backgroundColor: item.color }"></span>
                            <span class="text-sm font-semibold text-[#111827]">{{ item.label }}</span>
                        </div>
                        <span class="text-sm font-bold text-[#111827]">{{ item.value }}</span>
                    </div>
                    <p class="mt-1 text-[11px] uppercase tracking-[0.14em] text-[#94A3B8]">{{ item.percent }}%</p>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Overview' },
    items: { type: Array, default: () => [] },
});

const filteredItems = computed(() => props.items.filter((item) => Number(item.value || 0) > 0));
const totalValue = computed(() => filteredItems.value.reduce((sum, item) => sum + Number(item.value || 0), 0));
const circumference = 2 * Math.PI * 42;

const normalizedItems = computed(() => filteredItems.value.map((item) => {
    const numeric = Number(item.value || 0);

    return {
        ...item,
        value: numeric,
        percent: totalValue.value > 0 ? Math.round((numeric / totalValue.value) * 100) : 0,
    };
}));

const segments = computed(() => {
    let offset = 0;

    return normalizedItems.value.map((item) => {
        const ratio = totalValue.value > 0 ? item.value / totalValue.value : 0;
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

const total = computed(() => String(totalValue.value));
</script>
