<template>
    <RouterLink :to="`/shop?category=${category.slug}`" class="group block rounded-[24px] border border-[#E5E7EB] bg-white p-5 shadow-[0_10px_26px_rgba(17,24,39,0.05)] transition duration-300 hover:-translate-y-1 hover:border-[#E8B4CF] hover:shadow-[0_18px_30px_rgba(162,95,136,0.10)]">
        <div class="flex items-start justify-between gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-[16px] text-white shadow-[0_10px_22px_rgba(17,24,39,0.08)]" :style="{ background: category.accent || '#A25F88' }">
                <span class="text-base font-bold">{{ category.name.slice(0, 1) }}</span>
            </div>
            <span class="rounded-full border border-[#F1E4ED] bg-[#FCF7FA] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-[#A25F88]">
                {{ category.products_count }} items
            </span>
        </div>
        <h3 class="mt-4 text-[1.05rem] font-semibold tracking-[-0.02em] text-[#111827] transition group-hover:text-[#8E4F76]">{{ category.name }}</h3>
        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ category.description || 'Explore curated products from this category.' }}</p>
        <div class="mt-4 text-sm font-semibold text-[#A25F88]">Browse collection</div>
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
