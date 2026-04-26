<template>
    <section class="grid gap-4 xl:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.label"
            class="admin-card relative overflow-hidden rounded-[30px] px-5 py-5"
        >
            <span class="absolute inset-x-0 top-0 h-1.5" :class="cardAccentClass(card.tone)"></span>

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">{{ card.label }}</p>
                    <p class="chatgpt-title mt-5 text-[2rem] leading-none text-slate-950">{{ card.value }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl" :class="cardBadgeClass(card.tone)">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(card.tone)" />
                    </svg>
                </span>
            </div>

            <p class="mt-5 flex items-center gap-2 text-sm text-slate-500">
                <span class="text-base leading-none" :class="cardTrendClass(card.tone)">↗</span>
                <span>{{ card.detail }}</span>
            </p>
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

function cardAccentClass(tone) {
    return {
        blue: 'bg-[#5a67f2]',
        slate: 'bg-[#5f6b86]',
        emerald: 'bg-[#21b889]',
        amber: 'bg-[#f0a31f]',
    }[tone] ?? 'bg-[#5a67f2]';
}

function cardBadgeClass(tone) {
    return {
        blue: 'bg-[#eef1ff] text-[#5562ec]',
        slate: 'bg-[#eef2f8] text-[#5f6b86]',
        emerald: 'bg-[#e8fbf4] text-[#21b889]',
        amber: 'bg-[#fff6e6] text-[#f0a31f]',
    }[tone] ?? 'bg-[#eef1ff] text-[#5562ec]';
}

function cardTrendClass(tone) {
    return {
        blue: 'text-[#5562ec]',
        slate: 'text-[#5f6b86]',
        emerald: 'text-[#21b889]',
        amber: 'text-[#f0a31f]',
    }[tone] ?? 'text-[#5562ec]';
}

function iconPath(tone) {
    return {
        blue: 'M17 20v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2m13-13a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm6 12v-2a3 3 0 0 0-2-2.83M15 7.13a3 3 0 0 1 0 5.74',
        slate: 'M4 19h16M7 16V8m5 8V5m5 11v-6',
        emerald: 'M16 18v-1a4 4 0 0 0-8 0v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 8v-1a3 3 0 0 0-2-2.83',
        amber: 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6',
    }[tone] ?? 'M12 5v14M5 12h14';
}
</script>
