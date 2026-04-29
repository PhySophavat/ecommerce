<template>
    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">KHQR Payment</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Top up merchant wallet</h2>

            <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-5 text-center">
                <img
                    v-if="khqr.image_url"
                    :src="khqr.image_url"
                    alt="KHQR"
                    class="mx-auto h-56 w-56 rounded-3xl border border-slate-200 object-cover"
                >
                <div v-else class="mx-auto flex h-56 w-56 items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white px-4 text-center text-sm text-slate-500">
                    QR image not configured. Use the KHQR code below.
                </div>

                <div class="mt-5 rounded-3xl bg-white p-4 text-left">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">KHQR Code</p>
                    <p class="mt-2 break-all text-sm text-slate-700">{{ khqr.code }}</p>
                    <button type="button" class="mt-4 rounded-full bg-[#A25F88] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90" @click="copyKhqr">
                        Copy KHQR Code
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Deposit Request</p>
            <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Submit payment proof</h2>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                    <input v-model="form.amount" type="number" step="0.01" min="0" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Payment Method</span>
                    <select v-model="form.payment_method" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]">
                        <option value="khqr">KHQR</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Payment Proof</span>
                    <input type="file" accept="image/*" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" @change="handleFileChange">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Note</span>
                    <textarea v-model="form.note" rows="4" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#A25F88]"></textarea>
                </label>

                <button type="submit" class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90" :disabled="isSubmitting || !form.file">
                    Submit Deposit
                </button>
            </form>

            <div class="mt-8 rounded-3xl border border-slate-200">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-bold text-slate-950">Deposit History</h3>
                </div>
                <div v-if="deposits.length === 0" class="px-5 py-8 text-center text-sm text-slate-500">No deposit requests yet.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            <tr>
                                <th class="px-4 py-4">Amount</th>
                                <th class="px-4 py-4">Method</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4">Proof</th>
                                <th class="px-4 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in deposits" :key="item.id" class="border-t border-slate-200">
                                <td class="px-4 py-4 font-semibold text-slate-950">{{ currency(item.amount) }}</td>
                                <td class="px-4 py-4 capitalize text-slate-600">{{ item.payment_method.replace('_', ' ') }}</td>
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
            </div>
        </section>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const props = defineProps({
    khqr: { type: Object, required: true },
    deposits: { type: Array, default: () => [] },
    isSubmitting: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'copied']);

const form = reactive({
    amount: '',
    payment_method: 'khqr',
    note: '',
    file: null,
});

function handleFileChange(event) {
    form.file = event.target.files?.[0] ?? null;
}

async function submit() {
    const payload = new FormData();
    payload.append('amount', form.amount);
    payload.append('payment_method', form.payment_method);
    payload.append('note', form.note);

    if (form.file) {
        payload.append('payment_proof', form.file);
    }

    await emit('submit', payload);

    form.amount = '';
    form.note = '';
    form.file = null;
}

async function copyKhqr() {
    await window.navigator.clipboard.writeText(props.khqr.code || '');
    emit('copied');
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
