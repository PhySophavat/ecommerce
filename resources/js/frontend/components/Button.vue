<template>
    <component
        :is="to ? RouterLink : 'button'"
        v-bind="buttonAttrs"
        :class="classes"
    >
        <slot />
    </component>
</template>

<script setup>
import { computed, useAttrs } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
    to: {
        type: [String, Object],
        default: null,
    },
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const attrs = useAttrs();

const buttonAttrs = computed(() => ({
    ...attrs,
    ...(props.to ? { to: props.to } : { type: props.type }),
}));

const classes = computed(() => {
    const base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition duration-200 focus:outline-none focus:ring-4';
    const variant = {
        primary: 'bg-[#1495E8] text-white shadow-[0_14px_34px_rgba(20,149,232,0.22)] hover:bg-[#0D86D6] focus:ring-[#1495E8]/20',
        secondary: 'bg-[#F3F9FD] text-[#1495E8] border border-[#D8E7F4] hover:bg-[#EFF7FD] hover:border-[#1495E8] focus:ring-[#1495E8]/12',
        ghost: 'bg-white text-[#374151] border border-[#D8E7F4] hover:bg-[#F3F9FD] hover:text-[#1495E8] focus:ring-[#1495E8]/12',
        dark: 'bg-[#0F172A] text-white hover:bg-[#111827] focus:ring-slate-900/15',
    }[props.variant];
    const size = {
        sm: 'px-4 py-2 text-sm',
        md: 'px-5 py-3 text-sm',
        lg: 'px-6 py-3.5 text-base',
    }[props.size];

    return [base, variant, size, props.block ? 'w-full' : ''].join(' ');
});
</script>
