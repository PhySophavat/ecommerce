<template>
    <section class="min-w-0 rounded-[24px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">Verification analytics</p>
                <h3 class="mt-1.5 text-base font-bold tracking-[-0.03em] text-[#111827] sm:text-lg">{{ title }}</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#6B7280]">{{ subtitle }}</p>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2 text-[#111827]">
                    <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                    <span>Success</span>
                </div>
                <div class="flex items-center gap-2 text-[#111827]">
                    <span class="h-3 w-3 rounded-full bg-[#C084A8]"></span>
                    <span>Failed</span>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <div class="relative">
                <svg viewBox="0 0 760 280" class="h-[280px] w-full overflow-visible">
                    <defs>
                        <linearGradient id="payment-success-fill" x1="0%" x2="0%" y1="0%" y2="100%">
                            <stop offset="0%" stop-color="#22C55E" stop-opacity="0.22" />
                            <stop offset="100%" stop-color="#22C55E" stop-opacity="0.03" />
                        </linearGradient>
                        <linearGradient id="payment-failed-fill" x1="0%" x2="0%" y1="0%" y2="100%">
                            <stop offset="0%" stop-color="#C084A8" stop-opacity="0.24" />
                            <stop offset="100%" stop-color="#C084A8" stop-opacity="0.04" />
                        </linearGradient>
                    </defs>

                    <g>
                        <line
                            v-for="line in yGrid"
                            :key="`grid-${line.value}`"
                            :x1="padding.left"
                            :x2="chartWidth + padding.left"
                            :y1="line.y"
                            :y2="line.y"
                            stroke="#E5E7EB"
                            stroke-dasharray="5 6"
                        />
                        <text
                            v-for="line in yGrid"
                            :key="`label-${line.value}`"
                            :x="padding.left - 12"
                            :y="line.y + 4"
                            text-anchor="end"
                            class="fill-[#6B7280] text-[11px] font-medium"
                        >
                            {{ line.value }}
                        </text>
                    </g>

                    <path :d="failedAreaPath" fill="url(#payment-failed-fill)" />
                    <path :d="successAreaPath" fill="url(#payment-success-fill)" />

                    <path :d="failedLinePath" fill="none" stroke="#C084A8" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                    <path :d="totalLinePath" fill="none" stroke="#22C55E" stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" />

                    <g>
                        <circle
                            v-for="point in plottedPoints"
                            :key="`failed-point-${point.label}`"
                            :cx="point.x"
                            :cy="point.failedY"
                            r="4"
                            fill="#C084A8"
                            stroke="#FFFFFF"
                            stroke-width="2"
                        />
                        <circle
                            v-for="point in plottedPoints"
                            :key="`success-point-${point.label}`"
                            :cx="point.x"
                            :cy="point.totalY"
                            r="4.5"
                            fill="#22C55E"
                            stroke="#FFFFFF"
                            stroke-width="2"
                        />
                    </g>

                    <g>
                        <rect
                            v-for="(point, index) in plottedPoints"
                            :key="`hover-${point.label}`"
                            :x="hoverBandX(index)"
                            :y="padding.top"
                            :width="hoverBandWidth(index)"
                            :height="chartHeight"
                            fill="transparent"
                            @mouseenter="activeIndex = index"
                            @mousemove="activeIndex = index"
                            @mouseleave="activeIndex = null"
                        />
                    </g>

                    <g>
                        <line
                            v-if="activePoint"
                            :x1="activePoint.x"
                            :x2="activePoint.x"
                            :y1="padding.top"
                            :y2="chartHeight + padding.top"
                            stroke="#A25F88"
                            stroke-dasharray="4 5"
                        />
                    </g>

                    <g>
                        <text
                            v-for="point in plottedPoints"
                            :key="`x-${point.label}`"
                            :x="point.x"
                            :y="chartHeight + padding.top + 26"
                            text-anchor="middle"
                            class="fill-[#6B7280] text-[12px] font-semibold"
                        >
                            {{ point.label }}
                        </text>
                    </g>
                </svg>

                <div
                    v-if="activePoint"
                    class="pointer-events-none absolute z-10 min-w-[180px] rounded-2xl border border-[#E5E7EB] bg-white px-4 py-3 shadow-[0_16px_30px_rgba(17,24,39,0.12)]"
                    :style="tooltipStyle"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ activePoint.label }}</p>
                    <div class="mt-3 space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-[#111827]">Successful payments</span>
                            <span class="font-bold text-emerald-600">{{ activePoint.success }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-[#111827]">Failed payments</span>
                            <span class="font-bold text-rose-700">{{ activePoint.failed }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Payment Verification Trend',
    },
    subtitle: {
        type: String,
        default: 'Track successful and failed QR screenshot payment verification.',
    },
    points: {
        type: Array,
        default: () => [],
    },
});

