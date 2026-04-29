<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="submitSettings"
                    @refresh="loadSettings"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
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

                    <div v-if="isLoading" class="admin-card rounded-[30px] px-6 py-14 text-center text-sm text-slate-500">
                        Loading platform fee settings...
                    </div>

                    <div v-else class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),420px]">
                        <section class="admin-card rounded-[30px] px-6 py-6">
                            <div class="flex flex-col gap-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Platform fee settings</p>
                                <h2 class="text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Commission rules for merchant payouts</h2>
                                <p class="text-sm leading-7 text-slate-500">Choose when the platform deducts commission and how much should be withheld from merchant balance.</p>
                            </div>

                            <form class="mt-6 space-y-5" @submit.prevent="submitSettings">
                                <label class="flex items-center justify-between rounded-[24px] border border-[#dfe5f5] bg-[#fbfcff] px-5 py-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Enable Platform Fee</p>
                                        <p class="mt-1 text-sm text-slate-500">Turn automatic commission deduction on or off.</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="relative h-8 w-14 rounded-full transition"
                                        :class="form.is_enabled ? 'bg-[#4f5ee4]' : 'bg-slate-300'"
                                        @click="form.is_enabled = !form.is_enabled"
                                    >
                                        <span
                                            class="absolute top-1 h-6 w-6 rounded-full bg-white shadow transition"
                                            :class="form.is_enabled ? 'left-7' : 'left-1'"
                                        ></span>
                                    </button>
                                </label>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-800">Fee Type</span>
                                        <select v-model="form.fee_type" class="field-input" :class="fieldClass('fee_type')">
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed</option>
                                        </select>
                                        <p v-if="errors.fee_type" class="text-xs text-rose-600">{{ errors.fee_type[0] }}</p>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-800">Fee Value</span>
                                        <input
                                            v-model="form.fee_value"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="field-input"
                                            :class="fieldClass('fee_value')"
                                            :placeholder="form.fee_type === 'percentage' ? '10' : '5.00'"
                                        >
                                        <p class="text-xs text-slate-500">
                                            {{ form.fee_type === 'percentage' ? 'Enter a value from 0 to 100.' : 'Enter a flat currency amount.' }}
                                        </p>
                                        <p v-if="errors.fee_value" class="text-xs text-rose-600">{{ errors.fee_value[0] }}</p>
                                    </label>
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-800">Apply Stage</span>
                                        <select v-model="form.apply_stage" class="field-input" :class="fieldClass('apply_stage')">
                                            <option value="payment_success">Payment Success</option>
                                            <option value="order_completed">Order Completed</option>
                                        </select>
                                        <p v-if="errors.apply_stage" class="text-xs text-rose-600">{{ errors.apply_stage[0] }}</p>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-800">Deduct From</span>
                                        <input value="Merchant Balance" type="text" class="field-input bg-slate-50 text-slate-500" readonly>
                                    </label>
                                </div>

                                <div class="rounded-[24px] border border-[#e3e9f7] bg-[#f8faff] px-5 py-5">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#6c78da]">Rule summary</p>
                                            <p class="mt-2 text-sm text-slate-600">
                                                {{ form.is_enabled ? `The platform fee will be deducted when the order reaches ${applyStageLabel.toLowerCase()}.` : 'The platform fee is disabled, so merchants receive the full amount.' }}
                                            </p>
                                        </div>
                                        <button
                                            type="submit"
                                            class="admin-primary-button rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isSaving"
                                        >
                                            {{ isSaving ? 'Saving...' : 'Save Settings' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </section>

                        <section class="admin-card rounded-[30px] px-6 py-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Preview</p>
                            <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Example payout</h3>
                            <p class="mt-2 text-sm leading-7 text-slate-500">This example uses an order total of $100.00 to show what the merchant receives and what the platform earns.</p>

                            <div class="mt-6 space-y-4">
                                <div class="rounded-[24px] border border-[#e3e9f7] bg-[#fbfcff] px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Order total</p>
                                    <p class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-slate-950">${{ preview.order_total }}</p>
                                </div>

                                <div class="rounded-[24px] border border-[#f0d9e6] bg-[#fcf7fa] px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#A25F88]">Platform fee</p>
                                    <p class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-[#A25F88]">${{ preview.platform_fee }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ previewDescription }}</p>
                                </div>

                                <div class="rounded-[24px] border border-emerald-200 bg-emerald-50 px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Merchant receives</p>
                                    <p class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-emerald-700">${{ preview.merchant_receives }}</p>
                                </div>

                                <div class="rounded-[24px] border border-[#dfe5f5] bg-white px-5 py-4 text-sm text-slate-600">
                                    <p><span class="font-semibold text-slate-900">Platform earns:</span> ${{ preview.platform_earns }}</p>
                                    <p class="mt-2"><span class="font-semibold text-slate-900">Apply stage:</span> {{ applyStageLabel }}</p>
                                    <p class="mt-2"><span class="font-semibold text-slate-900">Deduct from:</span> Merchant Balance</p>
                                </div>
                            </div>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

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
        : form.fee_type === 'fixed'
            ? numericFeeValue.value
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
        return 'Platform fee is disabled for this example.';
    }

    return form.fee_type === 'percentage'
        ? `${trimmedFeeValue()}% of the order total`
        : `Fixed amount of $${numericFeeValue.value.toFixed(2)}`;
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
            fee_type: form.fee_type,
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
    form.fee_type = setting.fee_type ?? 'percentage';
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
            subheadline: 'Configure how the platform deducts commission from merchant balances after each qualifying order stage.',
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
    border-radius: 1.25rem;
    border-width: 1px;
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 3px rgba(162, 95, 136, 0.12);
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
