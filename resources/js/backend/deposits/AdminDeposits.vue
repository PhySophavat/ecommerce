<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-x-clip rounded-[36px]">
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
                    @primary-action="loadDashboard"
                    @refresh="loadDashboard"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-[26px] border px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading deposits...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-4">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Top-up queue</p>
                                    <h2 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Merchant deposit requests</h2>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="filter in filters"
                                        :key="filter.value"
                                        type="button"
                                        class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                        :class="selectedStatus === filter.value ? 'bg-[#A25F88] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                        @click="changeStatus(filter.value)"
                                    >
                                        {{ filter.label }}
                                    </button>
                                </div>
                            </div>

                            <div v-if="isMerchantUser" class="mt-6 grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
                                <section class="rounded-[28px] border border-[#ead9e3] bg-[linear-gradient(180deg,#fff6fb_0%,#ffffff_100%)] p-5 shadow-sm sm:p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#A25F88]">KHQR Preview</p>
                                    <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Scan and pay</h3>

                                    <div class="mt-6 rounded-[28px] bg-white p-5 shadow-sm">
                                        <div class="mx-auto flex w-full max-w-[260px] items-center justify-center rounded-[28px] bg-[#fcf7fa] p-4">
                                            <img
                                                :src="preview.imageUrl"
                                                alt="KHQR preview"
                                                class="mx-auto h-auto w-full rounded-[24px] border border-[#f0d9e6] bg-white object-contain"
                                            >
                                        </div>

                                        <div class="mt-4 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Merchant / Shop</p>
                                                <p class="mt-1 font-semibold text-slate-900">{{ merchantProfile.shop_name || 'Merchant Shop' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Bank</p>
                                                <p class="mt-1 font-semibold text-slate-900">{{ selectedProvider.bank_name }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Amount</p>
                                                <p class="mt-1 font-semibold text-slate-900">{{ currency(preview.amount) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Deposit Form</p>
                                    <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">Submit top-up request</h3>
                                    <p class="mt-2 text-sm text-slate-500">Enter amount, sender bank details, and upload payment proof.</p>

                                    <div class="mt-6 space-y-5">
                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">Amount</span>
                                            <input v-model="depositForm.amount" type="number" step="0.01" min="0.01" class="field-input" placeholder="25.00">
                                        </label>

                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">Bank / Payment Provider</span>
                                            <select v-model="depositForm.bank_name" class="field-input">
                                                <option v-for="provider in providers" :key="provider.bank_name" :value="provider.bank_name">
                                                    {{ provider.bank_name }}
                                                </option>
                                            </select>
                                        </label>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <label class="block">
                                                <span class="mb-2 block text-sm font-semibold text-slate-700">Account Name</span>
                                                <input v-model="depositForm.account_name" type="text" class="field-input" placeholder="Merchant sender name">
                                            </label>
                                            <label class="block">
                                                <span class="mb-2 block text-sm font-semibold text-slate-700">Account Number</span>
                                                <input v-model="depositForm.account_number" type="text" class="field-input" placeholder="Merchant sender number">
                                            </label>
                                        </div>

                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">Phone Number</span>
                                            <input v-model="depositForm.phone_number" type="text" class="field-input" placeholder="088 123 4567">
                                        </label>

                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">Note</span>
                                            <textarea v-model="depositForm.note" rows="3" class="field-input" placeholder="Optional deposit note" />
                                        </label>

                                        <label class="block">
                                            <span class="mb-2 block text-sm font-semibold text-slate-700">Upload Payment Proof</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="field-input file:mr-4 file:rounded-xl file:border-0 file:bg-[#A25F88] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                                @change="handleProofChange"
                                            >
                                        </label>

                                        <div class="rounded-2xl border border-dashed border-[#A25F88]/30 bg-[#fff7fb] px-4 py-4 text-sm text-slate-600">
                                            Submit your payment proof and it will be added to the table below immediately with pending status.
                                        </div>

                                        <button
                                            type="button"
                                            class="w-full rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isSubmitting || !canSubmitDeposit"
                                            @click="submitDeposit"
                                        >
                                            {{ isSubmitting ? 'Submitting...' : 'Submit Deposit Request' }}
                                        </button>
                                    </div>
                                </section>
                            </div>

                            <div v-if="deposits.length === 0" class="mt-6 rounded-[24px] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                                No deposits match this filter.
                            </div>

                            <div v-else class="mt-6 overflow-x-auto rounded-[28px] border border-slate-200">
                                <table class="min-w-[1080px] w-full text-sm">
                                    <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                        <tr>
                                            <th class="px-5 py-4">Merchant</th>
                                            <th class="px-4 py-4">Amount</th>
                                            <th class="px-4 py-4">Bank</th>
                                            <th class="px-4 py-4">Sender</th>
                                            <th class="px-4 py-4">Proof</th>
                                            <th class="px-4 py-4">Status</th>
                                            <th class="px-5 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in deposits" :key="item.id" class="border-t border-slate-200">
                                            <td class="px-5 py-4">
                                                <p class="font-bold text-slate-950">{{ item.merchant?.shop_name }}</p>
                                                <p class="text-slate-500">{{ item.merchant?.owner_name }} • {{ item.merchant?.email }}</p>
                                            </td>
                                            <td class="px-4 py-4 font-bold text-slate-950">{{ currency(item.amount) }}</td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p class="font-semibold text-slate-900">{{ item.bank_name }}</p>
                                                <p class="line-clamp-2 break-all text-xs">{{ item.khqr_code }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <p>{{ item.account_name }}</p>
                                                <p>{{ item.account_number }} • {{ item.phone_number }}</p>
                                            </td>
                                            <td class="px-4 py-4 text-slate-600">
                                                <a v-if="item.payment_proof_url" :href="item.payment_proof_url" target="_blank" class="font-semibold text-[#A25F88] hover:underline">View proof</a>
                                                <span v-else>-</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em]" :class="statusClass(item.status)">
                                                    {{ item.status }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'approve')"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        v-if="item.status === 'pending'"
                                                        type="button"
                                                        class="rounded-full bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500"
                                                        :disabled="processingId === item.id"
                                                        @click="runAction(item, 'reject')"
                                                    >
                                                        Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/deposits';
const screen = window.__APP_CONTEXT__?.screen ?? 'deposits';
const currentUser = ref(window.__APP_CONTEXT__?.currentUser ?? null);
const isLoading = ref(true);
const isSubmitting = ref(false);
const notice = ref(null);
const processingId = ref(null);
const selectedStatus = ref('all');
const openMenus = ref({});
const paymentProof = ref(null);
const dashboard = ref({
    meta: {
        brand: 'E-commerce',
        page_title: 'Deposits',
        kicker: 'Wallet top-ups',
        subheadline: 'Review merchant deposit proofs.',
        links: {
            frontend: '/',
            admin_users: '/admin/users',
            admin_merchants: '/admin/merchants',
            admin_deposits: '/admin/deposits',
            admin_withdrawals: '/admin/withdrawals',
            logout: '/auth/logout',
        },
    },
    menu: [],
});
const filters = ref([]);
const deposits = ref([]);
const providers = ref([]);
const merchantProfile = ref({
    shop_name: '',
    owner_name: '',
});
const summary = ref({ all: 0, pending: 0, approved: 0, rejected: 0 });
const depositForm = reactive({
    amount: '25.00',
    bank_name: 'ABA',
    account_name: '',
    account_number: '',
    phone_number: '',
    note: '',
});

const isMerchantUser = computed(() => currentUser.value?.role === 'merchant');
const statCards = computed(() => [
    { label: 'All', value: String(summary.value.all ?? 0) },
    { label: 'Pending', value: String(summary.value.pending ?? 0) },
    { label: 'Approved', value: String(summary.value.approved ?? 0) },
    { label: 'Rejected', value: String(summary.value.rejected ?? 0) },
]);
const selectedProvider = computed(() => {
    return providers.value.find((provider) => provider.bank_name === depositForm.bank_name) ?? providers.value[0] ?? {
        bank_name: 'ABA',
        account_name: 'E-commerce KHQR Collection',
        account_number: '001 248 555',
        phone_number: '010 248 555',
        khqr_prefix: 'KHQR-ABA',
    };
});
const preview = computed(() => {
    const amount = Number.parseFloat(depositForm.amount || '0');
    const safeAmount = Number.isFinite(amount) && amount > 0 ? amount : 0;
    const provider = selectedProvider.value;
    const shopName = merchantProfile.value.shop_name || 'Merchant Shop';
    const khqrCode = [
        provider.khqr_prefix,
        `BANK:${provider.bank_name}`,
        `MERCHANT:${shopName}`,
        `ACCOUNT:${provider.account_number}`,
        `AMOUNT:${safeAmount.toFixed(2)}`,
        'COUNTRY:KH',
    ].join('|');

    return {
        amount: safeAmount,
        imageUrl: buildDynamicQr(shopName, provider.bank_name, safeAmount.toFixed(2), khqrCode),
    };
});
const canSubmitDeposit = computed(() => {
    const amount = Number.parseFloat(depositForm.amount || '0');

    return isMerchantUser.value
        && !Number.isNaN(amount)
        && amount > 0
        && depositForm.bank_name.trim() !== ''
        && depositForm.account_name.trim() !== ''
        && depositForm.account_number.trim() !== ''
        && depositForm.phone_number.trim() !== ''
        && paymentProof.value !== null;
});

onMounted(async () => {
    await loadInitialState();
});

async function loadInitialState() {
    await loadDashboard();

    if (isMerchantUser.value) {
        await loadMerchantDepositForm();
    }
}

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint, { params: { status: selectedStatus.value } });
        summary.value = response.data.summary ?? summary.value;
        filters.value = response.data.filters ?? [];
        selectedStatus.value = response.data.selected_status ?? selectedStatus.value;
        deposits.value = response.data.deposits ?? [];
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? dashboard.value.menu,
        };
        syncOpenMenus(response.data.menu ?? []);
    } catch (error) {
        showError(error, 'Unable to load deposits.');
    } finally {
        isLoading.value = false;
    }
}

async function loadMerchantDepositForm() {
    const response = await window.axios.get('/api/merchant/deposits');
    merchantProfile.value = response.data.merchant ?? merchantProfile.value;
    providers.value = response.data.providers ?? [];
    depositForm.bank_name = providers.value[0]?.bank_name ?? depositForm.bank_name;
}

async function changeStatus(status) {
    selectedStatus.value = status;
    await loadDashboard();
}

async function submitDeposit() {
    if (!canSubmitDeposit.value) {
        notice.value = {
            type: 'error',
            text: 'Fill in amount, bank details, and payment proof first.',
        };
        return;
    }

    isSubmitting.value = true;

    try {
        const payload = new FormData();
        payload.append('amount', depositForm.amount);
        payload.append('bank_name', depositForm.bank_name);
        payload.append('account_name', depositForm.account_name);
        payload.append('account_number', depositForm.account_number);
        payload.append('phone_number', depositForm.phone_number);
        payload.append('note', depositForm.note);
        payload.append('payment_proof', paymentProof.value);

        const response = await window.axios.post('/api/merchant/deposits', payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        const createdDeposit = response.data.deposit ?? null;

        notice.value = {
            type: 'success',
            text: response.data.message ?? 'Deposit request submitted successfully.',
        };

        if (createdDeposit) {
            if (selectedStatus.value !== 'all' && selectedStatus.value !== createdDeposit.status) {
                selectedStatus.value = 'all';
            }

            deposits.value = [
                createdDeposit,
                ...deposits.value.filter((item) => item.id !== createdDeposit.id),
            ];
        }

        depositForm.amount = '25.00';
        depositForm.account_name = '';
        depositForm.account_number = '';
        depositForm.phone_number = '';
        depositForm.note = '';
        paymentProof.value = null;

        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to submit deposit request.');
    } finally {
        isSubmitting.value = false;
    }
}

function handleProofChange(event) {
    paymentProof.value = event.target.files?.[0] ?? null;
}

async function runAction(item, action) {
    const adminNote = window.prompt('Optional admin note:', '') ?? '';
    processingId.value = item.id;

    try {
        const response = await window.axios.put(`/api/admin/deposits/${item.id}/${action}`, { admin_note: adminNote });
        notice.value = { type: 'success', text: response.data.message ?? 'Deposit updated.' };
        await loadDashboard();
    } catch (error) {
        showError(error, 'Unable to update deposit.');
    } finally {
        processingId.value = null;
    }
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

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        await window.axios.post('/auth/logout');
        window.location.assign('/login');
        return;
    }

    if (!item.path || !item.is_enabled) return;
    window.location.href = item.path;
}

function showError(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const first = Object.values(response.errors).flat()[0];
        notice.value = { type: 'error', text: first ?? fallback };
        return;
    }

    notice.value = { type: 'error', text: response?.message ?? fallback };
}

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function statusClass(status) {
    return {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    }[status] ?? 'bg-slate-100 text-slate-700';
}

