<template>
    <section
        v-if="slides.length > 0"
        class="relative w-full overflow-hidden bg-[#F8FBFE] shadow-[0_24px_60px_rgba(17,24,39,0.12)]"
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
                />
                <div v-else class="absolute inset-0 bg-[#EFF7FD]"></div>
            </div>

            <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="`dot-${slide.id}`"
                    type="button"
                    class="h-2.5 w-2.5 rounded-full transition"
                    :class="index === currentSlide ? 'bg-[#1495E8]' : 'bg-white/60 hover:bg-white/90'"
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
</script>
