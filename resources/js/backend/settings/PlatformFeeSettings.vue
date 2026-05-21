<template>
    <AdminLayout
        :dashboard="dashboard"
        :is-menu-open="isMenuOpen"
        :screen="screen"
        @select-item="handleMenuSelection"
        @toggle-menu="toggleMenu"
    >
        <template #header>
                <AdminHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="submitSettings"
                    @refresh="loadSettings"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />
        </template>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <transition name="fade">
                        <div
                            v-if="toast"
                            class="fixed right-6 top-6 z-50 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-emerald-700 shadow-xl"
                        >
                            {{ toast }}
                        </div>
                    </transition>

                    <section
                        v-if="notice"
                        class="admin-frosted mb-6 rounded-[26px] px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="rounded-[28px] border border-[#E5E7EB] bg-white px-6 py-14 text-center text-sm text-[#6B7280] shadow-[0_18px_40px_rgba(15,23,42,0.06)]">
                        Loading settings...
                    </div>

                    <div v-else class="grid gap-6 2xl:grid-cols-[minmax(0,1.18fr),minmax(360px,420px)]">
                        <section class="rounded-[30px] border border-[#E5E7EB] bg-white px-6 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.06)] sm:px-7 sm:py-7">
                            <div class="flex flex-col gap-2 border-b border-[#F1F5F9] pb-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Platform fee settings</p>
                                <h2 class="text-[1.9rem] font-bold tracking-[-0.04em] text-[#111827]">Commission rules</h2>
                            </div>

                            <form class="mt-7 space-y-6" @submit.prevent="submitSettings">
                                <label class="flex items-center justify-between gap-4 rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5">
                                    <div>
                                        <p class="text-sm font-semibold text-[#111827]">Enable Platform Fee</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="relative h-8 w-14 rounded-full transition"
                                        :class="form.is_enabled ? 'bg-[#A25F88]' : 'bg-slate-300'"
                                        @click="form.is_enabled = !form.is_enabled"
                                    >
                                        <span
                                            class="absolute top-1 h-6 w-6 rounded-full bg-white shadow transition"
                                            :class="form.is_enabled ? 'left-7' : 'left-1'"
                                        ></span>
                                    </button>
                                </label>

                                <div class="grid gap-5 lg:grid-cols-2">
                                    <label class="block space-y-2.5">
                                        <span class="text-sm font-semibold text-[#111827]">Fee Type</span>
                                        <input
                                            value="Percentage"
                                            type="text"
                                            class="field-input bg-[#F8FAFC] text-[#6B7280]"
                                            readonly
                                        >
                                        <p v-if="errors.fee_type" class="text-xs text-rose-600">{{ errors.fee_type[0] }}</p>
                                    </label>

                                    <label class="block space-y-2.5">
                                        <span class="text-sm font-semibold text-[#111827]">Fee Value</span>
                                        <div class="relative">
                                            <input
                                                v-model="form.fee_value"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                class="field-input pr-12"
                                                :class="fieldClass('fee_value')"
                                                placeholder="10"
                                            >
                                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">%</span>
                                        </div>
                                        <p v-if="errors.fee_value" class="text-xs text-rose-600">{{ errors.fee_value[0] }}</p>
                                    </label>
                                </div>

                                <div class="space-y-2.5">
                                    <label class="block space-y-2.5">
                                        <span class="text-sm font-semibold text-[#111827]">Apply Stage</span>
                                        <select v-model="form.apply_stage" class="field-input" :class="fieldClass('apply_stage')">
                                            <option value="payment_success">Payment Success</option>
                                            <option value="order_completed">Order Completed</option>
                                        </select>
                                        <p v-if="errors.apply_stage" class="text-xs text-rose-600">{{ errors.apply_stage[0] }}</p>
                                    </label>

                                    <input v-model="form.deduct_from" type="hidden">
                                </div>

                                <div class="rounded-[26px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#A25F88]">Rule summary</p>
                                            <p class="mt-2 max-w-2xl text-sm text-[#6B7280]">
                                                {{ form.is_enabled ? `Deduct on ${applyStageLabel.toLowerCase()}.` : 'Fee disabled.' }}
                                            </p>
                                        </div>
                                        <button
                                            type="submit"
                                            class="rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_rgba(162,95,136,0.22)] transition hover:-translate-y-0.5 hover:bg-[#92557a] disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isSaving"
                                        >
                                            {{ isSaving ? 'Saving...' : 'Save Settings' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </section>

                        <section class="rounded-[30px] border border-[#E5E7EB] bg-white px-6 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.06)] sm:px-7 sm:py-7">
                            <div class="rounded-[26px] border border-[#EDE7EC] bg-[linear-gradient(180deg,#FCFAFB_0%,#FFFFFF_100%)] p-5 sm:p-6">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Preview</p>
                                <h3 class="mt-2 text-[1.9rem] font-bold tracking-[-0.04em] text-[#111827]">Example payout</h3>

                                <div class="mt-6 grid gap-4 md:grid-cols-3">
                                    <div class="rounded-[22px] border border-[#E5E7EB] bg-white px-5 py-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">Order Total</p>
                                        <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#111827]">${{ preview.order_total }}</p>
                                    </div>

                                    <div class="rounded-[22px] border border-[#F0D9E6] bg-[#FCF4F8] px-5 py-5 shadow-[0_10px_24px_rgba(162,95,136,0.06)]">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Platform Fee</p>
                                        <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-[#A25F88]">${{ preview.platform_fee }}</p>
                                        <p class="mt-2 text-xs text-[#8B5C78]">{{ previewDescription }}</p>
                                    </div>

                                    <div class="rounded-[22px] border border-emerald-200 bg-emerald-50 px-5 py-5 shadow-[0_10px_24px_rgba(16,185,129,0.06)]">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Merchant Receives</p>
                                        <p class="mt-3 text-3xl font-bold tracking-[-0.04em] text-emerald-700">${{ preview.merchant_receives }}</p>
                                    </div>
                                </div>

                                <div class="mt-6 rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] p-5">
                                    <div class="flex items-center justify-between gap-3 border-b border-[#E5E7EB] pb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-[#111827]">Payout details</p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-[#A25F88] shadow-sm">
                                            {{ form.is_enabled ? 'Fee enabled' : 'Fee disabled' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-[18px] bg-white px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#9CA3AF]">Platform Earns</p>
                                            <p class="mt-2 text-base font-semibold text-[#111827]">${{ preview.platform_earns }}</p>
                                        </div>
                                        <div class="rounded-[18px] bg-white px-4 py-3">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#9CA3AF]">Apply Stage</p>
                                            <p class="mt-2 text-base font-semibold text-[#111827]">{{ applyStageLabel }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
        </main>
    </AdminLayout>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

import AdminHeader from '../layout/AdminHeader.vue';
import AdminLayout from '../layout/AdminLayout.vue';

const screen = 'platform-fee-settings';
const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/platform-fee-settings';
const dashboard = ref(initialDashboard());
const isLoading = ref(true);
const isSaving = ref(false);
const notice = ref(null);
const toast = ref('');
const openMenus = ref({});
const form = reactive({
    is_enabled: true,
    fee_type: 'percentage',
    fee_value: '0.00',
    apply_stage: 'payment_success',
    deduct_from: 'merchant_balance',
});
const errors = reactive({});

const applyStageLabel = computed(() => form.apply_stage === 'order_completed' ? 'Order Completed' : 'Payment Success');
const numericFeeValue = computed(() => {
    const parsed = Number.parseFloat(String(form.fee_value ?? '0'));

    return Number.isFinite(parsed) ? Math.max(parsed, 0) : 0;
});
const preview = computed(() => {
    const orderTotal = 100;
    const fee = !form.is_enabled
        ? 0
        : roundCurrency((orderTotal * numericFeeValue.value) / 100);
    const merchantReceives = Math.max(roundCurrency(orderTotal - fee), 0);

    return {
        order_total: '100.00',
        platform_fee: fee.toFixed(2),
        merchant_receives: merchantReceives.toFixed(2),
        platform_earns: fee.toFixed(2),
    };
});
const previewDescription = computed(() => {
    if (!form.is_enabled) {
        return 'Disabled';
    }

    return `${trimmedFeeValue()}% of total`;
});

onMounted(async () => {
    await loadSettings();
});

async function loadSettings({ preserveNotice = false } = {}) {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            headers: {
                Accept: 'application/json',
            },
        });

        dashboard.value = response.data;
        syncOpenMenus(response.data.menu ?? []);
        syncForm(response.data.setting ?? {});

        if (!preserveNotice) {
            notice.value = null;
        }
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to load platform fee settings right now.'),
        };
    } finally {
        isLoading.value = false;
    }
}

async function submitSettings() {
    isSaving.value = true;
    clearErrors();
    notice.value = null;

    try {
        const response = await window.axios.put(endpoint, {
            is_enabled: form.is_enabled,
            fee_type: 'percentage',
            fee_value: numericFeeValue.value.toFixed(2),
            apply_stage: form.apply_stage,
            deduct_from: form.deduct_from,
        }, {
            headers: {
                Accept: 'application/json',
            },
        });

        dashboard.value = response.data;
        syncOpenMenus(response.data.menu ?? []);
        syncForm(response.data.setting ?? {});

        toast.value = response.data?.message ?? 'Platform fee settings saved successfully.';
        notice.value = {
            type: 'success',
            text: toast.value,
        };
        window.setTimeout(() => {
            toast.value = '';
        }, 2500);
    } catch (error) {
        if (error?.response?.status === 422 && error.response.data?.errors) {
            assignErrors(error.response.data.errors);
        }

        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to save platform fee settings right now.'),
        };
    } finally {
        isSaving.value = false;
    }
}

function syncForm(setting) {
    form.is_enabled = Boolean(setting.is_enabled);
    form.fee_type = 'percentage';
    form.fee_value = String(setting.fee_value ?? '0.00');
    form.apply_stage = setting.apply_stage ?? 'payment_success';
    form.deduct_from = setting.deduct_from ?? 'merchant_balance';
}

function syncOpenMenus(menuItems) {
    openMenus.value = menuItems.reduce((state, item) => {
        state[item.slug] = Boolean(item.is_expanded);
        return state;
    }, {});
}

function toggleMenu(slug) {
    openMenus.value = {
        ...openMenus.value,
        [slug]: !openMenus.value[slug],
    };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        try {
            await window.axios.post('/auth/logout');
        } finally {
            window.location.assign('/login');
        }
        return;
    }

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

function assignErrors(validationErrors) {
    clearErrors();

    Object.entries(validationErrors).forEach(([field, fieldErrors]) => {
        errors[field] = fieldErrors;
    });
}

function clearErrors() {
    Object.keys(errors).forEach((field) => {
        delete errors[field];
    });
}

function fieldClass(field) {
    return errors[field]
        ? 'border-rose-300 bg-rose-50 text-slate-900 placeholder:text-rose-300'
        : 'border-[#d8def1] bg-white text-slate-900 placeholder:text-slate-400';
}

function extractMessage(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const firstError = Object.values(response.errors).flat()[0];

        if (firstError) {
            return firstError;
        }
    }

    return response?.message ?? fallback;
}

function roundCurrency(value) {
    return Math.round(value * 100) / 100;
}

function trimmedFeeValue() {
    return String(Number.parseFloat(numericFeeValue.value.toFixed(2)));
}

function initialDashboard() {
    return {
        screen,
        meta: {
            brand: 'E-commerce',
            page_title: 'Platform Fee Settings',
            kicker: 'Commission control',
            subheadline: 'Set the platform commission rule.',
            links: {
                frontend: '/frontend',
                admin_users: '/admin/users',
                admin_merchants: '/admin/merchants',
                logout: '/auth/logout',
            },
        },
        menu: [],
    };
}
</script>

<style scoped>
.field-input {
    width: 100%;
    border-radius: 1rem;
    border-width: 1px;
    border-color: #e5e7eb;
    padding: 0.95rem 1rem;
    font-size: 0.95rem;
    color: #111827;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 3px rgba(162, 95, 136, 0.12);
}

.field-input::placeholder {
    color: #9ca3af;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
