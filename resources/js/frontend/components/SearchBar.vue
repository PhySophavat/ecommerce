<template>
    <label class="relative block">
        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-[#A25F88]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
        </span>
        <input
            :value="modelValue"
            :placeholder="placeholder"
            type="search"
            class="w-full rounded-full border border-[#E5E7EB] bg-[#F8FAFC] py-3 pl-11 pr-4 text-sm text-[#111827] shadow-sm outline-none transition placeholder:text-[#94A3B8] focus:border-[#E8B4CF] focus:bg-white focus:ring-4 focus:ring-[rgba(162,95,136,0.10)]"
            @input="$emit('update:modelValue', $event.target.value)"
        >
    </label>
</template>

<script setup>
defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search products',
    },
});

defineEmits(['update:modelValue']);
</script>
