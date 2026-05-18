<template>
    <div class="mx-auto w-full lg:w-[80%] px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Checkout page</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Complete shipping and payment</h1>
        </div>

        <div class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <section class="space-y-6">
                <div class="rounded-[32px] border border-[#D8E7F4] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Shipping address</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <input v-model.trim="form.customer_name" type="text" placeholder="Full name" class="field-input">
                        <input v-model.trim="form.phone" type="text" placeholder="Phone number" class="field-input">
                        <input v-model.trim="form.email" type="email" placeholder="Email address" class="field-input sm:col-span-2">
                        <input v-model.trim="form.address_line1" type="text" placeholder="Address line 1" class="field-input sm:col-span-2">
                        <input v-model.trim="form.address_line2" type="text" placeholder="Address line 2" class="field-input sm:col-span-2">
                        <input v-model.trim="form.city" type="text" placeholder="City" class="field-input">
                        <input v-model.trim="form.postal_code" type="text" placeholder="Postal code" class="field-input">
                        <textarea v-model.trim="form.notes" rows="4" placeholder="Order note" class="field-input !rounded-[24px] sm:col-span-2"></textarea>
                    </div>
                </div>

                <div class="rounded-[32px] border border-[#D8E7F4] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Order review</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in store.cartItems" :key="`${item.id}-${item.variant}`" class="flex items-center justify-between gap-4 rounded-[24px] bg-[#F8FBFE] px-4 py-4">
                            <div>
                                <div class="font-semibold text-[#111827]">{{ item.name }}</div>
                                <div class="mt-1 text-sm text-[#6B7280]">{{ item.merchant_name }} &middot; {{ item.quantity }} qty</div>
                            </div>
                            <div class="text-right font-semibold text-[#111827]">${{ item.line_total.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="space-y-5 xl:sticky xl:top-28 xl:self-start">
                <OrderSummary title="Place order" :lines="store.orderSummaryLines()">
                    <div v-if="error" class="mb-4 rounded-[22px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
                    <Button block size="lg" :disabled="submitting || !store.cartItems.length" @click="openPaymentModal">
                        {{ submitting ? 'Placing order...' : 'Place order' }}
                    </Button>
                </OrderSummary>
            </div>
        </div>

        <transition name="modal-fade">
            <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center px-3 py-6 sm:px-6">
                <div class="absolute inset-0 bg-[rgba(15,23,42,0.55)] backdrop-blur-[6px]" @click="closePaymentModal"></div>
                <div class="payment-modal relative z-10 flex max-h-[88vh] flex-col overflow-hidden rounded-[20px] border border-[#E5E7EB] bg-white shadow-[0_20px_50px_rgba(15,23,42,0.16)]">
                    <div
                        class="flex justify-between gap-4 border-b border-[#F3F4F6] px-5 pb-4 pt-5 sm:px-6"
                        :class="paymentModalStep === 'detail' ? 'items-center' : 'items-start'"
                    >
                        <template v-if="paymentModalStep === 'select'">
                            <div>
                                <h2 class="text-lg font-semibold tracking-[-0.02em] text-[#111827]">Select payment method</h2>
                                <p class="mt-1.5 text-sm text-[#6B7280]">Preferred method with secure transactions.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#6B7280] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="closePaymentModal"
                            >
                                &times;
                            </button>
                        </template>

                        <template v-else-if="paymentModalStep === 'detail'">
                            <button
                                type="button"
                                class="inline-flex min-h-[34px] items-center justify-center rounded-[10px] border border-[#E5E7EB] bg-white px-3 text-sm font-medium text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="paymentModalStep = 'select'"
                            >
                                Back
                            </button>
                            <div class="min-w-0 flex-1 px-2 text-center">
                                <h2 class="truncate text-base font-semibold tracking-[-0.02em] text-[#111827]">{{ selectedPaymentMethod.title }}</h2>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#6B7280] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="closePaymentModal"
                            >
                                &times;
                            </button>
                        </template>

                        <template v-else-if="paymentModalStep === 'processing'">
                            <div>
                                <h2 class="text-lg font-semibold tracking-[-0.02em] text-[#111827]">Processing your order</h2>
                                <p class="mt-1.5 text-sm text-[#6B7280]">Please wait while we complete your purchase.</p>
                            </div>
                        </template>

                        <template v-else-if="paymentModalStep === 'success'">
                            <div>
                                <h2 class="text-lg font-semibold tracking-[-0.02em] text-[#111827]">Order complete</h2>
                                <p class="mt-1.5 text-sm text-[#6B7280]">Everything is ready for the next step.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#6B7280] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="closePaymentModal"
                            >
                                &times;
                            </button>
                        </template>

                        <template v-else>
                            <div>
                                <h2 class="text-lg font-semibold tracking-[-0.02em] text-[#111827]">Order failed</h2>
                                <p class="mt-1.5 text-sm text-[#6B7280]">We could not finish your order.</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#6B7280] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="closePaymentModal"
                            >
                                &times;
                            </button>
                        </template>
                    </div>

                    <div v-if="paymentModalStep === 'select'" class="px-5 pb-5 pt-4 sm:px-6">
                        <div class="space-y-2.5">
                            <label
                                v-for="method in paymentMethods"
                                :key="method.value"
                                class="flex min-h-[52px] w-full cursor-pointer items-center gap-3 rounded-[10px] border border-[#E5E7EB] bg-white px-3.5 py-3 transition duration-200"
                                :class="paymentMethod === method.value ? 'border-[#A25F88] bg-[rgba(162,95,136,0.08)] text-[#A25F88]' : 'hover:bg-[rgba(162,95,136,0.05)]'"
                            >
                                <div class="payment-brand-mark payment-brand-mark-compact shrink-0" :class="method.visualClass">
                                    <img
                                        v-if="method.logoSrc"
                                        :src="method.logoSrc"
                                        :alt="`${method.label} logo`"
                                        class="payment-logo-image"
                                    >
                                    <span v-else>{{ method.badge }}</span>
                                </div>
                                <div class="min-w-0 flex-1 text-sm font-medium" :class="paymentMethod === method.value ? 'text-[#A25F88]' : 'text-[#111827]'">
                                    {{ method.label }}
                                </div>
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition"
                                    :class="paymentMethod === method.value ? 'border-[#A25F88] text-[#A25F88]' : 'border-[#D1D5DB] text-transparent'"
                                >
                                    <span class="text-[11px] leading-none">&#9679;</span>
                                </div>
                                <input v-model="paymentMethod" :value="method.value" type="radio" class="sr-only">
                            </label>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                                @click="goToPaymentDetail"
                            >
                                Continue
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] border border-[#E5E7EB] bg-white px-5 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="closePaymentModal"
                            >
                                Go Back
                            </button>
                        </div>
                    </div>

                    <div v-else-if="paymentModalStep === 'detail'" class="min-h-0 flex-1 overflow-y-auto px-5 pb-4 pt-4 sm:px-6">
                        <div class="rounded-[14px] border border-[#F1E7ED] bg-[#FCFAFB] p-3.5">
                            <div class="flex items-center gap-3">
                                <div class="payment-brand-mark payment-brand-mark-compact shrink-0" :class="selectedPaymentMethod.visualClass">
                                    <img
                                        v-if="selectedPaymentMethod.logoSrc"
                                        :src="selectedPaymentMethod.logoSrc"
                                        :alt="`${selectedPaymentMethod.label} logo`"
                                        class="payment-logo-image"
                                    >
                                    <span v-else>{{ selectedPaymentMethod.badge }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-[#111827]">{{ selectedPaymentMethod.label }}</div>
                                    <div class="mt-1 text-xs leading-5 text-[#6B7280]">{{ selectedPaymentMethod.instructions }}</div>
                                </div>
                            </div>

                            <div v-if="selectedPaymentMethod.showTransferPanel" class="mt-4 grid gap-3">
                                <div class="rounded-[12px] border border-[#E5E7EB] bg-white p-3">
                                    <div class="payment-detail-logo-wrap">
                                        <img
                                            v-if="selectedPaymentMethod.logoSrc"
                                            :src="selectedPaymentMethod.logoSrc"
                                            :alt="`${selectedPaymentMethod.label} logo`"
                                            class="payment-detail-logo"
                                        >
                                        <span v-else>{{ selectedPaymentMethod.badge }}</span>
                                    </div>
                                    <img src="/khqr.jpg" alt="Payment QR code" class="mx-auto mt-3 h-[120px] w-[120px] rounded-[10px] object-cover">
                                </div>
                                <div class="rounded-[12px] border border-[#E5E7EB] bg-white p-3 text-xs text-[#111827]">
                                    <div><span class="font-semibold text-[#6B7280]">Account:</span> {{ selectedPaymentMethod.accountName || 'Manual payment' }}</div>
                                    <div class="mt-1"><span class="font-semibold text-[#6B7280]">Number:</span> {{ selectedPaymentMethod.accountNumber || 'Reference required' }}</div>
                                </div>
                            </div>

                            <div v-else class="mt-4 rounded-[12px] border border-[#E5E7EB] bg-white p-3 text-xs leading-5 text-[#6B7280]">
                                {{ selectedPaymentMethod.cashNote }}
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3">
                            <input
                                v-model.trim="form.payment_reference"
                                type="text"
                                class="field-input"
                                :placeholder="selectedPaymentMethod.referencePlaceholder"
                                :disabled="paymentMethod === 'cash'"
                            >
                            <textarea
                                v-model.trim="form.payment_notes"
                                rows="2"
                                class="field-input !rounded-[12px]"
                                :placeholder="selectedPaymentMethod.notesPlaceholder"
                            ></textarea>
                        </div>

                        <div class="mt-4 rounded-[12px] border border-[#F1E7ED] bg-[#FCFAFB] px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-medium text-[#111827]">Order total</span>
                                <span class="text-sm font-semibold text-[#A25F88]">{{ orderTotalLabel }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="paymentModalStep === 'processing'" class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-5 sm:px-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="processing-spinner"></div>
                            <h3 class="mt-5 text-lg font-semibold text-[#111827]">Creating your order</h3>
                            <p class="mt-2 max-w-[280px] text-sm leading-6 text-[#6B7280]">
                                We are verifying your payment and preparing the order details now.
                            </p>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div
                                v-for="(step, index) in processingSteps"
                                :key="step"
                                class="flex items-center gap-3 rounded-[12px] border px-4 py-3 transition"
                                :class="processingStepCardClass(index)"
                            >
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                    :class="processingStepIconClass(index)"
                                >
                                    <span v-if="index < processingStepIndex">&#10003;</span>
                                    <span v-else-if="index === processingStepIndex" class="h-2 w-2 rounded-full bg-current"></span>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="text-sm" :class="index <= processingStepIndex ? 'text-[#111827]' : 'text-[#6B7280]'">{{ step }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="paymentModalStep === 'success'" class="px-5 pb-5 pt-5 text-center sm:px-6">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(16,185,129,0.12)] text-[#10B981]">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13 9 17 19 7" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-semibold text-[#111827]">Order placed successfully</h3>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">Your order has been submitted and is now being processed.</p>
                        <div class="mt-6 grid gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                                @click="viewPlacedOrder"
                            >
                                View order
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] border border-[#E5E7EB] bg-white px-5 py-3 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="continueShopping"
                            >
                                Continue shopping
                            </button>
                        </div>
                    </div>

                    <div v-else class="px-5 pb-5 pt-5 text-center sm:px-6">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(239,68,68,0.12)] text-[#EF4444]">
                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.3 3.9l-7 12.1A1 1 0 0 0 4.17 17.5h13.66a1 1 0 0 0 .87-1.5l-7-12.1a1 1 0 0 0-1.74 0Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-xl font-semibold text-[#111827]">Order failed</h3>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ modalErrorMessage }}</p>
                        <div class="mt-6 grid gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                                @click="retryOrder"
                            >
                                Try again
                            </button>
                        </div>
                    </div>

                    <div v-if="paymentModalStep === 'detail'" class="border-t border-[#F3F4F6] px-5 pb-5 pt-4 sm:px-6">
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitting"
                            @click="confirmOrder"
                        >
                            {{ submitting ? 'Confirming...' : 'Confirm order' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import OrderSummary from '../components/OrderSummary.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const router = useRouter();
const submitting = ref(false);
const error = ref('');
const showPaymentModal = ref(false);
const paymentModalStep = ref('select');
const latestOrderId = ref(null);
const modalErrorMessage = ref('Unable to place this order.');
const processingStepIndex = ref(0);
const paymentMethod = ref('cash');
let processingInterval = null;
const processingSteps = [
    'Checking payment information...',
    'Creating your order...',
    'Updating product stock...',
    'Finalizing order...',
];
const paymentMethods = [
    {
        value: 'cash',
        label: 'Cash',
        title: 'Cash payment',
        text: 'Cash on delivery or manual settlement.',
        description: 'Collect payment when the order reaches the customer or when a manual handoff is completed.',
        instructions: 'No transfer is required. Confirm the order and collect payment at delivery or at the merchant handoff point.',
        referencePlaceholder: 'No payment reference needed for cash',
        notesPlaceholder: 'Optional note for delivery or cash handling',
        badge: 'CA',
        visualClass: 'payment-brand-cash',
        logoSrc: '/cash.jpg',
        showTransferPanel: false,
        cashNote: 'Cash orders do not require a transfer slip. Add an optional note if the rider or merchant needs a special collection instruction.',
    },
    {
        value: 'aba_qr',
        label: 'ABA QR',
        title: 'ABA QR transfer',
        text: 'Customer pays with ABA QR transfer.',
        description: 'Scan the QR or transfer to the ABA account, then provide the transfer reference for verification.',
        instructions: 'Use ABA app to scan the QR or transfer to the listed account. After payment, enter the ABA transfer reference and optional sender note.',
        referencePlaceholder: 'ABA transfer reference',
        notesPlaceholder: 'Optional payment note or sender name',
        badge: 'ABA',
        visualClass: 'payment-brand-aba',
        logoSrc: '/aba.png',
        showTransferPanel: true,
        accountName: 'E-Commerce ABA Collection',
        accountNumber: '001 234 567',
        channelLabel: 'ABA QR / Bank transfer',
    },
    {
        value: 'acleda',
        label: 'ACLEDA',
        title: 'ACLEDA transfer',
        text: 'Customer pays with ACLEDA transfer.',
        description: 'Use ACLEDA banking to transfer the total amount, then submit the payment reference.',
        instructions: 'Transfer the total to the ACLEDA account shown here. Enter the ACLEDA transaction reference so admin can verify the payment record.',
        referencePlaceholder: 'ACLEDA transfer reference',
        notesPlaceholder: 'Optional payment note or sender name',
        badge: 'AC',
        visualClass: 'payment-brand-acleda',
        logoSrc: '/ac.png',
        showTransferPanel: true,
        accountName: 'E-Commerce ACLEDA Account',
        accountNumber: '091 002 884',
        channelLabel: 'ACLEDA transfer',
    },
    {
        value: 'wing',
        label: 'Wing',
        title: 'Wing wallet payment',
        text: 'Customer pays with Wing wallet.',
        description: 'Send the payment through Wing and record the wallet transaction reference.',
        instructions: 'Open Wing, send the amount to the wallet shown in this panel, then paste the Wing transaction reference before confirming the order.',
        referencePlaceholder: 'Wing transaction reference',
        notesPlaceholder: 'Optional sender phone or payment note',
        badge: 'WG',
        visualClass: 'payment-brand-wing',
        logoSrc: '/wing.png',
        showTransferPanel: true,
        accountName: 'E-Commerce Wing Wallet',
        accountNumber: '012 555 909',
        channelLabel: 'Wing wallet',
    },
    {
        value: 'card',
        label: 'Card',
        title: 'Card payment',
        text: 'Card payment is available when enabled.',
        description: 'Use this option when card payment is enabled for the order and the payment reference is captured manually.',
        instructions: 'Process the card charge using the enabled card terminal or gateway, then enter the transaction reference and cardholder note if needed.',
        referencePlaceholder: 'Card transaction reference',
        notesPlaceholder: 'Optional card holder or transaction note',
        badge: 'CD',
        visualClass: 'payment-brand-card',
        logoSrc: '/paypal.png',
        showTransferPanel: false,
        cashNote: 'Card payment is recorded as a manual reference in this flow. Use the transaction reference field on the right to capture the confirmation code.',
    },
];

const form = reactive({
    customer_name: '',
    email: '',
    phone: '',
    address_line1: '',
    address_line2: '',
    city: '',
    postal_code: '',
    notes: '',
    payment_reference: '',
    payment_notes: '',
});

const selectedPaymentMethod = computed(() => paymentMethods.find((method) => method.value === paymentMethod.value) ?? paymentMethods[0]);
const orderTotalLabel = computed(() => store.orderSummaryLines().total);

onMounted(async () => {
    await store.initialize();
    if (store.user) {
        form.customer_name = store.user.name || '';
        form.email = store.user.email || '';
        form.phone = store.user.phone || '';
    }
});

watch(showPaymentModal, (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);
});

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
});

function openPaymentModal() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        return false;
    }

    error.value = '';
    paymentModalStep.value = 'select';
    showPaymentModal.value = true;

    return true;
}

function closePaymentModal() {
    if (submitting.value) {
        return;
    }

    stopProcessingAnimation();
    paymentModalStep.value = 'select';
    showPaymentModal.value = false;
}

function goToPaymentDetail() {
    paymentModalStep.value = 'detail';
}

function startProcessingAnimation() {
    stopProcessingAnimation();
    processingStepIndex.value = 0;
    processingInterval = window.setInterval(() => {
        processingStepIndex.value = Math.min(processingStepIndex.value + 1, processingSteps.length - 1);
    }, 700);
}

function stopProcessingAnimation() {
    if (processingInterval) {
        window.clearInterval(processingInterval);
        processingInterval = null;
    }
}

function processingStepCardClass(index) {
    if (index < processingStepIndex.value) {
        return 'border-emerald-200 bg-emerald-50';
    }

    if (index === processingStepIndex.value) {
        return 'border-[#A25F88] bg-[rgba(162,95,136,0.08)]';
    }

    return 'border-[#E5E7EB] bg-white';
}

function processingStepIconClass(index) {
    if (index < processingStepIndex.value) {
        return 'bg-[#10B981] text-white';
    }

    if (index === processingStepIndex.value) {
        return 'bg-[rgba(162,95,136,0.12)] text-[#A25F88]';
    }

    return 'bg-[#F3F4F6] text-[#6B7280]';
}

async function viewPlacedOrder() {
    if (!latestOrderId.value) {
        return;
    }

    showPaymentModal.value = false;
    paymentModalStep.value = 'select';
    await router.push(`/order-success/${latestOrderId.value}`);
}

async function continueShopping() {
    showPaymentModal.value = false;
    paymentModalStep.value = 'select';
    await router.push('/shop');
}

function retryOrder() {
    paymentModalStep.value = 'detail';
}

async function confirmOrder() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        showPaymentModal.value = false;
        return;
    }

    error.value = '';
    submitting.value = true;
    modalErrorMessage.value = 'Unable to place this order.';
    paymentModalStep.value = 'processing';
    startProcessingAnimation();

    try {
        const response = await store.checkout({
            ...form,
            payment_method: paymentMethod.value,
        });

        stopProcessingAnimation();
        processingStepIndex.value = processingSteps.length - 1;
        latestOrderId.value = response.order.id;
        paymentModalStep.value = 'success';
    } catch (requestError) {
        stopProcessingAnimation();
        const message = requestError?.response?.data?.message
            || Object.values(requestError?.response?.data?.errors ?? {}).flat()?.[0]
            || 'Unable to place this order.';
        error.value = message;
        modalErrorMessage.value = message;
        paymentModalStep.value = 'error';
    } finally {
        submitting.value = false;
    }
}

onUnmounted(() => {
    stopProcessingAnimation();
});
</script>

<style scoped>
.payment-modal {
    width: min(92vw, 480px);
    max-width: 480px;
}

.payment-brand-mark {
    display: inline-flex;
    height: 2.75rem;
    width: 2.75rem;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    overflow: hidden;
}

.payment-brand-mark-compact {
    height: 2.75rem;
    width: 2.75rem;
    border-radius: 0.85rem;
    font-size: 0.72rem;
}

.payment-logo-image {
    height: 100%;
    width: 100%;
    object-fit: cover;
    display: block;
}

.processing-spinner {
    height: 3rem;
    width: 3rem;
    border-radius: 9999px;
    border: 3px solid rgba(162, 95, 136, 0.16);
    border-top-color: #a25f88;
    animation: spin 0.9s linear infinite;
}

.payment-detail-logo-wrap {
    display: flex;
    height: 3rem;
    width: 3rem;
    align-items: center;
    justify-content: center;
    margin-inline: auto;
    overflow: hidden;
    border-radius: 0.9rem;
    background: #ffffff;
    box-shadow: inset 0 0 0 1px #e5e7eb;
}

.payment-detail-logo {
    height: 100%;
    width: 100%;
    object-fit: cover;
    display: block;
}

.payment-brand-cash {
    background: rgba(162, 95, 136, 0.14);
    color: #a25f88;
}

.payment-brand-aba {
    background: linear-gradient(135deg, #0f5fa8 0%, #2f89d2 100%);
    color: #ffffff;
}

.payment-brand-acleda {
    background: linear-gradient(135deg, #1d4ed8 0%, #60a5fa 100%);
    color: #ffffff;
}

.payment-brand-wing {
    background: linear-gradient(135deg, #f97316 0%, #fb923c 100%);
    color: #ffffff;
}

.payment-brand-card {
    background: linear-gradient(135deg, #111827 0%, #374151 100%);
    color: #ffffff;
}

.field-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 0.9rem 1rem;
    color: #111827;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.12);
}

@media (min-width: 1024px) {
    .payment-modal {
        width: min(100%, 480px);
    }
}

.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 180ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
