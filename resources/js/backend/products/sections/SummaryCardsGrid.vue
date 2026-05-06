<template>
    <section class="grid gap-4 xl:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.label"
            class="relative overflow-hidden rounded-[30px] border px-5 py-5 shadow-[0_18px_40px_rgba(44,62,148,0.08)]"
            :class="cardSurfaceClass(card.tone)"
        >
            <span class="absolute inset-x-0 top-0 h-1.5" :class="cardAccentClass(card.tone)"></span>

            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase" :class="cardLabelClass(card.tone)">{{ card.label }}</p>
                    <p class="chatgpt-title mt-5 text-[2rem] leading-none" :class="cardValueClass(card.tone)">{{ card.value }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl" :class="cardBadgeClass(card.tone)">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(card.tone)" />
                    </svg>
                </span>
            </div>

            <p class="mt-5 flex items-center gap-2 text-sm" :class="cardDetailClass(card.tone)">
                <span class="text-base leading-none" :class="cardTrendClass(card.tone)">↑</span>
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

function cardSurfaceClass(tone) {
    return {
        blue: 'border-[#cfe4ff] bg-[linear-gradient(180deg,#f8fbff_0%,#edf5ff_100%)]',
        slate: 'border-[#d9e1f0] bg-[linear-gradient(180deg,#fbfcff_0%,#f1f5fb_100%)]',
        emerald: 'border-[#cceee2] bg-[linear-gradient(180deg,#f7fffb_0%,#ebfaf3_100%)]',
        amber: 'border-[#f6dfb1] bg-[linear-gradient(180deg,#fffdf7_0%,#fff5df_100%)]',
    }[tone] ?? 'border-[#cfe4ff] bg-[linear-gradient(180deg,#f8fbff_0%,#edf5ff_100%)]';
}

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
        blue: 'bg-[#e4eeff] text-[#5562ec]',
        slate: 'bg-[#eef2f8] text-[#5f6b86]',
        emerald: 'bg-[#ddf8ec] text-[#21b889]',
        amber: 'bg-[#fff0cd] text-[#f0a31f]',
    }[tone] ?? 'bg-[#e4eeff] text-[#5562ec]';
}

function cardLabelClass(tone) {
    return {
        blue: 'text-[#7183d9]',
        slate: 'text-[#7b869f]',
        emerald: 'text-[#4a9b7c]',
        amber: 'text-[#c28a22]',
    }[tone] ?? 'text-[#7183d9]';
}

function cardValueClass(tone) {
    return {
        blue: 'text-[#1b2b63]',
        slate: 'text-[#283247]',
        emerald: 'text-[#155b44]',
        amber: 'text-[#7d5311]',
    }[tone] ?? 'text-[#1b2b63]';
}

function cardDetailClass(tone) {
    return {
        blue: 'text-[#5e6d92]',
        slate: 'text-[#667085]',
        emerald: 'text-[#4f7c69]',
        amber: 'text-[#936f2d]',
    }[tone] ?? 'text-[#5e6d92]';
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
        blue: 'M12 3v18m9-9H3',
        slate: 'M4 19h16M7 16V8m5 8V5m5 11v-6',
        emerald: 'M5 12h14M12 5l7 7-7 7',
        amber: 'M12 19V5m0 0-7 7m7-7 7 7',
    }[tone] ?? 'M12 5v14M5 12h14';
}
</script>
