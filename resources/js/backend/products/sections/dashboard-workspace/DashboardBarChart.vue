<template>
    <article class="rounded-[28px] border border-[#E5E7EB] bg-white p-5 shadow-[0_16px_40px_rgba(15,23,42,0.05)] sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-xl font-black tracking-[-0.04em] text-[#0F172A]">{{ title }}</h3>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    class="rounded-full px-3.5 py-2 text-xs font-semibold transition"
                    :class="activeFilter === filter.value ? 'bg-[#4F46E5] text-white shadow-[0_12px_24px_rgba(79,70,229,0.18)]' : 'bg-[#F8FAFC] text-[#64748B] hover:bg-[#EEF2FF] hover:text-[#4338CA]'"
                    @click="$emit('change-filter', filter.value)"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="min-w-[720px]">
                <div class="grid grid-cols-[56px_minmax(0,1fr)] gap-3">
                    <div class="flex h-[260px] flex-col justify-between pb-8 text-right text-[10px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
                        <span v-for="tick in yAxisTicks" :key="`tick-${tick}`">{{ money(tick) }}</span>
                    </div>

                    <div class="relative h-[260px]">
                        <div
                            v-for="tick in yAxisTicks"
                            :key="`grid-${tick}`"
                            class="absolute inset-x-0 border-t border-dashed border-[#E2E8F0]"
                            :style="{ bottom: `${gridBottom(tick)}px` }"
                        ></div>

                        <div class="absolute inset-0 flex items-end gap-3 px-1 pb-8">
                            <div v-for="point in normalizedPoints" :key="point.label" class="flex min-w-0 flex-1 flex-col items-center justify-end gap-3">
                                <div class="relative flex h-[208px] w-full items-end justify-center">
                                    <button
                                        type="button"
                                        class="group relative flex w-full items-end justify-center rounded-[18px] bg-[linear-gradient(180deg,#EEF2FF_0%,#DBEAFE_100%)] px-2 pb-0 outline-none"
                                        @mouseenter="hoveredLabel = point.label"
                                        @mouseleave="hoveredLabel = ''"
                                        @focus="hoveredLabel = point.label"
                                        @blur="hoveredLabel = ''"
                                    >
                                        <span
                                            class="w-full rounded-[16px] bg-[linear-gradient(180deg,#6366F1_0%,#3B82F6_100%)] shadow-[0_14px_30px_rgba(79,70,229,0.22)] transition-all duration-200 group-hover:brightness-105"
                                            :style="{ height: `${point.height}%` }"
                                        ></span>

                                        <span
                                            v-if="hoveredLabel === point.label"
                                            class="absolute -top-12 rounded-2xl bg-[#0F172A] px-3 py-2 text-xs font-semibold text-white shadow-[0_18px_36px_rgba(15,23,42,0.25)]"
                                        >
                                            {{ money(point.value) }} · {{ point.orders }} orders
                                        </span>
                                    </button>
                                </div>

                                <div class="text-center">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-[#64748B]">{{ point.label }}</p>
                                    <p class="mt-1 text-[11px] text-[#94A3B8]">{{ point.orders }} orders</p>
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
import { computed, ref } from 'vue';

defineEmits(['change-filter']);

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Performance' },
    points: { type: Array, default: () => [] },
    filters: { type: Array, default: () => [] },
    activeFilter: { type: String, default: '30days' },
});

const hoveredLabel = ref('');
const maxValue = computed(() => Math.max(...props.points.map((point) => Number(point.value || 0)), 1));
const scaleMax = computed(() => roundedScale(maxValue.value));
const yAxisTicks = computed(() => {
    const steps = 4;
    return Array.from({ length: steps + 1 }, (_, index) => Math.round((scaleMax.value / steps) * (steps - index)));
});
const normalizedPoints = computed(() => props.points.map((point) => ({
    ...point,
    value: Number(point.value || 0),
    orders: Number(point.orders || 0),
    height: Math.max(10, Math.round((Number(point.value || 0) / scaleMax.value) * 100)),
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
    return 32 + ((tick / scaleMax.value) * 208);
}

function money(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}
</script>
