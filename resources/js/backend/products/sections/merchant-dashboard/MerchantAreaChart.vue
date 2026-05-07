<template>
    <article class="h-full rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_12px_30px_rgba(17,24,39,0.045)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">{{ title }}</h3>
            </div>
            <p class="max-w-xs text-right text-[11px] leading-5 text-[#6B7280]">Based on payment date, excluding cancelled and failed orders.</p>
        </div>

        <div class="mt-4 overflow-x-auto">
            <div class="min-w-[520px]">
                <svg :viewBox="`0 0 ${viewWidth} 188`" class="h-[170px] w-full">
                    <defs>
                        <linearGradient id="merchant-area-fill" x1="0%" x2="0%" y1="0%" y2="100%">
                            <stop offset="0%" stop-color="#E7C9DA" stop-opacity="0.78" />
                            <stop offset="100%" stop-color="#F6EAF1" stop-opacity="0.12" />
                        </linearGradient>
                    </defs>
                    <line v-for="tick in yGridTicks" :key="`tick-${tick}`" x1="0" :x2="viewWidth" :y1="tick" :y2="tick" stroke="#E5E7EB" stroke-dasharray="4 5" />
                    <path :d="areaPath" fill="url(#merchant-area-fill)" />
                    <path :d="linePath" fill="none" stroke="#A25F88" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    <circle
                        v-for="point in plottedPoints"
                        :key="point.label"
                        :cx="point.x"
                        :cy="point.y"
                        r="3.25"
                        fill="#8E4F76"
                        stroke="#FFFFFF"
                        stroke-width="2"
                    />
                </svg>
                <div class="mt-3 grid gap-2 text-center text-[10px] font-semibold uppercase tracking-[0.1em] text-[#6B7280]" :style="{ gridTemplateColumns: `repeat(${safePointCount}, minmax(0, 1fr))` }">
                    <span v-for="point in points" :key="point.label">{{ point.label }}</span>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Sales trend' },
    points: { type: Array, required: true },
});

const chartHeight = 140;
const chartBottom = 156;
const chartPadding = 20;
const safePointCount = computed(() => Math.max(props.points.length, 1));
const step = computed(() => Math.max(52, Math.round(420 / safePointCount.value)));
const viewWidth = computed(() => Math.max(520, chartPadding * 2 + ((safePointCount.value - 1) * step.value)));
const maxValue = computed(() => Math.max(...props.points.map((point) => Number(point.sales || 0)), 1));
const yGridTicks = computed(() => [18, 58, 98, 138]);

const plottedPoints = computed(() => props.points.map((point, index) => ({
    label: point.label,
    x: chartPadding + (index * step.value),
    y: chartBottom - ((Number(point.sales || 0) / maxValue.value) * chartHeight),
})));

const linePath = computed(() => plottedPoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' '));
const areaPath = computed(() => {
    if (!plottedPoints.value.length) {
        return '';
    }

    const first = plottedPoints.value[0];
    const last = plottedPoints.value[plottedPoints.value.length - 1];

    return `${linePath.value} L ${last.x} ${chartBottom} L ${first.x} ${chartBottom} Z`;
});
</script>
