<template>
    <aside class="space-y-6 rounded-[30px] border border-[#E5E7EB] bg-white p-6 shadow-[0_10px_28px_rgba(17,24,39,0.05)]">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#A78B9A]">Filters</p>
                <h3 class="mt-1 text-xl font-black tracking-[-0.03em] text-[#111827]">Refine products</h3>
            </div>
            <button type="button" class="text-sm font-semibold text-[#A25F88] transition hover:text-[#8E4F76]" @click="$emit('reset')">Reset</button>
        </div>

        <div class="space-y-5 text-sm">
            <section>
                <h4 class="font-semibold text-[#111827]">Category</h4>
                <div class="mt-3 space-y-2">
                    <label v-for="category in categories" :key="category.id" class="flex items-center gap-3 text-[#6B7280]">
                        <input :checked="modelValue.category === category.slug" name="category-filter" type="radio" class="h-4 w-4 accent-[#A25F88]" @change="update('category', category.slug)">
                        <span>{{ category.name }}</span>
                    </label>
                </div>
            </section>

            <section>
                <h4 class="font-semibold text-[#111827]">Price</h4>
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <input :value="modelValue.minPrice" type="number" min="0" placeholder="Min" class="filter-input" @input="update('minPrice', $event.target.value)">
                    <input :value="modelValue.maxPrice" type="number" min="0" placeholder="Max" class="filter-input" @input="update('maxPrice', $event.target.value)">
                </div>
            </section>

            <section>
                <h4 class="font-semibold text-[#111827]">Rating</h4>
                <select :value="modelValue.rating" class="filter-input mt-3" @change="update('rating', $event.target.value)">
                    <option value="">All ratings</option>
                    <option value="4">4 stars and above</option>
                    <option value="4.5">4.5 stars and above</option>
                </select>
            </section>

            <section>
                <h4 class="font-semibold text-[#111827]">Color</h4>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="color in colors" :key="color" type="button" class="rounded-full border px-3 py-2 text-xs font-semibold transition duration-300" :class="modelValue.color === color ? 'border-[#E8B4CF] bg-[#FDF2F8] text-[#A25F88]' : 'border-[#E5E7EB] bg-white text-[#6B7280] hover:border-[#E8B4CF] hover:bg-[#FDF2F8] hover:text-[#A25F88]'" @click="update('color', modelValue.color === color ? '' : color)">
                        {{ color }}
                    </button>
                </div>
            </section>

            <section>
                <h4 class="font-semibold text-[#111827]">Size</h4>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="size in sizes" :key="size" type="button" class="rounded-full border px-3 py-2 text-xs font-semibold transition duration-300" :class="modelValue.size === size ? 'border-[#E8B4CF] bg-[#FDF2F8] text-[#A25F88]' : 'border-[#E5E7EB] bg-white text-[#6B7280] hover:border-[#E8B4CF] hover:bg-[#FDF2F8] hover:text-[#A25F88]'" @click="update('size', modelValue.size === size ? '' : size)">
                        {{ size }}
                    </button>
                </div>
            </section>

            <section>
                <h4 class="font-semibold text-[#111827]">Merchant</h4>
                <select :value="modelValue.merchant" class="filter-input mt-3" @change="update('merchant', $event.target.value)">
                    <option value="">All merchants</option>
                    <option v-for="merchant in merchants" :key="merchant" :value="merchant">{{ merchant }}</option>
                </select>
            </section>
        </div>
    </aside>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    merchants: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => ['Rose', 'Ivory', 'Slate', 'Navy', 'Sage'],
    },
    sizes: {
        type: Array,
        default: () => ['XS', 'S', 'M', 'L', 'XL'],
    },
});

const emit = defineEmits(['update:modelValue', 'reset']);

function update(key, value) {
    emit('update:modelValue', {
        ...props.modelValue,
        [key]: value,
    });
}
</script>

<style scoped>
.filter-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 0.8rem 1rem;
    color: #111827;
    outline: none;
    transition: border-color 200ms ease, box-shadow 200ms ease, background-color 200ms ease;
}

.filter-input:focus {
    border-color: #e8b4cf;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.1);
}
</style>
