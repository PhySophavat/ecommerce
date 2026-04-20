<template>
    <section id="categories" class="admin-card mt-6 rounded-[28px] px-6 py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Menu highlights</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Store operations at a glance</h3>
            </div>
            <div class="flex flex-wrap gap-3">
                <div v-for="item in highlights" :key="item.label" class="admin-chip rounded-full px-4 py-2 text-sm font-medium text-slate-600">
                    <span class="text-slate-400">{{ item.label }}</span>
                    <span class="ml-2 text-slate-950">{{ item.value }}</span>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    highlights: {
        type: Array,
        required: true,
    },
});
</script>
