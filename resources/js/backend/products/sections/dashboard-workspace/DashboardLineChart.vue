<template>
    <article class="rounded-[22px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-1 text-[1.7rem] font-bold tracking-[-0.04em] text-[#111827]">{{ title }}</h3>
            </div>
            <div class="rounded-full px-3 py-1 text-xs font-semibold" :class="trendBadgeClass">
                {{ trendLabel }}
            </div>
        </div>

        <div class="mt-2 flex items-center gap-2 text-sm text-[#64748B]">
            <span class="h-2.5 w-2.5 rounded-full" :class="trendDotClass"></span>
            <span>{{ trendDescription }}</span>
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="min-w-[720px]">
                <svg :viewBox="`0 0 ${viewWidth} 230`" class="h-[220px] w-full">
                    <defs>
                        <linearGradient id="dashboard-line-fill" x1="0%" x2="0%" y1="0%" y2="100%">
                            <stop offset="0%" :stop-color="fillColor" stop-opacity="0.24" />
                            <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.04" />
                        </linearGradient>
                    </defs>

                    <line
                        v-for="tick in yGridTicks"
                        :key="`tick-${tick}`"
                        x1="0"
                        :x2="viewWidth"
                        :y1="tick"
                        :y2="tick"
                        stroke="#E2E8F0"
                        stroke-dasharray="4 5"
                    />

                    <path :d="areaPath" fill="url(#dashboard-line-fill)" />
                    <path :d="trendPath" fill="none" stroke="#CBD5E1" stroke-width="2" stroke-dasharray="6 6" stroke-linecap="round" />
                    <path :d="linePath" fill="none" :stroke="lineColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                    <g v-for="point in plottedPoints" :key="point.label">
                        <circle
                            :cx="point.x"
                            :cy="point.y"
                            r="5"
                            :fill="lineColor"
                            stroke="#FFFFFF"
                            stroke-width="2.5"
                            @mouseenter="hoveredLabel = point.label"
                            @mouseleave="hoveredLabel = ''"
                        />
                        <g v-if="hoveredLabel === point.label">
                            <rect :x="point.x - 52" :y="point.y - 42" width="104" height="28" rx="12" fill="#0F172A" />
                            <text :x="point.x" :y="point.y - 24" text-anchor="middle" fill="#FFFFFF" font-size="11" font-weight="700">
                                {{ money(point.value) }}
                            </text>
                        </g>
                    </g>
                </svg>

                <div class="mt-3 grid gap-2 text-center text-[10px] font-semibold uppercase tracking-[0.1em] text-[#64748B]" :style="{ gridTemplateColumns: `repeat(${safePointCount}, minmax(0, 1fr))` }">
                    <span v-for="point in points" :key="point.label">{{ point.label }}</span>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Trend' },
    points: { type: Array, default: () => [] },
    trend: { type: Object, default: () => ({ direction: 'up', label: '+0.0%', delta: 'No change' }) },
    lineColor: { type: String, default: '' },
});

const hoveredLabel = ref('');
const chartHeight = 150;
const chartBottom = 180;
const chartPadding = 22;
const safePointCount = computed(() => Math.max(props.points.length, 1));
const step = computed(() => Math.max(46, Math.round(520 / safePointCount.value)));
const viewWidth = computed(() => Math.max(720, chartPadding * 2 + ((safePointCount.value - 1) * step.value)));
const maxValue = computed(() => Math.max(...props.points.map((point) => Number(point.value || 0)), 1));
const yGridTicks = computed(() => [24, 64, 104, 144]);
const lineColor = computed(() => props.lineColor || (props.trend?.direction === 'down' ? '#EF4444' : '#3B82F6'));
const fillColor = computed(() => props.lineColor || (props.trend?.direction === 'down' ? '#FCA5A5' : '#93C5FD'));
const trendLabel = computed(() => props.trend?.label ?? '+0.0%');
const trendDescription = computed(() => props.trend?.delta ?? 'No change');
const trendBadgeClass = computed(() => props.trend?.direction === 'down'
    ? 'bg-[#FEF2F2] text-[#DC2626]'
    : 'bg-[#ECFDF5] text-[#059669]');
const trendDotClass = computed(() => props.trend?.direction === 'down' ? 'bg-[#EF4444]' : 'bg-[#10B981]');

const plottedPoints = computed(() => props.points.map((point, index) => ({
    label: point.label,
    value: Number(point.value || 0),
    x: chartPadding + (index * step.value),
    y: chartBottom - ((Number(point.value || 0) / maxValue.value) * chartHeight),
})));

const linePath = computed(() => plottedPoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' '));
const trendPath = computed(() => {
    if (plottedPoints.value.length < 2) {
        return '';
    }

    const first = plottedPoints.value[0];
    const last = plottedPoints.value[plottedPoints.value.length - 1];

    return `M ${first.x} ${first.y} L ${last.x} ${last.y}`;
});
const areaPath = computed(() => {
    if (!plottedPoints.value.length) {
        return '';
    }

    const first = plottedPoints.value[0];
    const last = plottedPoints.value[plottedPoints.value.length - 1];

    return `${linePath.value} L ${last.x} ${chartBottom} L ${first.x} ${chartBottom} Z`;
});

function money(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}
</script>
