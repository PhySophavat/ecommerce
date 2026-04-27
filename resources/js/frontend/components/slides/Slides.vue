<template>
    <section v-if="slides.length > 0" class="relative overflow-hidden bg-slate-900">
        <div class="relative h-[75vh] min-h-[250px] max-w-7xl mx-auto">
            <div
                v-for="(slide, index) in slides"
                :key="slide.id"
                class="absolute inset-0 transition-opacity duration-700"
                :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
            >
                <!-- Slide Image -->
                <div
                    v-if="slide.image_url"
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{ backgroundImage: `url(${slide.image_url})` }"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900/80 to-slate-900/20"></div>
                </div>
                <div v-else class="absolute inset-0 bg-gradient-to-br from-slate-700 to-slate-900"></div>
                <!-- Slide Content -->
                <div class="relative flex h-full items-center px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                     
                      
                       
                    </div>
                </div>
            </div>
            <!-- Slide Navigation -->
            <div class="absolute bottom-6 left-1/2 flex -translate-x-1/2 gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="`dot-${slide.id}`"
                    type="button"
                    class="h-2 w-2 rounded-full transition"
                    :class="index === currentSlide ? 'bg-white' : 'bg-white/40 hover:bg-white/60'"
                    @click="$emit('update:currentSlide', index)"
                />
            </div>
        </div>
    </section>
</template>

<script setup>
const props = defineProps({
    slides: Array,
    currentSlide: Number
});
</script>
