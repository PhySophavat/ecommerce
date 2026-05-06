<template>
    <RouterLink :to="`/shop?category=${category.slug}`" class="group block rounded-[28px] border border-[#E5E7EB] bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-[0_18px_36px_rgba(17,24,39,0.08)]">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-sm" :style="{ background: category.accent || '#A25F88' }">
            <span class="text-lg font-black">{{ category.name.slice(0, 1) }}</span>
        </div>
        <h3 class="mt-4 text-lg font-bold tracking-[-0.03em] text-[#111827] group-hover:text-[#A25F88]">{{ category.name }}</h3>
        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ category.description || 'Explore curated products from this category.' }}</p>
        <div class="mt-4 text-sm font-semibold text-[#A25F88]">{{ category.products_count }} products</div>
    </RouterLink>
</template>

<script setup>
defineProps({
    category: {
        type: Object,
        required: true,
    },
});
</script>
