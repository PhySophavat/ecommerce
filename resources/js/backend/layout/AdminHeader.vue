<template>
    <header class="border-b border-[#dfe5f5] bg-white/88 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <button
                    type="button"
                    class="admin-chip flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-slate-500 transition hover:-translate-y-0.5 hover:text-slate-900"
                    @click="$emit('toggle-menu')"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>

                <div class="min-w-0">
                    <p v-if="heroEyebrow" class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#6c78da]">{{ heroEyebrow }}</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                        {{ heroTitle }}
                    </h1>
                    <p v-if="heroSubtitle" class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">
                        {{ heroSubtitle }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    v-if="showUtilityActions"
                    type="button"
                    class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5 hover:text-slate-900"
                    @click="$emit('refresh')"
                >
                    Refresh
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

const heroTitle = computed(() => props.dashboard.meta.page_title || 'Admin workspace');
const heroEyebrow = computed(() => props.dashboard.meta.kicker || ({
    dashboard: 'Overview',
    sliders: '',
    products: '',
    'featured-products': 'Storefront highlights',
    'add-product': '',
    users: 'Access control',
    wallet: 'Finance overview',
    deposits: 'Wallet top-ups',
    withdrawals: 'Merchant payouts',
    'platform-fee-settings': 'Commission control',
    merchants: 'Seller management',
    'pending-merchants': 'Approval queue',
    'merchant-details': 'Merchant review',
}[props.screen] ?? 'Admin workspace'));
const heroSubtitle = computed(() => props.dashboard.meta.subheadline || ({
    dashboard: 'Track the latest product changes, featured placements, and inventory signals from one admin surface.',
    sliders: '',
    products: '',
    'featured-products': 'Focus on products with the strongest storefront visibility and update them without scanning the full catalog.',
    users: 'Keep administrator access tidy, create backend users, and review who is currently active in the dashboard.',
    wallet: 'Open the wallet overview to monitor merchant deposit and withdrawal queues from one place.',
    deposits: 'Verify merchant KHQR payment proofs and approve wallet top-ups after manual review.',
    withdrawals: 'Review merchant withdrawal requests, approve valid payouts, and mark completed transfers as paid.',
    'platform-fee-settings': 'Configure platform fee rules, choose the deduction stage, and preview what merchants receive from each order.',
    merchants: 'Create seller accounts, check merchant product activity, and keep storefront operators organized.',
    'pending-merchants': 'Approve or reject merchants who are waiting for access to the selling dashboard.',
    'merchant-details': 'Review merchant business, owner, and location information before taking action.',
}[props.screen] ?? ''));
const primaryActionLabel = computed(() => ({
    dashboard: 'Open products',
    sliders: '+ Add slide',
    products: '+ Add product',
    'featured-products': '+ Add product',
    'add-product': 'All products',
    users: 'Create admin',
    wallet: 'Refresh overview',
    deposits: 'Refresh deposits',
    withdrawals: 'Refresh withdrawals',
    'platform-fee-settings': 'Save settings',
    merchants: 'Create merchant',
    'pending-merchants': 'All merchants',
    'merchant-details': 'Back to merchants',
}[props.screen] ?? '+ Add product'));
const showUtilityActions = computed(() => props.screen !== 'sliders');
</script>
