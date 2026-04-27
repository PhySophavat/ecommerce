<template>
    <label class="block">
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-semibold text-[#111827]">
                {{ label }}
                <span v-if="required" class="text-[#A25F88]">*</span>
            </span>
            <span v-if="hint" class="text-xs text-[#6B7280]">{{ hint }}</span>
        </div>

        <textarea
            v-if="as === 'textarea'"
            :id="id"
            :rows="rows"
            :placeholder="placeholder"
            :value="modelValue"
            class="mt-3 w-full rounded-[18px] border bg-white px-4 py-3 text-sm text-[#111827] outline-none transition placeholder:text-[#9CA3AF] focus:border-[#A25F88] focus:ring-4 focus:ring-[#A25F88]/10"
            :class="error ? 'border-rose-300' : 'border-[#E5E7EB]'"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <input
            v-else
            :id="id"
            :inputmode="inputmode"
            :min="min"
            :placeholder="placeholder"
            :step="step"
            :type="type"
            :value="modelValue"
            class="mt-3 w-full rounded-[18px] border bg-white px-4 py-3 text-sm text-[#111827] outline-none transition placeholder:text-[#9CA3AF] focus:border-[#A25F88] focus:ring-4 focus:ring-[#A25F88]/10"
            :class="error ? 'border-rose-300' : 'border-[#E5E7EB]'"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>
    </label>
</template>

<script setup>
defineProps({
    as: {
        type: String,
        default: 'input',
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        required: true,
    },
    inputmode: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        required: true,
    },
    min: {
        type: [Number, String],
        default: null,
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    rows: {
        type: Number,
        default: 5,
    },
    step: {
        type: [Number, String],
        default: null,
    },
    type: {
        type: String,
        default: 'text',
    },
});

defineEmits(['update:modelValue']);
</script>
