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
                                <div class="mt-1 text-sm text-[#6B7280]">{{ item.variant }}</div>
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
                <div class="payment-modal relative z-10 flex max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white shadow-[0_20px_50px_rgba(15,23,42,0.16)]">
                    <div
                        class="flex justify-between gap-4 border-b border-[#F3F4F6] px-5 pb-4 pt-5 sm:px-6"
                        :class="['detail', 'proof'].includes(paymentModalStep) ? 'items-center' : 'items-start'"
                    >
                        <template v-if="paymentModalStep === 'select'">
                            <div>
                                <h2 class="text-lg font-semibold tracking-[-0.02em] text-[#111827]">Payment method</h2>
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

                        <template v-else-if="paymentModalStep === 'proof'">
                            <button
                                type="button"
                                class="inline-flex min-h-[34px] items-center justify-center rounded-[10px] border border-[#E5E7EB] bg-white px-3 text-sm font-medium text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                                @click="paymentModalStep = 'detail'"
                            >
                                Back
                            </button>
                            <div class="min-w-0 flex-1 px-2 text-center">
                                <h2 class="truncate text-base font-semibold tracking-[-0.02em] text-[#111827]">Upload Payment Proof</h2>
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

                    <div v-if="paymentModalStep === 'select'" class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-4 sm:px-6">
                        <div class="space-y-2.5">
                            <label
                                v-for="method in paymentMethods"
                                :key="method.value"
                                class="flex w-full gap-3 rounded-[14px] border px-3.5 py-3 transition duration-200"
                                :class="paymentMethodCardClass(method)"
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
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-sm font-semibold" :class="paymentMethod === method.value ? 'text-[#A25F88]' : 'text-[#111827]'">
                                            {{ method.label }}
                                        </div>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em]" :class="paymentMethodBadgeClass(method)">
                                            {{ method.badgeText }}
                                        </span>
                                    </div>
                                    <p v-if="method.description" class="mt-1 text-xs leading-5 text-[#6B7280]">{{ method.description }}</p>
                                </div>
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition"
                                    :class="paymentMethod === method.value ? 'border-[#A25F88] text-[#A25F88]' : 'border-[#D1D5DB] text-transparent'"
                                >
                                    <span class="text-[11px] leading-none">&#9679;</span>
                                </div>
                                <input v-model="paymentMethod" :value="method.value" type="radio" class="sr-only" :disabled="!method.enabled">
                            </label>
                        </div>

                        <div class="mt-5 grid gap-3 border-t border-[#F3F4F6] pt-4">
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

                    <div v-else-if="paymentModalStep === 'detail'" class="min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-4 sm:px-6">
                        <div class="space-y-4">
                            <div v-if="selectedPaymentMethod.paymentType === 'manual_transfer'" class="rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC] p-4">
                                <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Pay Amount</p>
                                    <p class="mt-1 text-[1.75rem] font-black tracking-[-0.04em] text-[#A25F88]">{{ orderTotalLabel }}</p>
                                </div>

                                <div class="mt-4 text-center">
                                    <div class="payment-brand-mark payment-brand-mark-compact mx-auto" :class="selectedPaymentMethod.visualClass">
                                        <img
                                            v-if="selectedPaymentMethod.logoSrc"
                                            :src="selectedPaymentMethod.logoSrc"
                                            :alt="`${selectedPaymentMethod.label} logo`"
                                            class="payment-logo-image"
                                        >
                                        <span v-else>{{ selectedPaymentMethod.badge }}</span>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold uppercase tracking-[0.14em] text-[#A25F88]">Scan to Pay</p>
                                    <p class="mt-1 text-sm text-[#6B7280]">Open your banking app and scan this QR.</p>
                                </div>

                                <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-white p-4">
                                    <div class="qr-crop-shell qr-crop-shell-large mx-auto">
                                        <img src="/khqr.jpg" alt="Payment QR code" class="qr-crop-image">
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827]">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="font-medium text-[#6B7280]">Account</span>
                                        <span class="text-right font-semibold">{{ selectedPaymentMethod.accountName || 'Manual payment' }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-4">
                                        <span class="font-medium text-[#6B7280]">Number</span>
                                        <span class="text-right font-semibold">{{ selectedPaymentMethod.accountNumber || 'Reference required' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC] p-4 text-sm leading-6 text-[#6B7280]">
                                {{ selectedPaymentMethod.cashNote }}
                            </div>
                        </div>
                    </div>

                    <div v-else-if="paymentModalStep === 'proof'" class="min-h-0 flex-1 overflow-y-auto px-5 pb-28 pt-4 sm:px-6">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC] p-4">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Order number</p>
                                        <p class="mt-1 text-sm font-semibold text-[#111827]">{{ manualTransferOrder?.order_code || 'Pending...' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Method</p>
                                        <p class="mt-1 text-sm font-semibold text-[#111827]">{{ selectedPaymentMethod.label }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Amount</p>
                                        <p class="mt-1 text-sm font-semibold text-[#A25F88]">{{ orderTotalLabel }}</p>
                                    </div>
                                </div>
                            </div>

                            <label class="flex min-h-[180px] cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-[#A25F88] bg-[#F8FAFC] px-4 py-5 text-center">
                                <span class="text-sm font-semibold text-[#111827]">{{ paymentScreenshotName || 'Choose payment screenshot' }}</span>
                                <span class="mt-2 text-xs text-[#6B7280]">JPG, PNG, WEBP up to 4MB</span>
                                <input type="file" accept="image/*" class="sr-only" @change="handleScreenshotChange">
                            </label>

                            <div v-if="paymentScreenshotPreview" class="overflow-hidden rounded-2xl border border-[#E5E7EB] bg-white">
                                <img :src="paymentScreenshotPreview" alt="Payment proof preview" class="h-auto max-h-[240px] w-full object-contain bg-[#F8FAFC]">
                            </div>

                            <textarea
                                v-model.trim="form.payment_notes"
                                rows="3"
                                class="field-input !rounded-[14px]"
                                placeholder="Optional sender name or payment note"
                            ></textarea>
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
                        <h3 class="mt-5 text-xl font-semibold text-[#111827]">{{ successTitle }}</h3>
                        <p class="mt-2 text-sm leading-6 text-[#6B7280]">{{ successMessage }}</p>
                        <div class="mt-6 grid gap-3">
                            <button
                                type="button"
                                class="inline-flex min-h-[44px] items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                                @click="primarySuccessAction"
                            >
                                {{ successPrimaryLabel }}
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

                    <div v-if="['detail', 'proof'].includes(paymentModalStep)" class="border-t border-[#F3F4F6] bg-white px-5 pb-5 pt-4 sm:px-6">
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] w-full items-center justify-center rounded-[12px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76] disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitButtonDisabled"
                            @click="handleDetailFooterAction"
                        >
                            {{ submitting ? submitButtonBusyLabel : submitButtonLabel }}
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
const manualTransferOrder = ref(null);
const modalErrorMessage = ref('Unable to place this order.');
const processingStepIndex = ref(0);
const paymentMethod = ref('cash');
const paymentScreenshotName = ref('');
const paymentScreenshotFile = ref(null);
const paymentScreenshotPreview = ref('');
let processingInterval = null;
const processingSteps = [
    'Checking payment information...',
    'Creating your order...',
    'Updating product stock...',
    'Finalizing order...',
];
const gatewayEnabled = computed(() => Boolean(store.meta?.payment_gateway_enabled));
const paymentMethodDefinitions = [
    {
        value: 'cash',
        label: 'Cash',
        title: 'Cash payment',
        text: 'Cash on delivery.',
        description: 'Pay when delivered.',
        instructions: 'No transfer needed. Pay when the order arrives.',
        notesPlaceholder: 'Optional note for delivery or cash handling',
        badge: 'CA',
        visualClass: 'payment-brand-cash',
        logoSrc: '/cash.jpg',
        paymentType: 'cash',
        cashNote: 'No transfer slip needed.',
    },
    {
        value: 'aba_qr',
        label: 'ABA QR',
        title: 'ABA QR transfer',
        text: 'ABA transfer.',
        description: 'Scan QR and enter reference.',
        instructions: 'Pay with ABA, then enter the transfer reference.',
        notesPlaceholder: 'Optional payment note or sender name',
        badge: 'ABA',
        visualClass: 'payment-brand-aba',
        logoSrc: '/aba.png',
        paymentType: 'manual_transfer',
        accountName: 'E-Commerce ABA Collection',
        accountNumber: '001 234 567',
        channelLabel: 'ABA QR / Bank transfer',
    },
    {
        value: 'acleda',
        label: 'ACLEDA',
        title: 'ACLEDA transfer',
        text: 'ACLEDA transfer.',
        description: 'Transfer and enter reference.',
        instructions: 'Pay with ACLEDA, then enter the transfer reference.',
        notesPlaceholder: 'Optional payment note or sender name',
        badge: 'AC',
        visualClass: 'payment-brand-acleda',
        logoSrc: '/ac.png',
        paymentType: 'manual_transfer',
        accountName: 'E-Commerce ACLEDA Account',
        accountNumber: '091 002 884',
        channelLabel: 'ACLEDA transfer',
    },
    {
        value: 'wing',
        label: 'Wing',
        title: 'Wing wallet payment',
        text: 'Wing payment.',
        description: 'Send payment and enter reference.',
        instructions: 'Pay with Wing, then enter the transaction reference.',
        notesPlaceholder: 'Optional sender phone or payment note',
        badge: 'WG',
        visualClass: 'payment-brand-wing',
        logoSrc: '/wing.png',
        paymentType: 'manual_transfer',
        accountName: 'E-Commerce Wing Wallet',
        accountNumber: '012 555 909',
        channelLabel: 'Wing wallet',
    },
    {
        value: 'card',
        label: 'Card',
        title: 'Card payment',
        text: 'Card payment.',
        description: 'Available when enabled.',
        instructions: 'Use card payment when the gateway is enabled.',
        notesPlaceholder: 'Optional card holder or transaction note',
        badge: 'CD',
        visualClass: 'payment-brand-card',
        logoSrc: '/paypal.png',
        paymentType: 'gateway',
        cashNote: 'Card payment only becomes available after a real gateway is connected and configured.',
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

const paymentMethods = computed(() => paymentMethodDefinitions.map((method) => {
    const isEnabled = method.paymentType !== 'gateway' || gatewayEnabled.value;

    return {
        ...method,
        enabled: isEnabled,
        badgeText: method.paymentType === 'cash'
            ? 'Pay later'
            : method.paymentType === 'manual_transfer'
                ? 'Manual verification'
                : (isEnabled ? 'Gateway ready' : 'Coming soon'),
    };
}));
const selectedPaymentMethod = computed(() => paymentMethods.value.find((method) => method.value === paymentMethod.value) ?? paymentMethods.value[0]);
const orderTotalLabel = computed(() => store.orderSummaryLines().total);
const isManualTransferMethod = computed(() => selectedPaymentMethod.value?.paymentType === 'manual_transfer');
const submitButtonLabel = computed(() => {
    if (paymentModalStep.value === 'detail' && isManualTransferMethod.value) {
        return 'I have paid, continue';
    }

    if (paymentModalStep.value === 'proof') {
        return 'Submit Payment Proof';
    }

    if (selectedPaymentMethod.value?.paymentType === 'gateway') {
        return 'Confirm order';
    }

    return 'Place order';
});
const submitButtonBusyLabel = computed(() => {
    if (paymentModalStep.value === 'proof') {
        return 'Submitting proof...';
    }

    return 'Confirming...';
});
const submitButtonDisabled = computed(() => {
    if (submitting.value) {
        return true;
    }

    if (paymentModalStep.value === 'proof') {
        return !paymentScreenshotFile.value;
    }

    return false;
});
const successTitle = computed(() => isManualTransferMethod.value ? 'Payment proof submitted' : 'Order placed successfully');
const successMessage = computed(() => isManualTransferMethod.value
    ? 'Payment proof submitted. Admin will verify your payment soon.'
    : 'Your order has been submitted and is now being processed.');
const successPrimaryLabel = computed(() => isManualTransferMethod.value ? 'View order' : 'View order');

onMounted(async () => {
    await store.initialize();

    if (!store.cartItems.length) {
        await router.replace('/cart');
        return;
    }

    if (store.user) {
        form.customer_name = store.user.name || '';
        form.email = store.user.email || '';
        form.phone = store.user.phone || '';
    }
});

watch(showPaymentModal, (isOpen) => {
    document.body.classList.toggle('overflow-hidden', isOpen);
});

watch(paymentMethods, (methods) => {
    if (!methods.find((method) => method.value === paymentMethod.value && method.enabled)) {
        paymentMethod.value = methods.find((method) => method.enabled)?.value ?? 'cash';
    }
}, { immediate: true });

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
});

function openPaymentModal() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        router.push('/cart');
        return false;
    }

    error.value = '';
    paymentModalStep.value = 'select';
    paymentMethod.value = paymentMethods.value.find((method) => method.enabled)?.value ?? 'cash';
    manualTransferOrder.value = null;
    paymentScreenshotName.value = '';
    paymentScreenshotFile.value = null;
    paymentScreenshotPreview.value = '';
    form.payment_notes = '';
    showPaymentModal.value = true;

    return true;
}

function closePaymentModal() {
    if (submitting.value) {
        return;
    }

    stopProcessingAnimation();
    paymentModalStep.value = 'select';
    manualTransferOrder.value = null;
    paymentScreenshotName.value = '';
    paymentScreenshotFile.value = null;
    paymentScreenshotPreview.value = '';
    showPaymentModal.value = false;
}

function goToPaymentDetail() {
    if (!selectedPaymentMethod.value?.enabled) {
        return;
    }

    paymentModalStep.value = 'detail';
}

function handleScreenshotChange(event) {
    const [file] = event.target.files ?? [];
    paymentScreenshotFile.value = file ?? null;
    paymentScreenshotName.value = file?.name ?? '';
    paymentScreenshotPreview.value = file ? URL.createObjectURL(file) : '';
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
    paymentModalStep.value = isManualTransferMethod.value ? 'proof' : 'detail';
}

function paymentMethodCardClass(method) {
    if (!method.enabled) {
        return 'cursor-not-allowed border-[#E5E7EB] bg-[#F8FAFC] opacity-65';
    }

    return paymentMethod.value === method.value
        ? 'cursor-pointer border-[#A25F88] bg-[rgba(162,95,136,0.08)]'
        : 'cursor-pointer border-[#E5E7EB] bg-white hover:bg-[rgba(162,95,136,0.05)]';
}

function paymentMethodBadgeClass(method) {
    if (!method.enabled) {
        return 'bg-slate-200 text-slate-600';
    }

    return method.paymentType === 'gateway'
        ? 'bg-[#FDF2F8] text-[#A25F88]'
        : 'bg-[#EEF2FF] text-[#6366F1]';
}

async function handleDetailFooterAction() {
    if (paymentModalStep.value === 'detail' && isManualTransferMethod.value) {
        if (manualTransferOrder.value?.id) {
            paymentModalStep.value = 'proof';
            return;
        }

        await createManualTransferOrder();
        return;
    }

    await confirmOrder();
}

async function createManualTransferOrder() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        showPaymentModal.value = false;
        await router.push('/cart');
        return;
    }

    error.value = '';
    submitting.value = true;
    modalErrorMessage.value = 'Unable to create this order.';
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
        manualTransferOrder.value = response.order;
        showPaymentModal.value = false;
        paymentModalStep.value = 'select';
        await router.push(`/payment/${response.order.id}`);
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

async function confirmOrder() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        showPaymentModal.value = false;
        await router.push('/cart');
        return;
    }

    if (paymentModalStep.value === 'proof' && manualTransferOrder.value?.id) {
        await submitManualTransferProof();
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

        if (paymentMethod.value === 'cash') {
            paymentModalStep.value = 'success';
            return;
        }

        showPaymentModal.value = false;
        paymentModalStep.value = 'select';
        await router.push(`/payment/${response.order.id}`);
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

async function submitManualTransferProof() {
    if (!manualTransferOrder.value?.id || !paymentScreenshotFile.value) {
        return;
    }

    error.value = '';
    submitting.value = true;
    modalErrorMessage.value = 'Unable to submit payment proof.';

    try {
        await store.submitPaymentProof(manualTransferOrder.value.id, {
            screenshot: paymentScreenshotFile.value,
            transaction_ref: '',
            payment_note: form.payment_notes,
        });
        paymentModalStep.value = 'success';
    } catch (requestError) {
        const message = requestError?.response?.data?.message
            || Object.values(requestError?.response?.data?.errors ?? {}).flat()?.[0]
            || 'Unable to submit payment proof.';
        error.value = message;
        modalErrorMessage.value = message;
        paymentModalStep.value = 'error';
    } finally {
        submitting.value = false;
    }
}

async function primarySuccessAction() {
    await viewPlacedOrder();
}

onUnmounted(() => {
    stopProcessingAnimation();
    if (paymentScreenshotPreview.value) {
        URL.revokeObjectURL(paymentScreenshotPreview.value);
    }
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

.qr-crop-shell {
    height: 7.5rem;
    width: 7.5rem;
    overflow: hidden;
    border-radius: 1.1rem;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
}

.qr-crop-shell-large {
    height: 12.5rem;
    width: 12.5rem;
}

.qr-crop-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 64%;
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
