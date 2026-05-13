<template>
    <article class="rounded-[22px] border border-[#9DB7F5] bg-white p-5 shadow-[0_12px_28px_rgba(15,23,42,0.04)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#64748B]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">{{ title }}</h3>
            </div>
            <div class="flex items-center gap-2 text-xs font-semibold text-[#64748B]">
                <span class="h-3 w-3 rounded-sm" :style="{ background: barColor }"></span>
                <span>{{ legendLabel }}</span>
            </div>
        </div>

        <div v-if="normalizedItems.length === 0" class="mt-5 flex h-[170px] items-center justify-center rounded-[18px] border border-dashed border-[#CBD5E1] bg-[#F8FAFC] text-sm text-[#94A3B8]">
            No data for this period.
        </div>

        <div v-else class="mt-5 overflow-x-auto">
            <div class="min-w-[420px]">
                <div class="grid grid-cols-[44px_minmax(0,1fr)] gap-3">
                    <div class="flex h-[170px] flex-col justify-between pb-8 text-right text-[10px] font-semibold text-[#94A3B8]">
                        <span v-for="tick in yAxisTicks" :key="`tick-${tick}`">{{ formatTick(tick) }}</span>
                    </div>

                    <div class="relative h-[170px]">
                        <div
                            v-for="tick in yAxisTicks"
                            :key="`grid-${tick}`"
                            class="absolute inset-x-0 border-t border-[#E2E8F0]"
                            :style="{ bottom: `${gridBottom(tick)}px` }"
                        ></div>

                        <div class="absolute inset-0 flex items-end gap-3 pb-8">
                            <div v-for="item in normalizedItems" :key="item.label" class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                <div class="flex h-[128px] w-full items-end justify-center">
                                    <div
                                        class="w-full max-w-[52px] rounded-t-[10px] transition-all duration-300 hover:brightness-105"
                                        :style="{ height: `${item.height}%`, background: barColor }"
                                        :title="`${item.label}: ${formatTick(item.value)}`"
                                    ></div>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] font-semibold text-[#475569]" :title="item.label">{{ shortLabel(item.label) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Breakdown' },
    items: { type: Array, default: () => [] },
    legendLabel: { type: String, default: 'Visits' },
    barColor: { type: String, default: '#27B5D0' },
});

const nonZeroItems = computed(() => props.items.filter((item) => Number(item.value || 0) > 0));
const maxValue = computed(() => Math.max(...nonZeroItems.value.map((item) => Number(item.value || 0)), 1));
const scaleMax = computed(() => roundedScale(maxValue.value));
const yAxisTicks = computed(() => {
    const steps = 3;
    return Array.from({ length: steps + 1 }, (_, index) => Math.round((scaleMax.value / steps) * (steps - index)));
});
const normalizedItems = computed(() => nonZeroItems.value.map((item) => ({
    ...item,
    value: Number(item.value || 0),
    height: Math.max(3, Math.round((Number(item.value || 0) / scaleMax.value) * 100)),
})));

function roundedScale(value) {
    if (value <= 10) {
        return 10;
    }

    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;
    const rounded = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;

    return rounded * magnitude;
}

function gridBottom(tick) {
    return 32 + ((tick / scaleMax.value) * 128);
}

function formatTick(value) {
    return new Intl.NumberFormat('en-US', {
        notation: value >= 1000 ? 'compact' : 'standard',
        maximumFractionDigits: 0,
    }).format(value);
}

function shortLabel(label) {
    const value = String(label ?? '').trim();

    if (value.length <= 14) {
        return value;
    }

    return `${value.slice(0, 12)}...`;
}
</script>
