<template>
    <div class="space-y-8">
        <section class="mx-auto w-full max-w-[760px] rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ isEditing ? 'Edit Account' : 'New Account' }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ isEditing ? 'Update account' : 'Add payout account' }}</h2>
                </div>
                <button
                    v-if="isEditing"
                    type="button"
                    class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
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

                <div v-if="selectedBankVisual" class="rounded-[20px] border border-[#ead9e3] bg-[#fff8fc] px-4 py-4">
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-2xl text-sm font-extrabold text-white shadow-sm"
                            :style="{ backgroundColor: selectedBankVisual.color }"
                        >
                            {{ selectedBankVisual.short }}
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Selected Bank</p>
                            <p class="mt-1 text-sm font-bold text-slate-950">{{ selectedBankVisual.label }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Currency</span>
                        <select v-model="form.currency" class="field-input">
                            <option value="USD">USD</option>
                            <option value="KHR">KHR</option>
                        </select>
                    </label>

                    <div class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Account Type</span>
                        <div class="rounded-[18px] border border-[#ead9e3] bg-[#fff8fc] px-4 py-3">
                            <p class="text-sm font-bold text-slate-950">Bank Account + KHQR</p>
                            <p class="mt-1 text-xs text-slate-500">You can fill account number and KHQR in one form.</p>
                        </div>
                    </div>
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
                </label>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="busyId === 'create' || (editingId !== null && busyId === editingId)"
                >
                    {{ isEditing ? 'Save & Resubmit' : 'Submit' }}
                </button>
            </form>
        </section>

        <section class="rounded-[30px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <h2 class="text-2xl font-extrabold tracking-[-0.04em] text-slate-950">All payout accounts</h2>

                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="block sm:col-span-2">
                        <span class="sr-only">Search accounts</span>
                        <input v-model.trim="search" type="text" class="field-input" placeholder="Search">
                    </label>

                    <label class="block">
                        <span class="sr-only">Filter status</span>
                        <select v-model="statusFilter" class="field-input">
                            <option value="all">All Statuses</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </label>
                </div>
            </div>

            <div v-if="selectedAccount" class="mt-5 rounded-[24px] border border-[#ead9e3] bg-[#fff7fb] px-5 py-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-950">{{ selectedAccount.bank_name }} | {{ selectedAccount.account_holder_name }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ accountTypeLabel(selectedAccount) }} | {{ selectedAccount.currency }} | {{ formatDate(selectedAccount.created_at) }}</p>
                        <p v-if="selectedAccount.reject_reason" class="mt-3 text-sm text-rose-700">{{ selectedAccount.reject_reason }}</p>
                    </div>
                    <button type="button" class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" @click="selectedAccount = null">
                        Close
                    </button>
                </div>
            </div>

            <div v-if="filteredAccounts.length === 0" class="mt-6 rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                No payout accounts found yet.
            </div>

            <div v-else class="mt-6 overflow-x-auto rounded-[28px] border border-slate-200">
                <table class="min-w-[1180px] w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <tr>
                            <th class="px-4 py-4">Bank Name</th>
                            <th class="px-4 py-4">Account Holder</th>
                            <th class="px-4 py-4">Account Number</th>
                            <th class="px-4 py-4">Phone Number</th>
                            <th class="px-4 py-4">Currency</th>
                            <th class="px-4 py-4">Account Type</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4">Default</th>
                            <th class="px-4 py-4">
                                <button type="button" class="font-semibold text-slate-500 transition hover:text-slate-700" @click="toggleSortDirection">
                                    Created Date
                                </button>
                            </th>
                            <th class="px-4 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="account in filteredAccounts" :key="account.id" class="border-t border-slate-200 bg-white align-top">
                            <td class="px-4 py-4"><p class="font-semibold text-slate-950">{{ account.bank_name }}</p></td>
                            <td class="px-4 py-4 text-slate-700">{{ account.account_holder_name }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ account.account_number || '-' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ account.phone_number || '-' }}</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ account.currency }}</span></td>
                            <td class="px-4 py-4 text-slate-700">{{ accountTypeLabel(account) }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="statusClass(account.status)">{{ account.status }}</span>
                            </td>
                            <td class="px-4 py-4">
                                <span v-if="account.is_default" class="rounded-full bg-[#A25F88] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-white">Default</span>
                                <button
                                    v-else-if="account.status === 'approved'"
                                    type="button"
                                    class="rounded-full bg-[#f6eaf1] px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-[#A25F88] transition hover:bg-[#eedbe6]"
                                    :disabled="busyId === account.id"
                                    @click="setDefault(account)"
                                >
                                    Set Default
                                </button>
                                <span v-else class="text-xs text-slate-400">-</span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ formatDate(account.created_at) }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200" @click="selectedAccount = account">View</button>
                                    <button type="button" class="rounded-full bg-[#f6eaf1] px-3 py-2 text-xs font-semibold text-[#A25F88] transition hover:bg-[#eedbe6]" @click="startEdit(account)">
                                        {{ account.status === 'approved' ? 'Edit / Resubmit' : 'Edit' }}
                                    </button>
                                    <button type="button" class="rounded-full bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" :disabled="busyId === account.id" @click="$emit('delete', account.id)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
