<template>
    <div class="min-h-full bg-[#F8FAFC] px-4 py-6 sm:px-6 sm:py-8">
        <div class="mx-auto w-full max-w-[760px]">
            <div class="rounded-[28px] border border-[#E5E7EB] bg-[#FFFFFF] p-6 shadow-[0_18px_40px_rgba(15,23,42,0.08)] sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Payment verification</p>
                        <h1 class="mt-2 text-[1.9rem] font-black tracking-[-0.04em] text-[#111827]">{{ pageTitle }}</h1>
                        <p class="mt-2 text-sm text-[#6B7280]">{{ pageMessage }}</p>
                    </div>
                    <RouterLink
                        to="/orders"
                        class="inline-flex min-h-[42px] items-center justify-center rounded-full border border-[#E5E7EB] bg-white px-4 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                    >
                        My orders
                    </RouterLink>
                </div>

                <div v-if="errorMessage" class="mt-5 rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ errorMessage }}
                </div>

                <div v-if="order" class="mt-6 grid gap-5 lg:grid-cols-[1.05fr_0.95fr]">
                    <section class="space-y-5">
                        <div class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#6B7280]">Order number</p>
                                    <p class="mt-1 text-lg font-black tracking-[-0.03em] text-[#111827]">{{ order.order_code }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em]" :class="statusBadgeClass">
                                    {{ statusBadgeLabel }}
                                </span>
                            </div>

                            <div class="mt-5 rounded-[18px] bg-[#F8FAFC] p-4">
                                <div class="flex items-center justify-between gap-4 rounded-[14px] bg-[#FFFFFF] px-4 py-3">
                                    <span class="text-sm font-semibold text-[#6B7280]">Amount</span>
                                    <span class="text-lg font-black text-[#A25F88]">${{ payableAmountLabel }}</span>
                                </div>

                                <div class="qr-crop-shell mx-auto mt-4">
                                    <img :src="checkout.qr_image_url || '/khqr.jpg'" alt="Payment QR code" class="qr-crop-image">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <h2 class="text-lg font-bold text-[#111827]">Transfer details</h2>
                            <div class="mt-4 space-y-2 text-sm text-[#111827]">
                                <p><span class="font-semibold text-[#6B7280]">Account:</span> {{ checkout.account_name || 'E-Commerce payment account' }}</p>
                                <p><span class="font-semibold text-[#6B7280]">Number:</span> {{ checkout.account_number || 'N/A' }}</p>
                                <p><span class="font-semibold text-[#6B7280]">Method:</span> {{ paymentMethodLabel }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-5">
                        <div v-if="submitting" class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-6 text-center shadow-sm">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[rgba(162,95,136,0.1)]">
                                <div class="loading-ring"></div>
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-[#111827]">Checking your payment screenshot...</h2>
                            <p class="mt-2 text-sm leading-6 text-[#6B7280]">Please wait while OCR verifies your screenshot details.</p>
                        </div>

                        <div v-else-if="showVerifiedState" class="rounded-[24px] border border-emerald-200 bg-emerald-50 p-5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.2 4.2L19 6.5" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h2 class="text-xl font-bold text-emerald-700">Payment verified successfully</h2>
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700">Processing</span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-emerald-700">Your payment screenshot is correct. Your order is now being processed.</p>
                                </div>
                            </div>
                            <div class="mt-5 rounded-[18px] bg-white p-4">
                                <p class="text-sm font-semibold text-[#111827]">Verification checklist</p>
                                <div class="mt-3 space-y-2">
                                    <div v-for="item in verificationChecklist" :key="item.label" class="flex items-center justify-between gap-3 rounded-[14px] bg-[#F8FAFC] px-3 py-2">
                                        <span class="text-sm text-[#111827]">{{ item.label }}</span>
                                        <span class="text-sm font-semibold text-emerald-700">{{ item.successLabel }}</span>
                                    </div>
                                </div>
                            </div>
                            <RouterLink
                                :to="`/orders/${id}`"
                                class="mt-5 inline-flex min-h-[46px] items-center justify-center rounded-[14px] bg-[#A25F88] px-5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                            >
                                View My Order
                            </RouterLink>
                        </div>

                        <div v-else-if="showFailedState" class="rounded-[24px] border border-rose-200 bg-rose-50 p-5">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M4.93 19h14.14c.77 0 1.25-.83.87-1.5L12.87 4.5a1 1 0 0 0-1.74 0L4.06 17.5c-.38.67.1 1.5.87 1.5Z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-xl font-bold text-rose-700">Payment verification failed</h2>
                                    <p class="mt-2 text-sm leading-6 text-rose-700">Your payment screenshot could not be verified. Please upload the correct screenshot again.</p>
                                </div>
                            </div>
                            <div class="mt-4 rounded-[18px] bg-white p-4">
                                <p class="text-sm font-semibold text-[#111827]">Failed reasons</p>
                                <ul class="mt-3 space-y-2 text-sm text-rose-700">
                                    <li v-for="reason in failedReasons" :key="reason">{{ reason }}</li>
                                </ul>
                            </div>
                            <button
                                type="button"
                                class="mt-5 inline-flex min-h-[46px] items-center justify-center rounded-[14px] bg-[#A25F88] px-5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                                @click="scrollToUpload"
                            >
                                Upload Again
                            </button>
                        </div>

                        <div v-if="payment?.auto_check_result" class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <h2 class="text-lg font-bold text-[#111827]">Verification result</h2>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[18px] bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Amount match</p>
                                    <p class="mt-2 text-sm font-semibold" :class="booleanClass(payment?.auto_check_result?.amount_match)">{{ successLabel(payment?.auto_check_result?.amount_match, 'Yes', 'No') }}</p>
                                </div>
                                <div class="rounded-[18px] bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">OCR engine</p>
                                    <p class="mt-2 text-sm font-semibold" :class="booleanClass(ocrAvailable)">{{ successLabel(ocrAvailable, 'Available', 'Unavailable') }}</p>
                                    <p v-if="ocrError" class="mt-2 text-sm text-rose-700">{{ ocrError }}</p>
                                </div>
                            </div>
                            <div class="mt-4 rounded-[18px] bg-[#F8FAFC] p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Score</p>
                                    <p class="text-lg font-black text-[#111827]">{{ payment?.auto_check_score ?? 0 }}%</p>
                                </div>
                                <p class="mt-2 text-sm text-[#6B7280]">
                                    {{ showVerifiedState ? 'OCR matched the required payment details.' : (ocrAvailable ? 'The screenshot did not pass OCR verification yet.' : 'OCR could not run on the server for this screenshot.') }}
                                </p>
                            </div>
                        </div>

                        <div v-if="canSubmitProof" ref="uploadSection" class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <h2 class="text-lg font-bold text-[#111827]">{{ showFailedState ? 'Upload Again' : 'Upload Payment Proof' }}</h2>
                            <div class="mt-4 rounded-[18px] bg-[#F8FAFC] p-4 text-sm text-[#111827]">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold text-[#6B7280]">Order</span>
                                    <span class="font-semibold">{{ order.order_code }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <span class="font-semibold text-[#6B7280]">Method</span>
                                    <span class="font-semibold">{{ paymentMethodLabel }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <span class="font-semibold text-[#6B7280]">Amount</span>
                                    <span class="font-semibold text-[#A25F88]">${{ payableAmountLabel }}</span>
                                </div>
                            </div>
                            <div class="mt-4 space-y-4">
                                <input
                                    v-model.trim="form.transaction_ref"
                                    type="text"
                                    class="field-input"
                                    placeholder="Transaction reference"
                                >

                                <input
                                    v-model.trim="form.payment_note"
                                    type="text"
                                    class="field-input"
                                    placeholder="Optional sender name or payment note"
                                >

                                <label class="flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-[18px] border border-dashed border-[#A25F88] bg-[#F8FAFC] px-4 py-5 text-center">
                                    <img v-if="screenshotPreview" :src="screenshotPreview" alt="Payment proof preview" class="mb-3 h-28 w-auto rounded-[14px] object-cover shadow-sm">
                                    <span class="text-sm font-semibold text-[#111827]">{{ screenshotName || 'Choose payment screenshot' }}</span>
                                    <span class="mt-2 text-xs text-[#6B7280]">JPG, PNG, WEBP up to 4MB</span>
                                    <input type="file" accept="image/*" class="sr-only" @change="handleScreenshotChange">
                                </label>

                                <button
                                    type="button"
                                    class="inline-flex min-h-[48px] w-full items-center justify-center rounded-[14px] bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#8E4F76] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="submitting || !form.screenshot"
                                    @click="submitProof"
                                >
                                    {{ submitting ? 'Verifying...' : 'Submit Payment Proof' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="payment?.ocr_text" class="rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <h2 class="text-lg font-bold text-[#111827]">OCR extracted text</h2>
                            <pre class="mt-4 max-h-[240px] overflow-auto whitespace-pre-wrap rounded-[18px] bg-[#F8FAFC] p-4 text-sm leading-6 text-[#111827]">{{ payment.ocr_text }}</pre>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useRouter } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const props = defineProps({
    id: {
        type: [String, Number],
        required: true,
    },
});

const store = useStorefrontStore();
const router = useRouter();
const order = ref(null);
const payment = ref(null);
const checkout = ref({});
const submitting = ref(false);
const errorMessage = ref('');
const screenshotName = ref('');
const screenshotPreview = ref('');
const uploadSection = ref(null);
const verifiedRedirectTimer = ref(null);
const form = ref({
    transaction_ref: '',
    payment_note: '',
    screenshot: null,
});

const paymentMethodLabel = computed(() => {
    return {
        cash: 'Cash',
        aba_qr: 'ABA QR',
        acleda: 'ACLEDA',
        wing: 'Wing',
        card: 'Card',
    }[order.value?.payment_method] ?? 'Payment';
});

const payableAmountLabel = computed(() => checkout.value.pay_amount || payment.value?.amount || order.value?.total_amount || '0.00');
const showVerifiedState = computed(() => payment.value?.status === 'approved' && payment.value?.auto_check_status === 'auto_verified');
const showFailedState = computed(() => payment.value?.status === 'auto_failed' || payment.value?.auto_check_status === 'auto_failed');
const canSubmitProof = computed(() => order.value?.payment_type === 'manual_transfer' && ['pending', 'submitted', 'auto_failed', 'rejected', 'failed'].includes(payment.value?.status || 'pending'));
const failedReasons = computed(() => payment.value?.auto_check_result?.reasons ?? []);
const ocrAvailable = computed(() => payment.value?.auto_check_result?.ocr_available !== false);
const ocrError = computed(() => payment.value?.auto_check_result?.ocr_error || '');
const verificationChecklist = computed(() => [
    {
        label: 'Amount Match',
        value: payment.value?.auto_check_result?.amount_match,
        successLabel: successLabel(payment.value?.auto_check_result?.amount_match, 'Yes', 'No'),
    },
]);
const statusBadgeLabel = computed(() => {
    if (showVerifiedState.value) {
        return 'Processing';
    }

    if (showFailedState.value) {
        return 'Failed';
    }

    return String(payment.value?.status || order.value?.payment_status || 'pending').replaceAll('_', ' ');
});
const statusBadgeClass = computed(() => {
    if (showVerifiedState.value) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (showFailedState.value) {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-slate-100 text-slate-700';
});
const pageTitle = computed(() => {
    if (showVerifiedState.value) {
        return 'Payment verified successfully';
    }

    if (showFailedState.value) {
        return 'Payment verification failed';
    }

    return 'Submit payment proof';
});
const pageMessage = computed(() => {
    if (showVerifiedState.value) {
        return 'Your payment screenshot has been verified. Your order is now being processed.';
    }

    if (showFailedState.value) {
        return 'We could not verify your payment screenshot. Please upload a clear and correct screenshot.';
    }

    return 'Upload your payment screenshot for automatic OCR verification.';
});

onMounted(async () => {
    await initializePage();
});

watch(showVerifiedState, (verified) => {
    clearVerifiedRedirect();

    if (!verified || !order.value?.id) {
        return;
    }

    verifiedRedirectTimer.value = window.setTimeout(() => {
        router.push(`/orders/${order.value.id}`);
    }, 1800);
});

async function initializePage() {
    try {
        if (!store.initialized) {
            await store.initialize();
        }

        const response = await store.fetchPaymentStatus(props.id);
        order.value = response.order;
        payment.value = response.payment;
        checkout.value = response.checkout ?? {};
        form.value.transaction_ref = response.payment?.transaction_ref || '';
        form.value.payment_note = '';
    } catch (error) {
        errorMessage.value = extractMessage(error, 'Unable to load this payment page.');
    }
}

function handleScreenshotChange(event) {
    const [file] = event.target.files ?? [];
    revokePreview();
    form.value.screenshot = file ?? null;
    screenshotName.value = file?.name ?? '';
    screenshotPreview.value = file ? URL.createObjectURL(file) : '';
}

async function submitProof() {
    if (!form.value.screenshot) {
        return;
    }

    submitting.value = true;
    errorMessage.value = '';

    try {
        const response = await store.submitPaymentProof(props.id, form.value);
        order.value = response.order;
        payment.value = response.payment;
        form.value.transaction_ref = response.payment?.transaction_ref || form.value.transaction_ref;
        form.value.payment_note = '';
        form.value.screenshot = null;
        screenshotName.value = '';
        revokePreview();
    } catch (error) {
        errorMessage.value = extractMessage(error, 'Unable to submit payment proof.');
    } finally {
        submitting.value = false;
    }
}

async function scrollToUpload() {
    await nextTick();
    uploadSection.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function revokePreview() {
    if (screenshotPreview.value) {
        URL.revokeObjectURL(screenshotPreview.value);
        screenshotPreview.value = '';
    }
}

function clearVerifiedRedirect() {
    if (verifiedRedirectTimer.value) {
        window.clearTimeout(verifiedRedirectTimer.value);
        verifiedRedirectTimer.value = null;
    }
}

function booleanClass(value) {
    return value ? 'text-emerald-700' : 'text-rose-700';
}

function successLabel(value, positive, negative) {
    return value ? positive : negative;
}

function extractMessage(error, fallback) {
    return error?.response?.data?.message
        || Object.values(error?.response?.data?.errors ?? {}).flat()?.[0]
        || fallback;
}

onBeforeUnmount(() => {
    clearVerifiedRedirect();
    revokePreview();
});
</script>

<style scoped>
.field-input {
    width: 100%;
    min-height: 3.25rem;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    color: #111827;
    outline: none;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.1);
}

.qr-crop-shell {
    width: 210px;
    max-width: 100%;
    overflow: hidden;
    border-radius: 20px;
    background: #fff;
}

.qr-crop-image {
    display: block;
    width: 100%;
    height: auto;
    object-fit: cover;
}

.loading-ring {
    height: 2rem;
    width: 2rem;
    border-radius: 9999px;
    border: 3px solid rgba(162, 95, 136, 0.18);
    border-top-color: #a25f88;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
