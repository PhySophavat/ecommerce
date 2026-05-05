<template>
    <div class="grid gap-6 xl:grid-cols-[1.18fr_0.82fr]">
        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">Payout Accounts</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ accounts.length }} merchant payout accounts</h2>
                    <p class="mt-2 text-sm text-slate-500">New accounts stay pending until an admin reviews them. Only approved accounts can be used for withdrawals.</p>
                </div>
            </div>

            <div v-if="accounts.length === 0" class="mt-6 rounded-[28px] border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-6 py-12 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-[#A25F88] text-white shadow-lg shadow-[#A25F88]/20">
                    <span class="text-2xl font-black">+</span>
                </div>
                <h3 class="mt-4 text-xl font-extrabold tracking-[-0.03em] text-slate-950">No payout account yet</h3>
                <p class="mt-2 text-sm text-slate-500">Add your first bank account or KHQR payout profile to start receiving approved withdrawals.</p>
            </div>

            <div v-else class="mt-6 space-y-4">
                <article
                    v-for="account in accounts"
                    :key="account.id"
                    class="rounded-[28px] border border-slate-200 bg-slate-50/70 p-5"
                >
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-950">{{ account.bank_name }}</h3>
                                <span v-if="account.is_default" class="rounded-full bg-[#A25F88] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-white">
                                    Default
                                </span>
                                <span class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]" :class="statusClass(account.status)">
                                    {{ account.status }}
                                </span>
                                <span class="rounded-full bg-slate-200 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">
                                    {{ account.currency }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Account Holder</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ account.account_holder_name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Account Number</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ account.account_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Phone</p>
                                    <p class="mt-1">{{ account.phone_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Type</p>
                                    <p class="mt-1">{{ accountTypeLabel(account.account_type) }}</p>
                                </div>
                            </div>

                            <div v-if="account.qr_image_url || account.khqr_code || account.reject_reason" class="mt-4 grid gap-4 lg:grid-cols-[180px_1fr]">
                                <div v-if="account.qr_image_url" class="rounded-3xl border border-slate-200 bg-white p-3">
                                    <img :src="account.qr_image_url" alt="KHQR" class="h-36 w-full rounded-2xl object-contain">
                                </div>
                                <div class="space-y-3">
                                    <div v-if="account.khqr_code" class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">KHQR Code</p>
                                        <p class="mt-2 break-all text-sm text-slate-600">{{ account.khqr_code }}</p>
                                    </div>
                                    <div v-if="account.reject_reason" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                        <p class="font-semibold text-rose-800">Admin note</p>
                                        <p class="mt-1">{{ account.reject_reason }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="account.status === 'approved' && !account.is_default"
                                type="button"
                                class="rounded-full bg-[#A25F88] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                :disabled="busyId === account.id"
                                @click="setDefault(account)"
                            >
                                Set Default
                            </button>
                            <button
                                type="button"
                                class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                                @click="startEdit(account)"
                            >
                                {{ account.status === 'approved' ? 'Edit & Resubmit' : 'Edit' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-full bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100"
                                :disabled="busyId === account.id"
                                @click="$emit('delete', account.id)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ isEditing ? 'Edit account' : 'New account' }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ isEditing ? 'Update payout account' : 'Add payout account' }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Bank account submissions are reviewed by admin before they become available for payout requests.</p>
                </div>
                <button
                    v-if="isEditing"
                    type="button"
                    class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700"
                    @click="resetForm"
                >
                    Cancel
                </button>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submitForm">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Bank Name</span>
                    <select v-model="form.bank_name" class="field-input">
                        <option value="">Select bank</option>
                        <option v-for="option in bankOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Currency</span>
                        <select v-model="form.currency" class="field-input">
                            <option value="USD">USD</option>
                            <option value="KHR">KHR</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Account Type</span>
                        <select v-model="form.account_type" class="field-input">
                            <option value="bank_account">Bank Account</option>
                            <option value="khqr">KHQR</option>
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Account Holder Name</span>
                    <input v-model="form.account_holder_name" type="text" class="field-input" placeholder="Merchant owner or business name">
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Account Number</span>
                        <input v-model="form.account_number" type="text" class="field-input" :placeholder="isEditing ? 'Leave blank to keep current account number' : 'Enter account number'">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Phone Number</span>
                        <input v-model="form.phone_number" type="text" class="field-input" placeholder="088 123 4567">
                    </label>
                </div>

                <template v-if="form.account_type === 'khqr'">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">KHQR Code</span>
                        <textarea v-model="form.khqr_code" rows="3" class="field-input" placeholder="Optional raw KHQR code" />
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">QR Image</span>
                        <input
                            type="file"
                            accept="image/*"
                            class="field-input file:mr-4 file:rounded-xl file:border-0 file:bg-[#A25F88] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                            @change="handleQrImageChange"
                        >
                        <p class="mt-2 text-xs text-slate-500">Upload a KHQR image, or provide a KHQR code above.</p>
                    </label>
                </template>

                <div class="rounded-3xl border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-4 py-4 text-sm text-slate-600">
                    Approved accounts can be set as default later. Pending, rejected, or disabled accounts cannot be used for withdrawals.
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="busyId === 'create' || (editingId !== null && busyId === editingId)"
                >
                    {{ isEditing ? 'Save & Resubmit For Approval' : 'Submit For Approval' }}
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    bankOptions: { type: Array, default: () => [] },
    busyId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['create', 'update', 'delete']);

const editingId = ref(null);
const form = reactive(defaultForm());
const isEditing = computed(() => editingId.value !== null);

function startEdit(account) {
    editingId.value = account.id;
    form.bank_name = account.bank_name;
    form.account_holder_name = account.account_holder_name;
    form.account_number = '';
    form.phone_number = account.phone_number ?? '';
    form.currency = account.currency ?? 'USD';
    form.account_type = account.account_type;
    form.khqr_code = account.khqr_code ?? '';
    form.qr_image = null;
}

async function submitForm() {
    const payload = {
        bank_name: form.bank_name,
        account_holder_name: form.account_holder_name,
        account_number: form.account_number,
        phone_number: form.phone_number,
        currency: form.currency,
        account_type: form.account_type,
        khqr_code: form.khqr_code,
        qr_image: form.qr_image,
        is_default: false,
    };

    if (isEditing.value) {
        await emit('update', editingId.value, payload);
    } else {
        await emit('create', payload);
    }

    resetForm();
}

async function setDefault(account) {
    await emit('update', account.id, {
        bank_name: account.bank_name,
        account_holder_name: account.account_holder_name,
        account_number: '',
        phone_number: account.phone_number,
        currency: account.currency,
        account_type: account.account_type,
        khqr_code: account.khqr_code,
        is_default: true,
        qr_image: null,
    });
}

function handleQrImageChange(event) {
    form.qr_image = event.target.files?.[0] ?? null;
}

function resetForm() {
    editingId.value = null;
    Object.assign(form, defaultForm());
}

function defaultForm() {
    return {
        bank_name: '',
        account_holder_name: '',
        account_number: '',
        phone_number: '',
        currency: 'USD',
        account_type: 'bank_account',
        khqr_code: '',
        qr_image: null,
    };
}

function accountTypeLabel(type) {
    return type === 'khqr' ? 'KHQR' : 'Bank Account';
}

function statusClass(status) {
    return {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
        disabled: 'bg-slate-200 text-slate-600',
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
