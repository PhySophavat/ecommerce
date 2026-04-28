<template>
    <header class="border-b border-[#dfe5f5] bg-white/72 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-8">
        <div class="space-y-5">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="admin-chip flex h-11 w-11 items-center justify-center rounded-2xl text-slate-500">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </div>

                    <div class="admin-chip rounded-2xl px-4 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400">Today</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ todayLabel }}</p>
                    </div>

                    <div v-if="dashboard.meta.kicker" class="rounded-2xl bg-[#edf1ff] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#6673db]">
                        {{ dashboard.meta.kicker }}
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        v-if="showUtilityActions"
                        type="button"
                        class="admin-chip flex h-11 w-11 items-center justify-center rounded-2xl text-slate-500 transition hover:-translate-y-0.5 hover:text-slate-900"
                        @click="$emit('refresh')"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 1 0 2-9.7L10 10" />
                        </svg>
                    </button>
                    <div class="admin-chip rounded-2xl px-4 py-2.5 text-sm text-slate-500">
                        <span class="font-medium text-slate-400">Last updated:</span>
                        <span class="ml-2 font-semibold text-slate-800">{{ updatedLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6c78da]">{{ heroEyebrow }}</p>
                    
                    <p class="mt-2 text-sm leading-7 text-slate-500">
                        {{ heroSubtitle }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        v-if="showUtilityActions"
                        type="button"
                        class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-900"
                        @click="$emit('refresh')"
                    >
                        Refresh view
                    </button>
                    <button
                        type="button"
                        class="admin-primary-button rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5"
                        @click="$emit('primary-action')"
                    >
                        {{ primaryActionLabel }}
                    </button>
                </div>
            </div>
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
    isMenuOpen: {
        type: Function,
        default: () => false,
    },
});

const todayLabel = computed(() => new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    month: 'short',
    day: 'numeric',
}).format(new Date()));
const updatedLabel = computed(() => new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
}).format(new Date()));
const heroEyebrow = computed(() => ({
    dashboard: 'Overview',
    sliders: '',
    products: '',
    'featured-products': 'Storefront highlights',
    'add-product': '',
    users: 'Access control',
    merchants: 'Seller management',
}[props.screen] ?? 'Admin workspace'));
const heroTitle = computed(() => ({
    dashboard: `Welcome back, ${props.dashboard.meta.brand}.`,
    sliders: '',
    products: '',
    'featured-products': 'Curate products that lead the storefront.',
    users: 'Manage backend access for admin users.',
    merchants: 'Review and create merchant accounts.',
}[props.screen] ?? props.dashboard.meta.page_title));
const heroSubtitle = computed(() => props.dashboard.meta.subheadline || ({
    dashboard: 'Track the latest product changes, featured placements, and inventory signals from one admin surface.',
    sliders: '',
    products: '',
    'featured-products': 'Focus on products with the strongest storefront visibility and update them without scanning the full catalog.',
    users: 'Keep administrator access tidy, create backend users, and review who is currently active in the dashboard.',
    merchants: 'Create seller accounts, check merchant product activity, and keep storefront operators organized.',
}[props.screen] ?? ''));
const primaryActionLabel = computed(() => ({
    dashboard: 'Open products',
    sliders: '+ Add slide',
    products: '+ Add product',
    'featured-products': '+ Add product',
    'add-product': 'All products',
    users: 'Create admin',
    merchants: 'Create merchant',
}[props.screen] ?? '+ Add product'));
const showUtilityActions = computed(() => props.screen !== 'sliders');

function menuIsInteractive(item) {
    return item.is_enabled || item.slug === 'add-product';
}

function isMobileMenuHighlighted(item) {
    return Boolean(item.is_active || (item.children.length && props.isMenuOpen(item.slug)));
}
</script>
