<template>
    <article
        class="h-full rounded-[22px] border bg-white p-4 shadow-[0_12px_28px_rgba(17,24,39,0.05)] transition hover:-translate-y-0.5"
        :class="surfaceClass"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em]" :class="labelClass">{{ card.label }}</p>
                <p class="mt-3 text-[1.5rem] font-black leading-none tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[16px]" :class="badgeClass">
                <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath" />
                </svg>
            </span>
        </div>
        <p class="mt-3 text-[13px] leading-5 text-[#6B7280]">{{ card.description }}</p>
    </article>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    card: {
        type: Object,
        required: true,
    },
});

const surfaceClass = computed(() => ({
    primary: 'border-[#E7D4E1] bg-[linear-gradient(180deg,#FFF8FC_0%,#FFFFFF_100%)]',
    slate: 'border-[#E5E7EB] bg-[linear-gradient(180deg,#FCFCFD_0%,#FFFFFF_100%)]',
    success: 'border-[#D5EDE1] bg-[linear-gradient(180deg,#F7FFFB_0%,#FFFFFF_100%)]',
    warning: 'border-[#F4E4C9] bg-[linear-gradient(180deg,#FFFDF7_0%,#FFFFFF_100%)]',
    danger: 'border-[#F3D7D7] bg-[linear-gradient(180deg,#FFF8F8_0%,#FFFFFF_100%)]',
    info: 'border-[#D8E8F7] bg-[linear-gradient(180deg,#F7FBFF_0%,#FFFFFF_100%)]',
}[props.card.tone] ?? 'border-[#E5E7EB]'));

const labelClass = computed(() => ({
    primary: 'text-[#A25F88]',
    slate: 'text-[#6B7280]',
    success: 'text-[#0F9F6E]',
    warning: 'text-[#C2851A]',
    danger: 'text-[#C14D5D]',
    info: 'text-[#2B78C6]',
}[props.card.tone] ?? 'text-[#6B7280]'));

const badgeClass = computed(() => ({
    primary: 'bg-[#F6EBF1] text-[#A25F88]',
    slate: 'bg-[#F3F4F6] text-[#6B7280]',
    success: 'bg-[#EAF8F1] text-[#0F9F6E]',
    warning: 'bg-[#FFF2DE] text-[#C2851A]',
    danger: 'bg-[#FDECEC] text-[#C14D5D]',
    info: 'bg-[#EAF4FD] text-[#2B78C6]',
}[props.card.tone] ?? 'bg-[#F3F4F6] text-[#6B7280]'));

const iconPath = computed(() => ({
    wallet: 'M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5V9h-3a3 3 0 0 0 0 6h3v1.5A2.5 2.5 0 0 1 16.5 19h-11A2.5 2.5 0 0 1 3 16.5v-9ZM16 12h5',
    bank: 'M3 10.5 12 5l9 5.5M5 10v7m4-7v7m6-7v7m4-7v7M3 19h18',
    'arrow-down': 'M12 5v14m0 0 6-6m-6 6-6-6',
    'arrow-up': 'M12 19V5m0 0 6 6m-6-6-6 6',
    activity: 'M4 13h4l2-6 4 12 2-6h4',
    check: 'M5 13l4 4L19 7',
    x: 'M6 6l12 12M18 6 6 18',
    'credit-card': 'M3 7h18v10H3V7Zm0 4h18',
    alert: 'M12 8v5m0 4h.01',
}[props.card.icon] ?? 'M12 5v14M5 12h14'));
</script>
