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
                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading wallet overview...
                    </div>

                    <template v-else>
                       
                        <section class="mb-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                            <section class="rounded-[30px] border border-[#ead9e3] bg-[linear-gradient(180deg,#fff6fb_0%,#ffffff_100%)] px-6 py-6 shadow-sm">
                                <div class="rounded-[28px] bg-white p-5 shadow-sm">
                                    <div class="mx-auto flex w-full max-w-[260px] items-center justify-center rounded-[28px] bg-[#fcf7fa] p-4">
                                        <img
                                            v-if="paymentScreenshotPreview"
                                            :src="paymentScreenshotPreview"
                                            alt="KHQR preview"
                                            class="mx-auto h-auto w-full rounded-[24px] border border-[#f0d9e6] bg-white object-contain"
                                        >
                                        <div v-else class="flex min-h-[220px] w-full items-center justify-center rounded-[24px] border border-dashed border-[#ead9e3] bg-white px-4 text-center text-sm text-slate-400">
                                            Upload KHQR image
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-center">
                                        <button type="button" class="rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90" @click="copyKhqr">
                                            Copy Link
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                                <div class="space-y-4">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Amount</span>
                                        <input v-model="depositForm.amount" type="number" step="0.01" min="0.01" class="field-input" placeholder="25.00">
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Bank / Payment Provider</span>
                                        <select v-model="depositForm.bank_name" class="field-input">
                                            <option v-for="provider in previewProviders" :key="provider.bank_name" :value="provider.bank_name">
                                                {{ provider.bank_name }}
                                            </option>
                                        </select>
                                    </label>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="block space-y-2">
                                            <span class="text-sm font-semibold text-slate-700">Account Name</span>
                                            <input v-model="depositForm.account_name" type="text" class="field-input" placeholder="Merchant sender name">
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-sm font-semibold text-slate-700">Account Number</span>
                                            <input v-model="depositForm.account_number" type="text" class="field-input" placeholder="Merchant sender number">
                                        </label>
                                    </div>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Phone Number</span>
                                        <input v-model="depositForm.phone_number" type="text" class="field-input" placeholder="088 123 4567">
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Upload Payment</span>
                                        <input type="file" accept="image/*" class="field-input file:mr-4 file:rounded-xl file:border-0 file:bg-[#A25F88] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" @change="handleScreenshotChange">
                                    </label>

                                    <div v-if="payment" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Selected file: <span class="font-semibold text-slate-900">{{ paymentScreenshotName }}</span>
                                    </div>
                                </div>
                            </section>
                        </section>

                      
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/wallet';
const screen = window.__APP_CONTEXT__?.screen ?? 'wallet';
const isLoading = ref(true);
const openMenus = ref({});
const dashboard = ref({ meta: { brand: 'E-commerce' }, menu: [] });
const summary = ref({
    pending_deposits: 0,
    approved_deposits: 0,
    pending_withdrawals: 0,
    paid_withdrawals: 0,
});
const links = ref({
    deposits: '/admin/deposits',
    withdrawals: '/admin/withdrawals',
});
const previewMerchant = ref({ shop_name: 'Merchant Shop' });
const previewProviders = ref([]);
const paymentScreenshotName = ref('');
const paymentScreenshotPreview = ref('');
const depositForm = reactive({
    amount: '25.00',
    bank_name: 'ABA',
    account_name: '',
    account_number: '',
    phone_number: '',
});

const statCards = computed(() => [
    { label: 'Pending Deposits', value: String(summary.value.pending_deposits ?? 0) },
    { label: 'Approved Deposits', value: String(summary.value.approved_deposits ?? 0) },
    { label: 'Pending Withdrawals', value: String(summary.value.pending_withdrawals ?? 0) },
    { label: 'Paid Withdrawals', value: String(summary.value.paid_withdrawals ?? 0) },
]);
const selectedProvider = computed(() => {
    return previewProviders.value.find((provider) => provider.bank_name === depositForm.bank_name) ?? previewProviders.value[0] ?? {
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
    const shopName = previewMerchant.value.shop_name || 'Merchant Shop';
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
        imageUrl: buildDynamicQr(previewMerchant.value.shop_name || 'Merchant Shop', provider.bank_name, safeAmount.toFixed(2), khqrCode),
    };
});

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard() {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint);
        dashboard.value = {
            ...dashboard.value,
            meta: response.data.meta ?? dashboard.value.meta,
            menu: response.data.menu ?? [],
        };
        summary.value = response.data.summary ?? summary.value;
        links.value = response.data.links ?? links.value;
        previewMerchant.value = response.data.deposit_preview?.merchant ?? previewMerchant.value;
        previewProviders.value = response.data.deposit_preview?.providers ?? previewProviders.value;
        depositForm.bank_name = previewProviders.value[0]?.bank_name ?? depositForm.bank_name;
        syncOpenMenus(response.data.menu ?? []);
    } finally {
        isLoading.value = false;
    }
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
        await window.axios.post('/auth/logout');
        window.location.assign('/login');
        return;
    }

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

async function copyKhqr() {
    await window.navigator.clipboard.writeText(buildPreviewLink());
    window.alert('KHQR link copied.');
}

function handleScreenshotChange(event) {
    const file = event.target.files?.[0] ?? null;

    if (!file) {
        paymentScreenshotName.value = '';
        paymentScreenshotPreview.value = '';
        return;
    }

    paymentScreenshotName.value = file.name;

    const reader = new FileReader();
    reader.onload = () => {
        paymentScreenshotPreview.value = typeof reader.result === 'string' ? reader.result : '';
    };
    reader.readAsDataURL(file);
}

function currency(value) {
    const amount = Number.parseFloat(value ?? 0);
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number.isNaN(amount) ? 0 : amount);
}

function buildPreviewLink() {
    const url = new URL('/khqr-preview', window.location.origin);
    url.searchParams.set('bank', preview.value.bankName);
    url.searchParams.set('amount', preview.value.amount);
    url.searchParams.set('merchant', preview.value.shopName);
    url.searchParams.set('receiver', preview.value.provider.account_name ?? '');
    url.searchParams.set('code', preview.value.khqrCode);
    const token = persistPreviewImage();
    if (token) {
        url.searchParams.set('image_token', token);
    }

    return url.toString();
}

function persistPreviewImage() {
    if (!paymentScreenshotPreview.value) {
        return '';
    }

    const token = `wallet-${Date.now()}`;
    window.localStorage.setItem(`khqr_preview:${token}`, paymentScreenshotPreview.value);

    return token;
}

function buildDynamicQr(shopName, bankName, amount, qrText) {
    const modules = [];
    const size = 29;

    for (let y = 0; y < size; y += 1) {
        for (let x = 0; x < size; x += 1) {
            const seed = `${qrText}-${x}-${y}`;
            const active = isFinderZone(x, y, size) || hash(seed) % 2 === 0;

            if (active) {
                modules.push(`<rect x="${x * 10}" y="${x === 0 && y === 0 ? 0 : y * 10}" width="10" height="10" fill="#111111" />`);
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
