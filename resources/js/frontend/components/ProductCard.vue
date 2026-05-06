<template>
    <article class="group overflow-hidden rounded-[30px] border border-[#E5E7EB] bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-[0_18px_40px_rgba(17,24,39,0.08)]">
        <div class="relative">
            <RouterLink :to="`/product/${product.id}`" class="block aspect-[4/3] overflow-hidden bg-[linear-gradient(135deg,#fdf2f8,#eff6ff)]">
                <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                <div v-else class="flex h-full items-center justify-center bg-[radial-gradient(circle_at_top_left,_#fbcfe8,_#dbeafe_65%,_#f8fafc)] text-xs font-semibold uppercase tracking-[0.2em] text-[#64748B]">
                    {{ product.category }}
                </div>
            </RouterLink>

            <button type="button" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-[#6B7280] shadow-sm backdrop-blur transition hover:text-[#A25F88]" @click="$emit('toggle-wishlist', product.id)">
                <svg class="h-5 w-5" :class="wishlist ? 'fill-[#A25F88] text-[#A25F88]' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 20.25-1.45-1.32C5.4 14.36 2 11.28 2 7.5 2 4.42 4.42 2 7.5 2c1.74 0 3.41.81 4.5 2.09A6 6 0 0 1 16.5 2C19.58 2 22 4.42 22 7.5c0 3.78-3.4 6.86-8.55 11.43L12 20.25Z" />
                </svg>
            </button>

            <div class="absolute left-4 top-4 flex flex-wrap gap-2">
                <span v-if="product.is_featured" class="rounded-full bg-[#A25F88] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white">Featured</span>
                <span v-if="product.compare_at_price" class="rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#A25F88]">Sale</span>
            </div>
        </div>

        <div class="p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">{{ product.category }}</p>
                    <RouterLink :to="`/product/${product.id}`" class="mt-2 block text-lg font-black tracking-[-0.03em] text-[#111827] transition hover:text-[#A25F88]">
                        {{ product.name }}
                    </RouterLink>
                </div>
                <span class="rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#475569]">{{ product.merchant_name }}</span>
            </div>

            <div class="mt-3 flex items-center gap-2 text-sm text-[#6B7280]">
                <div class="flex items-center gap-1 text-amber-500">
                    <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20">
                        <path d="m9.05 2.93-1.4 2.84-3.13.45a1 1 0 0 0-.55 1.7l2.27 2.22-.53 3.12a1 1 0 0 0 1.45 1.05L10 12.82l2.89 1.52a1 1 0 0 0 1.45-1.05l-.53-3.12 2.27-2.22a1 1 0 0 0-.55-1.7l-3.13-.45-1.4-2.84a1 1 0 0 0-1.79 0Z" />
                    </svg>
                    <span class="font-semibold">{{ Number(product.rating_value || 4.8).toFixed(1) }}</span>
                </div>
                <span>({{ product.reviews_count }} reviews)</span>
            </div>

            <div class="mt-4 flex items-end justify-between gap-3">
                <div>
                    <div class="text-2xl font-black tracking-[-0.04em] text-[#111827]">{{ product.price }}</div>
                    <div v-if="product.compare_at_price" class="text-sm text-[#9CA3AF] line-through">{{ product.compare_at_price }}</div>
                </div>
                <Button
                    variant="dark"
                    size="sm"
                    :disabled="!product.is_orderable"
                    @click="$emit('add-to-cart', product.id)"
                >
                    {{ product.is_orderable ? 'Add to cart' : 'Unavailable' }}
                </Button>
            </div>
        </div>
    </article>
</template>

<script setup>
import Button from './Button.vue';

defineProps({
    product: {
        type: Object,
        required: true,
    },
    wishlist: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['add-to-cart', 'toggle-wishlist']);
</script>
