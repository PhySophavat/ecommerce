<template>
    <div class="min-h-screen bg-[#F8FAFC] px-4 py-4 sm:px-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] max-w-[1800px] overflow-hidden rounded-[32px] border border-[#E5E7EB] bg-white">
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
                    @primary-action="loadPayments"
                    @refresh="loadPayments"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section v-if="notice" class="mb-5 rounded-[18px] border px-4 py-3 text-sm" :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
                        {{ notice.text }}
                    </section>

                    <section class="mb-5 grid gap-3 sm:grid-cols-3">
                        <article v-for="card in summaryCards" :key="card.label" class="rounded-[22px] border border-[#E5E7EB] bg-[#FFFFFF] px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-black text-[#111827]">{{ card.value }}</p>
                        </article>
                    </section>

                    <section class="overflow-hidden rounded-[24px] border border-[#E5E7EB] bg-[#FFFFFF]">
                        <div class="border-b border-[#E5E7EB] bg-[#F8FAFC] px-5 py-4">
                            <div class="grid gap-3 lg:grid-cols-3">
                                <input v-model.trim="filters.search" type="text" placeholder="Search order or customer" class="rounded-[14px] border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none placeholder:text-[#6B7280]">
                                <select v-model="filters.status" class="rounded-[14px] border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none">
                                    <option value="">All statuses</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="auto_failed">Auto failed</option>
                                    <option value="pending">Pending</option>
                                </select>
                                <select v-model="filters.payment_method" class="rounded-[14px] border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none">
                                    <option value="">All methods</option>
                                    <option v-for="method in paymentMethods" :key="method" :value="method">{{ formatMethod(method) }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="loading" class="px-6 py-16 text-center text-sm text-[#6B7280]">Loading payments...</div>
                        <div v-else-if="filteredPayments.length === 0" class="px-6 py-16 text-center text-sm text-[#6B7280]">No payment proofs found.</div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full min-w-[980px] text-sm">
                                <thead class="bg-[#F8FAFC]">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Order</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Customer</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Reference</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Auto Check</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Amount</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="payment in filteredPayments"
                                        :key="payment.id"
                                        class="border-t border-[#E5E7EB] transition hover:bg-[#F8FAFC]"
                                    >
                                        <td class="px-4 py-4">
                                            <p class="max-w-[260px] truncate font-semibold text-[#111827]">{{ payment.order_code }}</p>
                                            <p class="mt-1 text-xs text-[#6B7280]">{{ formatDateTime(payment.created_at) }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-[#6B7280]">{{ payment.customer_name }}</td>
                                        <td class="px-4 py-4 text-[#6B7280]">{{ formatMethod(payment.payment_method) }}</td>
                                        <td class="px-4 py-4 text-[#6B7280]">{{ payment.transaction_ref || 'No reference' }}</td>
                                        <td class="px-4 py-4">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="statusClass(payment.status)">
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="autoCheckClass(payment.auto_check_status)">
                                                {{ payment.auto_check_status || 'pending' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right font-semibold text-[#111827]">${{ Number(payment.amount || 0).toFixed(2) }}</td>
                                        <td class="px-4 py-4 text-center">
                                            <button
                                                type="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#A25F88] transition hover:border-[#A25F88] hover:bg-[#FCFAFB] hover:text-[#8E4F76]"
                                                @click="openReviewModal(payment)"
                                            >
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </main>
            </div>
        </div>

        <transition name="modal-fade">
            <div v-if="isReviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6">
                <button type="button" class="absolute inset-0 bg-[rgba(15,23,42,0.68)]" @click="closeReviewModal"></button>

                <div class="relative z-10 flex max-h-[90vh] w-full max-w-[760px] flex-col overflow-hidden rounded-2xl bg-[#FFFFFF] shadow-[0_32px_80px_rgba(15,23,42,0.24)]">
                    <div class="flex items-start justify-between gap-4 border-b border-[#E5E7EB] px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Payment review</p>
                            <h2 class="mt-1 text-2xl font-black tracking-[-0.03em] text-[#111827]">{{ selectedPaymentDetail?.order_code || 'Review payment' }}</h2>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#E5E7EB] bg-white text-[#6B7280] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                            @click="closeReviewModal"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>

                    <div v-if="detailLoading" class="px-6 py-16 text-center text-sm text-[#6B7280]">Loading payment...</div>
                    <div v-else-if="selectedPaymentDetail" class="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Order number</p>
                                <p class="mt-2 text-lg font-bold text-[#111827]">{{ selectedPaymentDetail.order_code }}</p>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Status</p>
                                <div class="mt-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="statusClass(selectedPaymentDetail.status)">
                                        {{ selectedPaymentDetail.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Customer name</p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">{{ selectedPaymentDetail.customer_name }}</p>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Customer email</p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">{{ selectedPaymentDetail.customer_email }}</p>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Payment method</p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">{{ formatMethod(selectedPaymentDetail.payment_method) }}</p>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Amount</p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">${{ Number(selectedPaymentDetail.amount || 0).toFixed(2) }}</p>
                            </div>
                            <div class="rounded-2xl bg-[#F8FAFC] p-5 md:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Reference</p>
                                <p class="mt-2 text-base font-semibold text-[#111827]">{{ selectedPaymentDetail.transaction_ref || 'No reference' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Auto check</p>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Final result</p>
                                    <div class="mt-2 flex items-center gap-3">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase" :class="autoCheckClass(selectedPaymentDetail.auto_check_status)">
                                            {{ selectedPaymentDetail.auto_check_status || 'pending' }}
                                        </span>
                                        <span class="text-sm font-semibold text-[#111827]">{{ selectedPaymentDetail.auto_check_score ?? 0 }}%</span>
                                    </div>
                                </div>
                                <div class="rounded-2xl bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Auto checked at</p>
                                    <p class="mt-2 text-sm font-semibold text-[#111827]">{{ formatDateTime(selectedPaymentDetail.auto_checked_at) }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Amount match</p>
                                    <p class="mt-2 text-sm font-semibold" :class="booleanClass(selectedPaymentDetail.auto_check_result?.amount_match)">{{ yesNo(selectedPaymentDetail.auto_check_result?.amount_match) }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F8FAFC] p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">OCR engine</p>
                                    <p class="mt-2 text-sm font-semibold" :class="booleanClass(selectedPaymentDetail.auto_check_result?.ocr_available)">
                                        {{ selectedPaymentDetail.auto_check_result?.ocr_available ? 'Ready' : 'Unavailable' }}
                                    </p>
                                    <p v-if="selectedPaymentDetail.auto_check_result?.ocr_error" class="mt-2 text-xs text-rose-600">
                                        {{ selectedPaymentDetail.auto_check_result.ocr_error }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Uploaded payment screenshot</p>
                            <div class="mt-4 overflow-hidden rounded-2xl border border-[#E5E7EB] bg-[#F8FAFC]">
                                <img
                                    v-if="selectedPaymentDetail.screenshot_url"
                                    :src="selectedPaymentDetail.screenshot_url"
                                    alt="Payment proof screenshot"
                                    class="h-auto max-h-[360px] w-full object-contain bg-white"
                                >
                                <div v-else class="px-4 py-12 text-center text-sm text-[#6B7280]">No screenshot uploaded.</div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">OCR extracted text</p>
                            <pre class="mt-4 max-h-[240px] overflow-auto whitespace-pre-wrap rounded-2xl bg-[#F8FAFC] p-4 text-sm leading-6 text-[#111827]">{{ selectedPaymentDetail.ocr_text || 'No OCR text extracted.' }}</pre>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#E5E7EB] bg-[#FFFFFF] p-5">
                            <label class="text-xs font-semibold uppercase tracking-[0.12em] text-[#6B7280]">Admin note</label>
                            <textarea
                                v-model.trim="reviewForm.admin_note"
                                rows="4"
                                class="mt-3 w-full rounded-xl border border-[#E5E7EB] bg-white px-4 py-3 text-sm text-[#111827] outline-none placeholder:text-[#6B7280]"
                                placeholder="Write an approval or rejection reason"
                            />
                        </div>
                    </div>

                    <div v-if="selectedPaymentDetail" class="border-t border-[#E5E7EB] bg-white px-6 py-5">
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="rejecting || !reviewForm.admin_note"
                                @click="rejectPayment"
                            >
                                {{ rejecting ? 'Rejecting...' : 'Reject Payment' }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex min-h-[46px] items-center justify-center rounded-xl bg-[#A25F88] px-5 text-sm font-semibold text-white transition hover:bg-[#8E4F76] disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="approving"
                                @click="approvePayment"
                            >
                                {{ approving ? 'Approving...' : 'Approve Payment' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
import { buildFallbackMenu } from '../layout/adminMenuFallback.js';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/payments';
const screen = window.__APP_CONTEXT__?.screen ?? 'payment-records';
const roleScope = window.__APP_CONTEXT__?.role_scope ?? 'admin';
const openMenus = ref({});
const dashboard = ref(initialDashboard());
const loading = ref(false);
const detailLoading = ref(false);
const approving = ref(false);
const rejecting = ref(false);
const isReviewModalOpen = ref(false);
const notice = ref(null);
const payments = ref([]);
const selectedPaymentDetail = ref(null);
const filters = reactive({
    search: '',
    status: '',
    payment_method: '',
});
const reviewForm = reactive({
    admin_note: '',
});

const paymentMethods = computed(() => [...new Set(payments.value.map((payment) => payment.payment_method).filter(Boolean))]);

const filteredPayments = computed(() => payments.value.filter((payment) => {
    const query = filters.search.trim().toLowerCase();

    if (filters.status && payment.status !== filters.status) {
        return false;
    }

    if (filters.payment_method && payment.payment_method !== filters.payment_method) {
        return false;
    }

    if (!query) {
        return true;
    }

    return [
        payment.order_code,
        payment.customer_name,
        payment.transaction_ref,
    ].join(' ').toLowerCase().includes(query);
}));

const summaryCards = computed(() => [
    { label: 'Submitted', value: String(payments.value.filter((payment) => payment.status === 'submitted').length) },
    { label: 'Approved', value: String(payments.value.filter((payment) => payment.status === 'approved').length) },
    { label: 'Rejected', value: String(payments.value.filter((payment) => payment.status === 'rejected').length) },
]);

onMounted(loadPayments);

watch(
    () => [filters.status, filters.payment_method],
    async () => {
        await loadPayments();
    },
);

async function loadPayments() {
    loading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            params: {
                status: filters.status || undefined,
                payment_method: filters.payment_method || undefined,
            },
        });

        payments.value = response.data.payments ?? [];
        notice.value = null;
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load payments.') };
    } finally {
        loading.value = false;
    }
}

async function openReviewModal(payment) {
    isReviewModalOpen.value = true;
    detailLoading.value = true;
    selectedPaymentDetail.value = null;
    reviewForm.admin_note = '';

    try {
        const response = await window.axios.get(`${endpoint}/${payment.id}`);
        selectedPaymentDetail.value = response.data.payment;
        reviewForm.admin_note = response.data.payment.admin_note || '';
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to load payment detail.') };
        isReviewModalOpen.value = false;
    } finally {
        detailLoading.value = false;
    }
}

function closeReviewModal() {
    if (approving.value || rejecting.value) {
        return;
    }

    isReviewModalOpen.value = false;
    selectedPaymentDetail.value = null;
    reviewForm.admin_note = '';
}

async function approvePayment() {
    if (!selectedPaymentDetail.value) {
        return;
    }

    approving.value = true;

    try {
        await window.axios.post(`${endpoint}/${selectedPaymentDetail.value.id}/approve`, {
            admin_note: reviewForm.admin_note || null,
        });
        notice.value = { type: 'success', text: 'Payment approved successfully.' };
        closeReviewModal();
        await loadPayments();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to approve payment.') };
    } finally {
        approving.value = false;
    }
}

async function rejectPayment() {
    if (!selectedPaymentDetail.value || !reviewForm.admin_note) {
        return;
    }

    rejecting.value = true;

    try {
        await window.axios.post(`${endpoint}/${selectedPaymentDetail.value.id}/reject`, {
            admin_note: reviewForm.admin_note,
        });
        notice.value = { type: 'success', text: 'Payment rejected successfully.' };
        closeReviewModal();
        await loadPayments();
    } catch (error) {
        notice.value = { type: 'error', text: extractMessage(error, 'Unable to reject payment.') };
    } finally {
        rejecting.value = false;
    }
}

function formatMethod(value) {
    return {
        aba_qr: 'ABA QR',
        acleda: 'ACLEDA',
        wing: 'Wing',
        card: 'Card',
        cash: 'Cash',
    }[value] ?? value ?? 'Payment';
}

function statusClass(status) {
    return {
        submitted: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
        pending: 'bg-slate-100 text-slate-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}

function autoCheckClass(status) {
    return {
        auto_verified: 'bg-emerald-100 text-emerald-700',
        auto_failed: 'bg-amber-100 text-amber-700',
        pending: 'bg-slate-100 text-slate-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}

function yesNo(value) {
    return value ? 'Yes' : 'No';
}

function booleanClass(value) {
    return value ? 'text-emerald-700' : 'text-rose-700';
}

function formatDateTime(value) {
    if (!value) {
        return 'Pending';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        window.axios.post('/auth/logout').finally(() => {
            window.location.assign('/login');
        });
        return;
    }

    if (!item.path || item.is_enabled === false) {
        return;
    }

    window.location.href = item.path;
}

function syncOpenMenus(menuItems) {
    openMenus.value = menuItems.reduce((state, item) => {
        state[item.slug] = Boolean(item.is_expanded);
        return state;
    }, {});
}

function toggleMenu(slug) {
    openMenus.value = { ...openMenus.value, [slug]: !openMenus.value[slug] };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

function initialDashboard() {
    const menu = buildFallbackMenu(screen, roleScope);

    syncOpenMenus(menu);

    return {
        meta: {
            brand: 'E-commerce',
            page_title: 'Payments',
            kicker: 'Manual verification',
            subheadline: 'Review uploaded payment screenshots and approve or reject them manually.',
            primary_action_label: 'Refresh payments',
        },
        menu,
    };
}

function extractMessage(error, fallback) {
    return error?.response?.data?.message
        || Object.values(error?.response?.data?.errors ?? {}).flat()?.[0]
        || fallback;
}
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 180ms ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
