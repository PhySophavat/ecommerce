<template>
    <section class="grid gap-4 xl:grid-cols-4">
        <article v-for="card in cards" :key="card.label" class="admin-card rounded-[28px] px-5 py-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">{{ card.label }}</p>
                    <p class="chatgpt-title mt-3 text-3xl text-slate-950">{{ card.value }}</p>
                </div>
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl" :class="summaryToneClass(card.tone)">
                    <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                </span>
            </div>
            <p class="chatgpt-copy mt-4 text-sm">{{ card.detail }}</p>
        </article>
    </section>
</template>

<script setup>
defineProps({
    cards: {
        type: Array,
        required: true,
    },
});

function summaryToneClass(tone) {
    return {
        blue: 'bg-[#eef3ff] text-[#3457ff]',
        slate: 'bg-slate-100 text-slate-500',
        emerald: 'bg-emerald-50 text-emerald-600',
        amber: 'bg-amber-50 text-amber-600',
    }[tone] ?? 'bg-slate-100 text-slate-500';
}
</script>
