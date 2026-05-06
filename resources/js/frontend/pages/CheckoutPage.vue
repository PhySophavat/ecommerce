<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Checkout page</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Complete shipping and payment</h1>
        </div>

        <div class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <section class="space-y-6">
                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
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

                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Payment method</h2>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <label v-for="method in paymentMethods" :key="method.value" class="flex cursor-pointer items-start gap-3 rounded-[24px] border p-4 transition" :class="paymentMethod === method.value ? 'border-[#A25F88] bg-[#F3E8F1]' : 'border-[#E5E7EB] bg-[#F8FAFC]'">
                            <input v-model="paymentMethod" :value="method.value" type="radio" class="mt-1 h-4 w-4 accent-[#A25F88]">
                            <div>
                                <div class="font-semibold text-[#111827]">{{ method.label }}</div>
                                <div class="mt-1 text-sm text-[#6B7280]">{{ method.text }}</div>
                            </div>
                        </label>
                    </div>

                    <div class="mt-5 rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] p-5">
                        <div class="text-sm font-semibold text-[#111827]">{{ selectedPaymentCopy.title }}</div>
                        <div class="mt-2 text-sm leading-7 text-[#6B7280]">{{ selectedPaymentCopy.description }}</div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <input
                                v-model.trim="form.payment_reference"
                                type="text"
                                class="field-input"
                                :placeholder="selectedPaymentCopy.referencePlaceholder"
                                :disabled="paymentMethod === 'cash'"
                            >
                            <textarea
                                v-model.trim="form.payment_notes"
                                rows="3"
                                class="field-input !rounded-[24px] sm:col-span-2"
                                :placeholder="selectedPaymentCopy.notesPlaceholder"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Order review</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in store.cartItems" :key="`${item.id}-${item.variant}`" class="flex items-center justify-between gap-4 rounded-[24px] bg-[#F8FAFC] px-4 py-4">
                            <div>
                                <div class="font-semibold text-[#111827]">{{ item.name }}</div>
                                <div class="mt-1 text-sm text-[#6B7280]">{{ item.merchant_name }} • {{ item.quantity }} qty</div>
                            </div>
                            <div class="text-right font-semibold text-[#111827]">${{ item.line_total.toFixed(2) }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="space-y-5 xl:sticky xl:top-28 xl:self-start">
                <OrderSummary title="Place order" :lines="store.orderSummaryLines()">
                    <div v-if="error" class="mb-4 rounded-[22px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
                    <Button block size="lg" :disabled="submitting || !store.cartItems.length" @click="placeOrder">
                        {{ submitting ? 'Placing order...' : 'Place order' }}
                    </Button>
                </OrderSummary>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import OrderSummary from '../components/OrderSummary.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const router = useRouter();
const submitting = ref(false);
const error = ref('');
const paymentMethod = ref('cash');
const paymentMethods = [
    { value: 'cash', label: 'Cash', text: 'Cash on delivery or manual settlement.' },
    { value: 'aba_qr', label: 'ABA QR', text: 'Customer pays with ABA QR transfer.' },
    { value: 'wing', label: 'Wing', text: 'Customer pays with Wing wallet.' },
    { value: 'card', label: 'Card', text: 'Card payment is available when enabled.' },
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

const selectedPaymentCopy = computed(() => ({
    cash: {
        title: 'Cash payment',
        description: 'The order is created immediately and stays unpaid until delivery or manual confirmation.',
        referencePlaceholder: 'No payment reference needed for cash',
        notesPlaceholder: 'Optional note for delivery or cash handling',
    },
    aba_qr: {
        title: 'ABA QR transfer',
        description: 'Complete the ABA payment, then enter the transfer reference so admin can verify it from the order dashboard.',
        referencePlaceholder: 'ABA transfer reference',
        notesPlaceholder: 'Optional payment note or sender name',
    },
    wing: {
        title: 'Wing wallet payment',
        description: 'Send the payment with Wing and provide the transaction reference for merchant or admin verification.',
        referencePlaceholder: 'Wing transaction reference',
        notesPlaceholder: 'Optional sender phone or payment note',
    },
    card: {
        title: 'Card payment',
        description: 'This demo flow stores the order and card payment reference for manual verification before it is marked paid.',
        referencePlaceholder: 'Card transaction reference',
        notesPlaceholder: 'Optional card holder or transaction note',
    },
}[paymentMethod.value]));

onMounted(async () => {
    await store.initialize();
    if (store.user) {
        form.customer_name = store.user.name || '';
        form.email = store.user.email || '';
        form.phone = store.user.phone || '';
    }
});

async function placeOrder() {
    if (!store.cartItems.length) {
        error.value = 'Your cart is empty.';
        return;
    }

    error.value = '';
    submitting.value = true;

    try {
        const response = await store.checkout({
            ...form,
            payment_method: paymentMethod.value,
        });

        await router.push(`/order-success/${response.order.id}`);
    } catch (requestError) {
        error.value = requestError?.response?.data?.message
            || Object.values(requestError?.response?.data?.errors ?? {}).flat()?.[0]
            || 'Unable to place this order.';
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.field-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 0.9rem 1rem;
    color: #111827;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.1);
}
</style>
