<template>
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <!-- Brand Logo -->
                <a href="/frontend" class="flex items-center gap-2">
                    <span class="text-2xl font-bold text-slate-900">{{ meta?.brand || 'Store' }}</span>
                </a>
                <!-- Category Navigation -->
                <nav class="hidden items-center gap-1 md:flex">
                    <a
                        v-for="category in categories"
                        :key="category.id"
                        :href="`/frontend?category=${category.slug}`"
                        class="rounded-full px-4 py-2 text-sm font-medium transition"
                        :class="activeCategory === category.slug 
                            ? 'bg-slate-900 text-white' 
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    >
                        {{ category.name }}
                    </a>
                </nav>
                <!-- Mobile Menu Toggle -->
                <button
                    type="button"
                    class="rounded-full p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                    @click="$emit('toggle-mobile-menu')"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <!-- Mobile Category Menu -->
            <div v-if="isMobileMenuOpen" class="mt-4 flex flex-wrap gap-2 pb-2 md:hidden">
                <a
                    v-for="category in categories"
                    :key="category.id"
                    :href="`/frontend?category=${category.slug}`"
                    class="rounded-full px-4 py-2 text-sm font-medium transition"
                    :class="activeCategory === category.slug 
                        ? 'bg-slate-900 text-white' 
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                >
                    {{ category.name }}
                </a>
            </div>
        </div>
    </header>
</template>

<script setup>
const props = defineProps({
    meta: Object,
    categories: Array,
    activeCategory: String,
    isMobileMenuOpen: Boolean
});
</script>
