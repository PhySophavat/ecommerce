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
        primary: 'bg-[#A25F88] text-white shadow-[0_14px_34px_rgba(162,95,136,0.22)] hover:bg-[#8B4E73] focus:ring-[#A25F88]/20',
        secondary: 'bg-white text-[#111827] border border-[#E5E7EB] hover:border-[#A25F88] hover:text-[#A25F88] focus:ring-[#A25F88]/12',
        ghost: 'bg-[#F3E8F1] text-[#A25F88] hover:bg-[#ead9e4] focus:ring-[#A25F88]/12',
        dark: 'bg-[#111827] text-white hover:bg-[#1f2937] focus:ring-slate-900/15',
    }[props.variant];
    const size = {
        sm: 'px-4 py-2 text-sm',
        md: 'px-5 py-3 text-sm',
        lg: 'px-6 py-3.5 text-base',
    }[props.size];

    return [base, variant, size, props.block ? 'w-full' : ''].join(' ');
});
</script>
