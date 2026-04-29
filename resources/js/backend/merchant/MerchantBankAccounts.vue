<template>
    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <section class="rounded-[28px] border border-slate-200 bg-slate-50/60 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Saved accounts</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ accounts.length }} payout accounts</h2>
                </div>
            </div>

            <div v-if="accounts.length === 0" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center text-sm text-slate-500">
                No payout accounts yet.
            </div>

            <div v-else class="mt-6 space-y-4">
                <article
                    v-for="account in accounts"
                    :key="account.id"
                    class="rounded-3xl border border-slate-200 bg-white p-5"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-950">{{ account.bank_name }}</h3>
                                <span v-if="account.is_default" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">
                                    Default
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.16em]" :class="account.status === 'active' ? 'bg-sky-100 text-sky-700' : 'bg-slate-200 text-slate-600'">
                                    {{ account.status }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ account.account_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ account.account_number }} • {{ account.account_type }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200"
                                @click="startEdit(account)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                :disabled="busyId === account.id"
                                @click="makeDefault(account)"
                            >
                                Set Default
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

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ isEditing ? 'Edit account' : 'New account' }}</p>
                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ isEditing ? 'Update payout details' : 'Add payout account' }}</h2>
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
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Bank name</span>
                    <select v-model="form.bank_name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                        <option value="">Select bank</option>
                        <option v-for="option in bankOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Account type</span>
                    <select v-model="form.account_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                        <option value="bank">Bank</option>
                        <option value="ewallet">E-Wallet</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Account name</span>
                    <input v-model="form.account_name" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Account number</span>
                    <input v-model="form.account_number" type="text" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400" :placeholder="isEditing ? 'Leave blank to keep current number' : 'Enter account number'">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
                    <select v-model="form.status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    <input v-model="form.is_default" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                    Set as default payout account
                </label>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                    :disabled="busyId === 'create' || (editingId !== null && busyId === editingId)"
                >
                    {{ isEditing ? 'Update Account' : 'Add Account' }}
                </button>
            </form>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
    bankOptions: {
        type: Array,
        default: () => [],
    },
    busyId: {
        type: [Number, String, null],
        default: null,
    },
});

const emit = defineEmits(['create', 'update', 'delete']);

const editingId = ref(null);
const form = reactive(defaultForm());
const isEditing = computed(() => editingId.value !== null);

function startEdit(account) {
    editingId.value = account.id;
    form.bank_name = account.bank_name;
    form.account_type = account.account_type;
    form.account_name = account.account_name;
    form.account_number = '';
    form.is_default = Boolean(account.is_default);
    form.status = account.status;
}

async function submitForm() {
    const payload = {
        bank_name: form.bank_name,
        account_type: form.account_type,
        account_name: form.account_name,
        account_number: form.account_number,
        is_default: form.is_default,
        status: form.status,
    };

    if (isEditing.value) {
        await emit('update', editingId.value, payload);
    } else {
        await emit('create', payload);
    }

    resetForm();
}

async function makeDefault(account) {
    await emit('update', account.id, {
        bank_name: account.bank_name,
        account_type: account.account_type,
        account_name: account.account_name,
        account_number: '',
        is_default: true,
        status: account.status,
    });
}

function resetForm() {
    editingId.value = null;
    Object.assign(form, defaultForm());
}

function defaultForm() {
    return {
        bank_name: '',
        account_type: 'bank',
        account_name: '',
        account_number: '',
        is_default: false,
        status: 'active',
    };
}
</script>
