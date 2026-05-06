<template>
    <section
        v-if="slides.length > 0"
        class="relative w-full overflow-hidden bg-[#111827] shadow-[0_24px_60px_rgba(17,24,39,0.12)]"
    >
        <div class="relative h-[420px] min-h-[250px] w-full sm:h-[520px]">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-700"
                :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
            >
                <div
                    v-if="slide.image_url"
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{ backgroundImage: `url(${slide.image_url})` }"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-[#111827]/82 via-[#111827]/50 to-[#111827]/12"></div>
                </div>
                <div v-else class="absolute inset-0 bg-gradient-to-br from-[#A25F88] to-[#111827]"></div>

                <div class="relative flex h-full items-center px-5 sm:px-8 lg:px-10">
                    <div class="max-w-2xl">
                        <p
                            v-if="slide.eyebrow || slide.category"
                            class="text-xs font-semibold uppercase tracking-[0.26em] text-white/70"
                        >
                            {{ slide.eyebrow || slide.category }}
                        </p>
                        <h2 class="mt-4 text-3xl font-black tracking-[-0.05em] text-white sm:text-5xl">
                            {{ slide.title }}
                            <span v-if="slide.highlight" class="text-[#F9A8D4]"> {{ slide.highlight }}</span>
                        </h2>
                        <p v-if="slide.description" class="mt-5 max-w-xl text-sm leading-7 text-slate-200 sm:text-base">
                            {{ slide.description }}
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <a
                                :href="normalizeUrl(slide.button_url)"
                                class="inline-flex items-center justify-center rounded-full bg-[#A25F88] px-6 py-3 text-sm font-semibold text-white shadow-[0_14px_34px_rgba(162,95,136,0.24)] transition hover:bg-[#8B4E73]"
                            >
                                {{ slide.button_text || 'Shop now' }}
                            </a>
                            <span
                                v-if="slide.badge_text"
                                class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/10 px-4 py-3 text-sm font-semibold text-white backdrop-blur"
                            >
                                {{ slide.badge_text }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="`dot-${slide.id}`"
                    type="button"
                    class="h-2.5 w-2.5 rounded-full transition"
                    :class="index === currentSlide ? 'bg-[#A25F88]' : 'bg-white/40 hover:bg-white/60'"
                    @click="$emit('update:currentSlide', index)"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    slides: {
        type: Array,
        default: () => [],
    },
    currentSlide: {
        type: Number,
        default: 0,
    },
});

function normalizeUrl(url) {
    if (!url) {
        return '/frontend#/shop';
    }

    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/frontend#')) {
        return url;
    }

    if (url.startsWith('/frontend')) {
        return url.includes('#') ? url : `${url}#/shop`;
    }

    if (url.startsWith('/')) {
        return `/frontend#${url}`;
    }

    return '/frontend#/shop';
}
</script>
