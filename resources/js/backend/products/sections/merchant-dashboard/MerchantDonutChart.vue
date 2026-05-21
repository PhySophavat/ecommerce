<template>
    <article class="h-full rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">{{ eyebrow }}</p>
                <h3 class="mt-2 text-lg font-bold tracking-[-0.03em] text-[#111827]">{{ title }}</h3>
            </div>
            <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[11px] font-semibold text-[#6B7280]">{{ totalLabel }}</span>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-[140px_minmax(0,1fr)] lg:items-center">
            <div class="relative mx-auto h-[136px] w-[136px] sm:h-[144px] sm:w-[144px]">
                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#EEF2F7" stroke-width="12" />
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
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Total</p>
                    <p class="mt-1 text-[1.1rem] font-black tracking-[-0.04em] text-[#111827]">{{ totalLabel }}</p>
                </div>
            </div>

            <div class="space-y-2.5">
                <div v-for="item in normalizedItems" :key="item.label" class="rounded-[16px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }"></span>
                            <p class="text-[13px] font-semibold text-[#111827]">{{ item.label }}</p>
                        </div>
                        <p class="text-[13px] font-bold text-[#111827]">{{ item.displayValue }}</p>
                    </div>
                    <p class="mt-1 text-[11px] text-[#6B7280]">{{ item.percent }}%</p>
                </div>
            </div>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: 'Chart' },
    items: { type: Array, required: true },
    valueType: { type: String, default: 'number' },
});

const totalValue = computed(() => props.items.reduce((sum, item) => sum + Number(item.value || 0), 0));
const circumference = 2 * Math.PI * 42;

const normalizedItems = computed(() => props.items.map((item) => {
    const numeric = Number(item.value || 0);
    const percent = totalValue.value > 0 ? Math.round((numeric / totalValue.value) * 100) : 0;

    return {
        ...item,
        color: normalizedColor(item.label),
        numeric,
        percent,
        displayValue: props.valueType === 'currency'
            ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numeric)
            : String(numeric),
    };
}));

const segments = computed(() => {
    let offset = 0;

    return normalizedItems.value.map((item) => {
        const ratio = totalValue.value > 0 ? item.numeric / totalValue.value : 0;
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

const totalLabel = computed(() => props.valueType === 'currency'
    ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(totalValue.value)
    : String(totalValue.value));

function normalizedColor(label) {
    const key = String(label ?? '').trim().toLowerCase();

    return {
        completed: '#10B981',
        success: '#10B981',
        successful: '#10B981',
        cancelled: '#9CA3AF',
        canceled: '#9CA3AF',
        refunded: '#3B82F6',
        failed: '#EF4444',
        aba: '#A25F88',
        acleda: '#8E4F76',
        wing: '#E7C9DA',
        cash: '#F59E0B',
        card: '#3B82F6',
        in: '#A25F88',
        out: '#F59E0B',
    }[key] ?? '#A25F88';
}
</script>
