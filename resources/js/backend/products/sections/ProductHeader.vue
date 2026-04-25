<template>
    <header class="border-b border-slate-200/80 bg-white/85 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p v-if="dashboard.meta.kicker" class="chatgpt-kicker text-[11px] uppercase text-slate-400">{{ dashboard.meta.kicker }}</p>
                <h2 class="chatgpt-title text-3xl text-slate-950 sm:text-4xl" :class="dashboard.meta.kicker ? 'mt-2' : ''">{{ dashboard.meta.page_title }}</h2>
                <!-- <p class="chatgpt-copy mt-3 max-w-2xl text-sm">{{ dashboard.meta.subheadline }}</p> -->
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <template v-if="showUtilityActions">
                    <button
                        type="button"
                        class="admin-chip flex h-11 w-11 items-center justify-center rounded-2xl text-slate-500 transition hover:-translate-y-0.5 hover:text-slate-900"
                        @click="$emit('refresh')"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 1 0 2-9.7L10 10" />
                        </svg>
                    </button>
                    <button type="button" class="admin-chip flex h-11 w-11 items-center justify-center rounded-2xl text-slate-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                        </svg>
                    </button>
                    <button type="button" class="admin-chip flex h-11 w-11 items-center justify-center rounded-2xl text-slate-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0" />
                        </svg>
                    </button>
                </template>
                <button
                    type="button"
                    class="rounded-2xl bg-[linear-gradient(135deg,#3457ff,#2543b8)] px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_28px_rgba(52,87,255,0.28)] transition hover:-translate-y-0.5"
                    @click="$emit('primary-action')"
                >
                    {{ primaryActionLabel }}
                </button>
            </div>
        </div>

        <div class="soft-scroll mt-5 flex gap-3 overflow-x-auto pb-1 lg:hidden">
            <button
                v-for="item in dashboard.menu"
                :key="`mobile-${item.slug}`"
                type="button"
                class="shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition"
                :class="item.is_active ? 'border-[#3457ff] bg-[#eef3ff] text-[#3457ff]' : 'border-slate-200 bg-white text-slate-500'"
                :disabled="item.children.length ? false : !menuIsInteractive(item)"
                @click="item.children.length ? $emit('toggle-menu', item.slug) : $emit('select-item', item)"
            >
                {{ item.label }}
            </button>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';

defineEmits(['primary-action', 'refresh', 'scroll-add-product', 'select-item', 'toggle-menu']);

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    screen: {
        type: String,
        required: true,
    },
});

const primaryActionLabel = computed(() => ({
    dashboard: 'View products',
    sliders: '+ Add slide',
    products: '+ Add product',
    'add-product': 'All products',
}[props.screen] ?? '+ Add product'));
const showUtilityActions = computed(() => props.screen !== 'sliders');

function menuIsInteractive(item) {
    return item.is_enabled || item.slug === 'add-product';
}
</script>
