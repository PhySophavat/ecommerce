<template>
    <div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-2xl border border-[#ead9e3] bg-[linear-gradient(180deg,#fff6fb_0%,#ffffff_100%)] p-5 shadow-[0_18px_48px_rgba(162,95,136,0.12)] sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">KHQR Preview</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Dynamic merchant top-up QR</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    Switch bank provider or amount on the right and this KHQR preview updates instantly for manual proof verification.
                </p>

                <div class="mt-6 rounded-2xl border border-[#f0d9e6] bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start">
                        <div class="mx-auto w-full max-w-[260px] rounded-[28px] bg-[#fcf7fa] p-4">
                            <img
                                :src="preview.imageUrl"
                                alt="KHQR preview"
                                class="mx-auto h-[228px] w-[228px] rounded-[24px] border border-[#f0d9e6] bg-white object-cover"
                            >
                        </div>

                        <div class="min-w-0 flex-1 space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Selected Bank</p>
                                    <p class="mt-2 text-sm font-bold text-slate-950">{{ preview.bankName }}</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Merchant / Shop</p>
                                    <p class="mt-2 text-sm font-bold text-slate-950">{{ preview.shopName }}</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Deposit Amount</p>
                                    <p class="mt-2 text-sm font-bold text-slate-950">{{ currency(preview.amount) }}</p>
                                </article>
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Receiver Account</p>
                                    <p class="mt-2 text-sm font-bold text-slate-950">{{ preview.provider.account_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ preview.provider.account_number }}</p>
                                </article>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">KHQR Code Text</p>
                                <p class="mt-2 break-all text-sm leading-6 text-slate-700">{{ preview.khqrCode }}</p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    class="rounded-2xl bg-[#A25F88] px-4 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                                    @click="copyKhqr"
                                >
                                    Copy KHQR Code
                                </button>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-[#dcb8cc] bg-white px-4 py-3 text-sm font-semibold text-[#A25F88] transition hover:bg-[#fcf7fa]"
                                    @click="downloadQr"
                                >
                                    Download QR Image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.08)] sm:p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Deposit Form</p>
                <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Submit manual deposit proof</h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    Fill in your sending account details, upload the payment screenshot, and the request will be created with pending status for admin review.
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <label class="block space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Amount</span>
                        <input v-model="form.amount" type="number" step="0.01" min="0.01" class="field-input" placeholder="25.00">
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Bank / Payment Provider</span>
                        <select v-model="form.bank_name" class="field-input">
                            <option v-for="provider in providers" :key="provider.bank_name" :value="provider.bank_name">
                                {{ provider.bank_name }}
                            </option>
                        </select>
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Account Name</span>
                            <input v-model="form.account_name" type="text" class="field-input" placeholder="Your bank account name">
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-semibold text-slate-700">Account Number</span>
                            <input v-model="form.account_number" type="text" class="field-input" placeholder="Your bank account number">
                        </label>
                    </div>

                    <label class="block space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Phone Number</span>
                        <input v-model="form.phone_number" type="text" class="field-input" placeholder="088 123 4567">
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Upload Payment Screenshot</span>
                        <input type="file" accept="image/*" class="field-input file:mr-4 file:rounded-xl file:border-0 file:bg-[#A25F88] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" @change="handleFileChange">
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-semibold text-slate-700">Optional Note</span>
                        <textarea v-model="form.note" rows="4" class="field-input resize-none" placeholder="Reference or payment note"></textarea>
                    </label>

                    <button type="submit" class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60" :disabled="isSubmitting || !form.file">
                        {{ isSubmitting ? 'Submitting deposit...' : 'Submit Deposit Request' }}
                    </button>
                </form>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-bold text-slate-950">Deposit History</h3>
            </div>
            <div v-if="deposits.length === 0" class="px-5 py-8 text-center text-sm text-slate-500">No deposit requests yet.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <tr>
                            <th class="px-4 py-4">Bank</th>
                            <th class="px-4 py-4">Amount</th>
                            <th class="px-4 py-4">Sender</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Proof</th>
                            <th class="px-4 py-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in deposits" :key="item.id" class="border-t border-slate-200">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-950">{{ item.bank_name }}</p>
                                <p class="text-slate-500">{{ item.account_number }}</p>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-950">{{ currency(item.amount) }}</td>
                            <td class="px-4 py-4 text-slate-600">
                                <p>{{ item.account_name }}</p>
                                <p>{{ item.phone_number }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="statusClass(item.status)">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <a v-if="item.payment_proof_url" :href="item.payment_proof_url" target="_blank" class="font-semibold text-[#A25F88] hover:underline">View</a>
                                <span v-else>-</span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ formatDate(item.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({
    merchant: { type: Object, required: true },
    providers: { type: Array, default: () => [] },
    deposits: { type: Array, default: () => [] },
    isSubmitting: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'copied', 'downloaded']);

const form = reactive({
    amount: '',
    bank_name: props.providers[0]?.bank_name ?? 'ABA',
    account_name: '',
    account_number: '',
    phone_number: '',
    note: '',
    file: null,
});

const selectedProvider = computed(() => {
    return props.providers.find((provider) => provider.bank_name === form.bank_name) ?? props.providers[0] ?? {
        bank_name: 'ABA',
        merchant_name: 'E-commerce',
        account_name: 'E-commerce KHQR Collection',
        account_number: '001 248 555',
        phone_number: '010 248 555',
        khqr_prefix: 'KHQR-ABA',
    };
});

const preview = computed(() => {
    const amount = Number.parseFloat(form.amount || '0');
    const safeAmount = Number.isFinite(amount) && amount > 0 ? amount : 0;
    const shopName = props.merchant?.shop_name || props.merchant?.owner_name || 'Merchant Shop';
    const provider = selectedProvider.value;
    const khqrCode = [
        provider.khqr_prefix,
        `BANK:${provider.bank_name}`,
        `MERCHANT:${shopName}`,
        `ACCOUNT:${provider.account_number}`,
        `AMOUNT:${safeAmount.toFixed(2)}`,
        'COUNTRY:KH',
    ].join('|');

    return {
        bankName: provider.bank_name,
        shopName,
        amount: safeAmount.toFixed(2),
        provider,
        khqrCode,
        imageUrl: '/khqr.jpg',
    };
});

function handleFileChange(event) {
    form.file = event.target.files?.[0] ?? null;
}

async function submit() {
    const payload = new FormData();
    payload.append('amount', form.amount);
    payload.append('bank_name', form.bank_name);
    payload.append('account_name', form.account_name);
    payload.append('account_number', form.account_number);
    payload.append('phone_number', form.phone_number);
    payload.append('note', form.note);

    if (form.file) {
        payload.append('payment_proof', form.file);
    }

    await emit('submit', payload);

    form.amount = '';
    form.account_name = '';
    form.account_number = '';
    form.phone_number = '';
    form.note = '';
    form.file = null;
}

async function copyKhqr() {
    await window.navigator.clipboard.writeText(buildPreviewLink());
    window.alert('KHQR link copied.');
    emit('copied');
}

function downloadQr() {
    const link = document.createElement('a');
    link.href = preview.value.imageUrl;
    link.download = `${preview.value.bankName.toLowerCase()}-khqr.jpg`;
    link.click();
    emit('downloaded');
}

function buildPreviewLink() {
    const url = new URL('/khqr-preview', window.location.origin);
    url.searchParams.set('bank', preview.value.bankName);
    url.searchParams.set('amount', preview.value.amount);
    url.searchParams.set('merchant', preview.value.shopName);
    url.searchParams.set('receiver', preview.value.provider.account_name ?? '');
    url.searchParams.set('code', preview.value.khqrCode);

    return url.toString();
}

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(value));
}

function statusClass(status) {
    return {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}
</script>

<style scoped>
.field-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    padding: 0.85rem 1rem;
    font-size: 0.95rem;
    color: #0f172a;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(162, 95, 136, 0.14);
}
</style>
