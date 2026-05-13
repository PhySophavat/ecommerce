<template>
    <section
        v-if="slides.length > 0"
        class="relative mx-auto w-[90%] pt-4"
    >
        <Transition name="highlight-slide" mode="out-in">
            <div :key="`image-${activeSlide.id || currentSlide}`" class="overflow-hidden rounded-[24px]">
                <img
                    v-if="activeSlide.image_url"
                    :src="activeSlide.image_url"
                    :alt="activeSlide.title"
                    class="h-[500px] w-full object-cover"
                >
                <div
                    v-else
                    class="flex h-[500px] items-center justify-center bg-[linear-gradient(145deg,#FDF2F8,#FFF7ED)] text-6xl font-bold text-[#A25F88]"
                >
                    {{ (activeSlide.title || 'S').slice(0, 1) }}
                </div>
            </div>
        </Transition>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    slides: {
        type: Array,
        default: () => [],
    },
    currentSlide: {
        type: Number,
        default: 0,
    },
});

const activeSlide = computed(() => props.slides[props.currentSlide] ?? props.slides[0] ?? {});
</script>

<style scoped>
.highlight-slide-enter-active,
.highlight-slide-leave-active {
    transition: opacity 0.45s ease, transform 0.45s ease;
}

.highlight-slide-enter-from {
    opacity: 0;
    transform: translateX(36px);
}

.highlight-slide-leave-to {
    opacity: 0;
    transform: translateX(-36px);
}
</style>