const activeIndex = ref(null);
const padding = {
    top: 12,
    right: 18,
    bottom: 42,
    left: 40,
};
const svgWidth = 760;
const svgHeight = 280;
const chartWidth = svgWidth - padding.left - padding.right;
const chartHeight = svgHeight - padding.top - padding.bottom;

const normalizedPoints = computed(() => {
    const source = props.points?.length
        ? props.points
        : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((label) => ({
            label,
            success: 0,
            failed: 0,
        }));

    return source.map((item) => {
        const success = Number(item.success || 0);
        const failed = Number(item.failed || 0);

        return {
            label: item.label,
            success,
            failed,
            total: success + failed,
        };
    });
});

const maxValue = computed(() => {
    const max = Math.max(...normalizedPoints.value.map((item) => item.total), 0);
    return Math.max(max, 4);
});

const plottedPoints = computed(() => {
    if (normalizedPoints.value.length === 0) {
        return [];
    }

    const stepX = normalizedPoints.value.length === 1 ? 0 : chartWidth / (normalizedPoints.value.length - 1);

    return normalizedPoints.value.map((item, index) => {
        const x = padding.left + (stepX * index);
        const failedY = valueToY(item.failed);
        const totalY = valueToY(item.total);

        return {
            ...item,
            x,
            failedY,
            totalY,
        };
    });
});

const yGrid = computed(() => {
    const steps = 4;
    const increment = Math.ceil(maxValue.value / steps);

    return Array.from({ length: steps + 1 }, (_, index) => {
        const value = increment * (steps - index);

        return {
            value,
            y: valueToY(value),
        };
    });
});

const failedLinePath = computed(() => smoothPath(plottedPoints.value.map((point) => ({ x: point.x, y: point.failedY }))));
const totalLinePath = computed(() => smoothPath(plottedPoints.value.map((point) => ({ x: point.x, y: point.totalY }))));
const failedAreaPath = computed(() => areaPath(plottedPoints.value.map((point) => ({ x: point.x, y: point.failedY })), chartHeight + padding.top));
const successAreaPath = computed(() => stackedAreaPath(plottedPoints.value));
const activePoint = computed(() => activeIndex.value === null ? null : plottedPoints.value[activeIndex.value] ?? null);

const tooltipStyle = computed(() => {
    if (!activePoint.value) {
        return {};
    }

    const leftPercent = ((activePoint.value.x - padding.left) / Math.max(chartWidth, 1)) * 100;
    const clamped = Math.min(Math.max(leftPercent, 10), 74);

    return {
        left: `${clamped}%`,
        top: '16px',
    };
});

function valueToY(value) {
    const ratio = maxValue.value <= 0 ? 0 : value / maxValue.value;
    return padding.top + chartHeight - (ratio * chartHeight);
}

function smoothPath(points) {
    if (points.length === 0) {
        return '';
    }

    if (points.length === 1) {
        return `M ${points[0].x} ${points[0].y}`;
    }

    let path = `M ${points[0].x} ${points[0].y}`;

    for (let index = 0; index < points.length - 1; index += 1) {
        const current = points[index];
        const next = points[index + 1];
        const controlX = (current.x + next.x) / 2;

        path += ` C ${controlX} ${current.y}, ${controlX} ${next.y}, ${next.x} ${next.y}`;
    }

    return path;
}

function areaPath(points, baselineY) {
    if (points.length === 0) {
        return '';
    }

    const line = smoothPath(points);
    const last = points[points.length - 1];
    const first = points[0];

    return `${line} L ${last.x} ${baselineY} L ${first.x} ${baselineY} Z`;
}

function stackedAreaPath(points) {
    if (points.length === 0) {
        return '';
    }

    const topPoints = points.map((point) => ({ x: point.x, y: point.totalY }));
    const bottomPoints = [...points]
        .reverse()
        .map((point) => ({ x: point.x, y: point.failedY }));

    return `${smoothPath(topPoints)} L ${bottomPoints[0].x} ${bottomPoints[0].y} ${bottomPoints.slice(1).map((point) => `L ${point.x} ${point.y}`).join(' ')} Z`;
}

function hoverBandWidth(index) {
    if (plottedPoints.value.length <= 1) {
        return chartWidth;
    }

    if (index === plottedPoints.value.length - 1) {
        return (plottedPoints.value[index].x - plottedPoints.value[index - 1].x) / 2;
    }

    if (index === 0) {
        return (plottedPoints.value[index + 1].x - plottedPoints.value[index].x) / 2;
    }

    return (plottedPoints.value[index + 1].x - plottedPoints.value[index - 1].x) / 2;
}

function hoverBandX(index) {
    if (plottedPoints.value.length <= 1) {
        return padding.left;
    }

    if (index === 0) {
        return padding.left;
    }

    return plottedPoints.value[index].x - ((plottedPoints.value[index].x - plottedPoints.value[index - 1].x) / 2);
}
</script>
