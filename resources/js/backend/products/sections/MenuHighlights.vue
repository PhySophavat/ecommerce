<template>
    <section id="categories" class="admin-card mt-6 rounded-[30px] px-6 py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Quick stats</p>
                <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Store health snapshot</h3>
                <p class="chatgpt-copy mt-2 text-sm">The main catalog signals stay pinned here so you can scan them before jumping into a workflow.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:flex xl:flex-wrap">
                <div
                    v-for="item in highlights"
                    :key="item.label"
                    class="admin-muted-panel rounded-[22px] px-4 py-3"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ item.label }}</p>
                    <p class="mt-2 text-xl font-bold tracking-[-0.04em] text-slate-950">{{ item.value }}</p>
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
