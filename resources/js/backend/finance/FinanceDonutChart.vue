<template>
    <section class="min-w-0 rounded-[24px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)]">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-1.5 text-base font-bold tracking-[-0.03em] text-[#111827] sm:text-lg">{{ title }}</h3>
            </div>
            <span class="shrink-0 rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#6B7280]">{{ total }}</span>
        </div>

        <div class="mt-5 grid gap-4 xl:grid-cols-[148px_minmax(0,1fr)] xl:items-center">
            <div class="relative mx-auto flex h-[148px] w-[148px] items-center justify-center sm:h-[156px] sm:w-[156px]">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#F1F5F9" stroke-width="12" />
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
                <div class="pointer-events-none absolute text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Total</p>
                    <p class="mt-1 text-[1.2rem] font-black tracking-[-0.04em] text-[#111827] sm:text-[1.35rem]">{{ total }}</p>
                </div>
            </div>

            <div class="min-w-0 space-y-2.5">
                <div
                    v-for="segment in normalizedItems"
                    :key="segment.label"
                    class="flex items-center justify-between gap-3 rounded-[16px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }" />
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-[#111827]">{{ segment.label }}</p>
                            <p class="text-[11px] text-[#6B7280]">{{ segment.percent }}%</p>
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
        color: normalizedColor(item.label, item.color),
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

function normalizedColor(label, explicitColor) {
    if (explicitColor) {
        return explicitColor;
    }

    const key = String(label ?? '').trim().toLowerCase();

    return {
        success: '#10B981',
        successful: '#10B981',
        failed: '#E879A6',
        cancelled: '#CBD5E1',
        pending: '#F59E0B',
        aba: '#A25F88',
        acleda: '#C084A8',
        wing: '#E7C9DA',
        card: '#94A3B8',
        in: '#A25F88',
        out: '#F59E0B',
    }[key] ?? '#A25F88';
}
</script>
