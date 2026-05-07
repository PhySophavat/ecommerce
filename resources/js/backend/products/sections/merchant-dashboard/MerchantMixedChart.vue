<template>
    <article class="h-full rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_12px_30px_rgba(17,24,39,0.045)] sm:p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">{{ title }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-[13px]">
                <span class="flex items-center gap-2 text-[#6B7280]"><span class="h-3 w-3 rounded-sm bg-[#E7C9DA]"></span>{{ barLabel }}</span>
                <span class="flex items-center gap-2 text-[#6B7280]"><span class="h-0.5 w-5 rounded-full bg-[#A25F88]"></span>{{ lineLabel }}</span>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <div class="min-w-[680px]">
                <div class="grid grid-cols-[52px_minmax(0,1fr)] gap-3">
                    <div class="flex h-[262px] flex-col justify-between pb-7 pr-1 text-right text-[10px] font-semibold uppercase tracking-[0.08em] text-[#9CA3AF]">
                        <span v-for="tick in yAxisTicks" :key="`tick-${tick}`">{{ formatTick(tick) }}</span>
                    </div>

                    <div class="relative h-[262px]">
                        <div
                            v-for="tick in yAxisTicks"
                            :key="`grid-${tick}`"
                        class="absolute inset-x-0 border-t border-dashed border-[#E5E7EB]"
                            :style="{ bottom: `${gridBottom(tick)}px` }"
                        ></div>

                        <svg :viewBox="`0 0 ${viewWidth} ${svgHeight}`" class="absolute inset-0 h-full w-full">
                            <path
                                :d="linePath"
                                fill="none"
                                stroke="#A25F88"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <g v-for="point in plottedPoints" :key="point.label">
                                <rect
                                    :x="point.x - barWidth / 2"
                                    :y="point.barY"
                                    :width="barWidth"
                                    :height="Math.max(svgHeight - axisBottom - point.barY, 8)"
                                    rx="10"
                                    fill="#E7C9DA"
                                />
                                <circle
                                    :cx="point.x"
                                    :cy="point.lineY"
                                    r="5"
                                    fill="#8E4F76"
                                    stroke="#FFFFFF"
                                    stroke-width="2"
                                />
                            </g>
                        </svg>

                        <div class="absolute inset-x-0 bottom-0 grid gap-3 border-t border-[#E5E7EB] pt-3" :style="{ gridTemplateColumns: `repeat(${safePointCount}, minmax(0, 1fr))` }">
                            <div v-for="point in points" :key="`${point.label}-label`" class="text-center">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-[#6B7280]">{{ point.label }}</p>
                                <p class="mt-1 text-[11px] text-[#9CA3AF]">{{ point.orders }} orders</p>
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
    eyebrow: { type: String, default: 'Analytics' },
    points: { type: Array, required: true },
    barLabel: { type: String, default: 'Sales' },
    lineLabel: { type: String, default: 'Orders' },
});

const safePointCount = computed(() => Math.max(props.points.length, 1));
const chartHeight = 188;
const svgHeight = 224;
const axisBottom = 28;
const sidePadding = 20;
const barWidth = 18;
const pointGap = computed(() => Math.max(66, Math.round(540 / safePointCount.value)));
const viewWidth = computed(() => (sidePadding * 2) + ((safePointCount.value - 1) * pointGap.value));
const maxSales = computed(() => Math.max(...props.points.map((point) => Number(point.sales || 0)), 1));
const maxOrders = computed(() => Math.max(...props.points.map((point) => Number(point.orders || 0)), 1));
const salesScaleMax = computed(() => roundedScale(maxSales.value));
const orderScaleMax = computed(() => roundedScale(maxOrders.value));
const yAxisTicks = computed(() => {
    const steps = 4;

    return Array.from({ length: steps + 1 }, (_, index) => Math.round((salesScaleMax.value / steps) * (steps - index)));
});
const plottedPoints = computed(() => props.points.map((point, index) => {
    const sales = Number(point.sales || 0);
    const orders = Number(point.orders || 0);
    const x = sidePadding + (index * pointGap.value);
    const barHeight = Math.max((sales / salesScaleMax.value) * chartHeight, sales > 0 ? 10 : 8);
    const orderHeight = (orders / orderScaleMax.value) * chartHeight;

    return {
        label: point.label,
        sales,
        orders,
        x,
        barY: svgHeight - axisBottom - barHeight,
        lineY: svgHeight - axisBottom - orderHeight,
    };
}));
const linePath = computed(() => plottedPoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.lineY}`).join(' '));

function roundedScale(value) {
    if (value <= 5) {
        return 5;
    }

    const magnitude = 10 ** Math.floor(Math.log10(value));
    const normalized = value / magnitude;
    const rounded = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;

    return rounded * magnitude;
}

function gridBottom(tick) {
    return axisBottom + ((tick / salesScaleMax.value) * chartHeight);
}

function formatTick(value) {
    return new Intl.NumberFormat('en-US', {
        notation: value >= 1000 ? 'compact' : 'standard',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>
