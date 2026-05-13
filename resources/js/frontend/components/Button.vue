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
    const base = 'inline-flex items-center justify-center gap-2 rounded-full font-semibold leading-none transition-all duration-300 ease-out focus:outline-none focus:ring-4';
    const variant = {
        primary: 'bg-[#A25F88] text-white shadow-[0_8px_16px_rgba(162,95,136,0.14)] hover:bg-[#8E4F76] hover:shadow-[0_14px_24px_rgba(162,95,136,0.25)] focus:ring-[rgba(162,95,136,0.18)]',
        secondary: 'border border-[#E5E7EB] bg-[#FDF2F8] text-[#A25F88] hover:border-[#E8B4CF] hover:bg-[#FCE7F3] focus:ring-[rgba(162,95,136,0.12)]',
        ghost: 'border border-[#E5E7EB] bg-white text-[#6B7280] hover:bg-[#FDF2F8] hover:text-[#A25F88] hover:border-[#E8B4CF] focus:ring-[rgba(162,95,136,0.12)]',
        dark: 'bg-[#0F172A] text-white hover:bg-[#111827] focus:ring-slate-900/15',
    }[props.variant];
    const size = {
        sm: 'px-4 py-2.5 text-[14px]',
        md: 'px-5 py-3 text-[15px]',
        lg: 'px-6 py-3.5 text-[15px]',
    }[props.size];

    return [base, variant, size, props.block ? 'w-full' : ''].join(' ');
});
</script>