const selectedAccount = ref(null);
const search = ref('');
const statusFilter = ref('all');
const sortDirection = ref('desc');
const form = reactive(defaultForm());
const isEditing = computed(() => editingId.value !== null);
const selectedBankVisual = computed(() => bankVisual(form.bank_name));

const filteredAccounts = computed(() => {
    const query = search.value.trim().toLowerCase();

    return [...props.accounts]
        .filter((account) => statusFilter.value === 'all' || account.status === statusFilter.value)
        .filter((account) => {
            if (!query) return true;

            return [
                account.bank_name,
                account.account_holder_name,
                account.account_number,
                account.phone_number,
                account.currency,
                account.status,
                accountTypeLabel(account),
            ]
                .filter(Boolean)
                .some((value) => String(value).toLowerCase().includes(query));
        })
        .sort((left, right) => {
            const leftTime = left.created_at ? new Date(left.created_at).getTime() : 0;
            const rightTime = right.created_at ? new Date(right.created_at).getTime() : 0;

            return sortDirection.value === 'desc' ? rightTime - leftTime : leftTime - rightTime;
        });
});

function startEdit(account) {
    selectedAccount.value = account;
    editingId.value = account.id;
    form.bank_name = account.bank_name;
    form.account_holder_name = account.account_holder_name;
    form.account_number = '';
    form.phone_number = account.phone_number ?? '';
    form.currency = account.currency ?? 'USD';
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
        account_type: resolvedAccountType(),
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
    selectedAccount.value = null;
    Object.assign(form, defaultForm());
}

function toggleSortDirection() {
    sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
}

function defaultForm() {
    return {
        bank_name: '',
        account_holder_name: '',
        account_number: '',
        phone_number: '',
        currency: 'USD',
        khqr_code: '',
        qr_image: null,
    };
}

function resolvedAccountType() {
    return form.khqr_code.trim() !== '' || form.qr_image instanceof File ? 'khqr' : 'bank_account';
}

function accountTypeLabel(accountOrType) {
    if (typeof accountOrType === 'object' && accountOrType !== null) {
        const hasBank = Boolean(accountOrType.account_number && accountOrType.account_number !== '-');
        const hasKhqr = Boolean(accountOrType.khqr_code || accountOrType.qr_image_url || accountOrType.account_type === 'khqr');

        if (hasBank && hasKhqr) {
            return 'Bank + KHQR';
        }

        return hasKhqr ? 'KHQR' : 'Bank Account';
    }

    return accountOrType === 'khqr' ? 'KHQR' : 'Bank Account';
}

function bankVisual(bankName) {
    if (!bankName) return null;

    const key = String(bankName).trim().toUpperCase();
    const presets = {
        ABA: { label: 'ABA', short: 'ABA', color: '#0F766E' },
        ACLEDA: { label: 'ACLEDA', short: 'AC', color: '#1D4ED8' },
        WING: { label: 'Wing', short: 'WG', color: '#16A34A' },
        CASH: { label: 'Cash', short: 'CA', color: '#F59E0B' },
        CARD: { label: 'Card', short: 'CD', color: '#7C3AED' },
    };

    if (presets[key]) {
        return presets[key];
    }

    const clean = key.replace(/[^A-Z0-9]/g, '');

    return {
        label: bankName,
        short: clean.slice(0, 2) || 'BK',
        color: '#A25F88',
    };
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
