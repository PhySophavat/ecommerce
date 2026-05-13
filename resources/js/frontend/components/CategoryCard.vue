<template>
    <RouterLink :to="`/shop?category=${category.slug}`" class="group block rounded-[28px] border border-[#D8E7F4] bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-[#1495E8] hover:shadow-[0_18px_36px_rgba(20,149,232,0.12)]">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-sm" :style="{ background: category.accent || '#1495E8' }">
            <span class="text-lg font-bold">{{ category.name.slice(0, 1) }}</span>
        </div>
        <h3 class="mt-4 text-[1.15rem] font-semibold tracking-[-0.02em] text-[#111827] group-hover:text-[#1495E8]">{{ category.name }}</h3>
        <p class="mt-2 text-[15px] leading-7 text-[#6B7280]">{{ category.description || 'Explore curated products from this category.' }}</p>
        <div class="mt-4 text-[15px] font-medium text-[#1495E8]">{{ category.products_count }} products</div>
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
