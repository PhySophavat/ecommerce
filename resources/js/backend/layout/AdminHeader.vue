<template>
    <header class="px-4 pt-4 sm:px-6 lg:px-7 lg:pt-5">
        <article class="overflow-hidden rounded-[28px] border border-white/12 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.14),_transparent_34%),linear-gradient(135deg,#465572_0%,#56657F_42%,#A25F88_100%)] text-white shadow-[0_18px_34px_rgba(70,85,114,0.18)]">
            <div class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-6 lg:py-5">
                <div class="flex min-w-0 items-start gap-3">
                    <button
                        type="button"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/12 text-white/90 shadow-[0_10px_20px_rgba(15,23,42,0.08)] backdrop-blur-sm transition hover:bg-white/18 lg:hidden"
                        @click="$emit('toggle-menu')"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>

                    <div class="min-w-0 max-w-4xl">
                        <p v-if="heroEyebrow" class="text-[10px] font-semibold uppercase tracking-[0.26em] text-[#E5E7EB]">{{ heroEyebrow }}</p>
                        <h1 class="mt-1.5 text-[1.85rem] font-black tracking-[-0.05em] text-white sm:text-[2rem]">
                            {{ heroTitle }}
                        </h1>
                        <p v-if="heroSubtitle" class="mt-2 max-w-3xl text-sm leading-6 text-white/78">
                            {{ heroSubtitle }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-2.5 sm:flex-row sm:justify-start lg:justify-end">
                    <button
                        v-if="showUtilityActions"
                        type="button"
                        class="min-h-[48px] rounded-2xl border border-[rgba(255,255,255,0.28)] bg-[rgba(255,255,255,0.08)] px-4 py-2.5 text-sm font-semibold text-white shadow-[0_10px_20px_rgba(15,23,42,0.08)] backdrop-blur-sm transition hover:bg-[rgba(255,255,255,0.16)] focus:outline-none focus:ring-2 focus:ring-white/30"
                        @click="$emit('refresh')"
                    >
                        Refresh
                    </button>
                    <button
                        type="button"
                        class="min-h-[48px] rounded-2xl border border-white/15 bg-white px-4 py-2.5 text-sm font-semibold text-[#111827] shadow-[0_12px_24px_rgba(15,23,42,0.10)] transition hover:bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-white/30"
                        @click="$emit('primary-action')"
                    >
                        {{ primaryActionLabel }}
                    </button>
                </div>
            </div>
        </article>
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
    orders: 'Order management',
    'featured-products': 'Storefront highlights',
    'add-product': '',
    customers: 'Customer accounts',
    'customer-details': 'Customer accounts',
    'purchase-history': 'Customer accounts',
    users: 'Access control',
    'merchant-balance': 'Merchant finance',
    'qr-codes': 'Payment collection',
    'payment-records': 'Frontend payments',
    'payment-methods': 'Frontend payments',
    'payment-fees': 'Commission ledger',
    wallet: 'Platform wallet',
    deposit: 'Merchant finance',
    withdraw: 'Merchant finance',
    transactions: 'Merchant finance',
    'bank-accounts': 'Merchant finance',
    deposits: 'Wallet top-ups',
    withdrawals: 'Merchant payouts',
    'platform-fee-settings': 'Commission control',
    merchants: 'Seller management',
    'pending-merchants': 'Approval queue',
    'merchant-details': 'Merchant review',
    'merchant-orders': 'Merchant fulfillment',
}[props.screen] ?? 'Admin workspace'));
const heroSubtitle = computed(() => props.dashboard.meta.subheadline || ({
    dashboard: 'Track the latest product changes, featured placements, and inventory signals from one admin surface.',
    sliders: '',
    products: 'Manage products, categories, stock, and approval status.',
    orders: 'Review customer orders, merchant breakdowns, and payment status changes from one admin queue.',
    'featured-products': 'Focus on products with the strongest storefront visibility and update them without scanning the full catalog.',
    customers: 'View registered storefront customers and keep track of account growth from the admin dashboard.',
    'customer-details': 'Inspect storefront customer profiles and account activity in a read-only admin view.',
    'purchase-history': 'Review which customers are buying and how many orders each account has placed.',
    users: 'Keep administrator access tidy, create backend users, and review who is currently active in the dashboard.',
    'merchant-balance': 'Review merchant balances, deposits, withdrawals, and pending payout totals in one admin ledger.',
    'qr-codes': 'Preview admin payment QR codes and collection details used during manual transfer checkout flows.',
    'payment-records': 'Review customer checkout payment records, references, and status changes from one admin queue.',
    'payment-methods': 'Review the payment methods available during storefront checkout and how customers use them.',
    'payment-fees': 'Review platform fee deductions collected from merchant payouts and trace which orders generated each fee.',
    wallet: 'Track the total platform fee balance collected from merchants and review the latest fee activity in one place.',
    deposit: 'Review merchant deposit requests and submit new wallet top-ups from the merchant finance area.',
    withdraw: 'Request merchant payouts and review the accounts available for withdrawal.',
    transactions: 'Review merchant wallet activity, withdrawals, deposits, and balance movements.',
    'bank-accounts': 'Manage the payout accounts used for merchant withdrawals.',
    deposits: 'Verify merchant KHQR payment proofs and approve wallet top-ups after manual review.',
    withdrawals: 'Review merchant withdrawal requests, approve valid payouts, and mark completed transfers as paid.',
    'platform-fee-settings': 'Configure platform fee rules, choose the deduction stage, and preview what merchants receive from each order.',
    merchants: 'Create seller accounts, check merchant product activity, and keep storefront operators organized.',
    'pending-merchants': 'Approve or reject merchants who are waiting for access to the selling dashboard.',
    'merchant-details': 'Review merchant business, owner, and location information before taking action.',
    'merchant-orders': 'View only the orders that contain your products and update their fulfillment status.',
}[props.screen] ?? ''));
const primaryActionLabel = computed(() => props.dashboard.meta.primary_action_label || ({
    dashboard: 'Open products',
    sliders: '+ Add slide',
    products: '+ Add product',
    orders: 'Refresh orders',
    'featured-products': '+ Add product',
    'add-product': 'All products',
    customers: 'Refresh customers',
    'customer-details': 'Refresh customers',
    'purchase-history': 'Refresh history',
    users: 'Create admin',
    'merchant-balance': 'Refresh balances',
    'qr-codes': 'Refresh QR',
    'payment-records': 'Refresh records',
    'payment-methods': 'Refresh methods',
    'payment-fees': 'Refresh fees',
    wallet: 'Refresh wallet',
    deposit: 'Refresh deposits',
    withdraw: 'Refresh withdrawals',
    transactions: 'Refresh transactions',
    'bank-accounts': 'Refresh accounts',
    deposits: 'Refresh deposits',
    withdrawals: 'Refresh withdrawals',
    'platform-fee-settings': 'Save settings',
    merchants: 'Create merchant',
    'pending-merchants': 'All merchants',
    'merchant-details': 'Back to merchants',
    'merchant-orders': 'Refresh orders',
}[props.screen] ?? '+ Add product'));
const showUtilityActions = computed(() => props.screen !== 'sliders');
</script>
