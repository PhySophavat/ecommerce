<template>
    <label class="block">
        <span class="text-sm font-semibold text-[#111827]">
            {{ label }}
            <span v-if="required" class="text-[#A25F88]">*</span>
        </span>

        <select
            :id="id"
            :value="modelValue"
            class="mt-3 w-full rounded-[18px] border bg-white px-4 py-3 text-sm text-[#111827] outline-none transition focus:border-[#A25F88] focus:ring-4 focus:ring-[#A25F88]/10"
            :class="error ? 'border-rose-300' : 'border-[#E5E7EB]'"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="">{{ placeholder }}</option>
            <option v-for="option in normalizedOptions" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>

        <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>
    </label>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    error: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select option',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:modelValue']);

const normalizedOptions = computed(() => props.options.map((option) => {
    if (typeof option === 'string') {
        return {
            label: option,
            value: option,
        };
    }

    return {
        label: option.label,
        value: option.value,
    };
}));
</script>