function buildDynamicQr(shopName, bankName, amount, qrText) {
    const modules = [];
    const size = 29;

    for (let y = 0; y < size; y += 1) {
        for (let x = 0; x < size; x += 1) {
            const seed = `${qrText}-${x}-${y}`;
            const active = isFinderZone(x, y, size) || hash(seed) % 2 === 0;

            if (active) {
                modules.push(`<rect x="${x * 10}" y="${y * 10}" width="10" height="10" fill="#111111" />`);
            }
        }
    }

    const safeShop = escapeXml(String(shopName).slice(0, 22));
    const safeAmount = escapeXml(amount);
    const safeBank = escapeXml(bankName);

    const svg = `
        <svg xmlns="http://www.w3.org/2000/svg" width="520" height="760" viewBox="0 0 520 760">
            <rect width="520" height="760" rx="36" fill="#ffffff"/>
            <rect x="54" y="48" width="412" height="74" rx="24" fill="#ee1717"/>
            <text x="260" y="93" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="800" fill="#ffffff">KHQR</text>
            <text x="92" y="176" font-family="Arial, sans-serif" font-size="20" font-weight="800" fill="#111111">${safeShop}</text>
            <text x="92" y="230" font-family="Arial, sans-serif" font-size="54" font-weight="900" fill="#111111">$${safeAmount}</text>
            <line x1="54" y1="266" x2="466" y2="266" stroke="#cbd5e1" stroke-dasharray="8 8" stroke-width="2"/>
            <rect x="80" y="292" width="360" height="360" rx="18" fill="#ffffff"/>
            <g transform="translate(115 327)">
                ${modules.join('')}
            </g>
            <circle cx="260" cy="507" r="34" fill="#ef2020"/>
            <circle cx="260" cy="507" r="22" fill="#ffffff"/>
            <circle cx="260" cy="507" r="16" fill="#ef2020"/>
            <text x="260" y="708" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#64748b">${safeBank}</text>
        </svg>
    `.trim();

    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function isFinderZone(x, y, size) {
    const topLeft = x < 7 && y < 7;
    const topRight = x >= size - 7 && y < 7;
    const bottomLeft = x < 7 && y >= size - 7;

    return topLeft || topRight || bottomLeft;
}

function hash(value) {
    let result = 0;

    for (let index = 0; index < value.length; index += 1) {
        result = ((result << 5) - result) + value.charCodeAt(index);
        result |= 0;
    }

    return Math.abs(result);
}

function escapeXml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&apos;');
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
