<template>
    <label class="relative block">
        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-[#9CA3AF]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
        </span>
        <input
            :value="modelValue"
            :placeholder="placeholder"
            type="search"
            class="w-full rounded-full border border-[#E5E7EB] bg-white py-3 pl-11 pr-4 text-sm text-[#111827] shadow-sm outline-none transition focus:border-[#A25F88] focus:ring-4 focus:ring-[#A25F88]/10"
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
