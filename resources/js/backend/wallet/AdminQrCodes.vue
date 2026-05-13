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
                    <div v-if="isLoading" class="rounded-[30px] border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                        Loading QR codes...
                    </div>

                    <template v-else>
                        <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <article v-for="card in statCards" :key="card.label" class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                            </article>
                        </section>

                        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                            <section class="rounded-[30px] border border-[#ead9e3] bg-[linear-gradient(180deg,#fff6fb_0%,#ffffff_100%)] px-6 py-6 shadow-sm">
                                <div class="rounded-[28px] bg-white p-5 shadow-sm">
                                    <div class="mx-auto flex w-full max-w-[260px] items-center justify-center rounded-[28px] bg-[#fcf7fa] p-4">
                                        <img
                                            v-if="paymentScreenshotPreview"
                                            :src="paymentScreenshotPreview"
                                            alt="Uploaded admin QR preview"
                                            class="mx-auto h-auto w-full rounded-[24px] border border-[#f0d9e6] bg-white object-contain"
                                        >
                                        <img
                                            v-else
                                            :src="preview.imageUrl"
                                            alt="Admin QR preview"
                                            class="mx-auto h-auto w-full rounded-[24px] border border-[#f0d9e6] bg-white object-contain"
                                        >
                                    </div>
                                    <div class="mt-4 flex justify-center">
                                        <button
                                            type="button"
                                            class="rounded-2xl bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
                                            @click="copyKhqr"
                                        >
                                            {{ copyButtonLabel }}
                                        </button>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                                <div class="space-y-4">
                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Amount</span>
                                        <input
                                            v-model="amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="field-input"
                                            placeholder="25.00"
                                        >
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Bank / Payment Provider</span>
                                        <select v-model="selectedBank" class="field-input">
                                            <option v-for="provider in providers" :key="provider.bank_name" :value="provider.bank_name">
                                                {{ provider.bank_name }}
                                            </option>
                                        </select>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Receiver</span>
                                        <input
                                            :value="preview.provider.account_name || '-'"
                                            type="text"
                                            class="field-input"
                                            readonly
                                        >
                                    </label>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="block space-y-2">
                                            <span class="text-sm font-semibold text-slate-700">Merchant Label</span>
                                            <input
                                                :value="preview.shopName"
                                                type="text"
                                                class="field-input"
                                                readonly
                                            >
                                        </label>
                                        <label class="block space-y-2">
                                            <span class="text-sm font-semibold text-slate-700">Account Number</span>
                                            <input
                                                :value="preview.provider.account_number || '-'"
                                                type="text"
                                                class="field-input"
                                                readonly
                                            >
                                        </label>
                                    </div>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Phone Number</span>
                                        <input
                                            :value="preview.provider.phone_number || '-'"
                                            type="text"
                                            class="field-input"
                                            readonly
                                        >
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">Upload QR</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="field-input file:mr-4 file:rounded-xl file:border-0 file:bg-[#A25F88] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                                            @change="handleQrUploadChange"
                                        >
                                    </label>

                                    <div v-if="paymentScreenshotName" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        Selected file: <span class="font-semibold text-slate-900">{{ paymentScreenshotName }}</span>
                                    </div>

                                    <label class="block space-y-2">
                                        <span class="text-sm font-semibold text-slate-700">QR Payload</span>
                                        <textarea
                                            :value="preview.khqrCode"
                                            rows="4"
                                            class="field-input resize-none font-mono text-xs"
                                            readonly
                                        />
                                    </label>
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
import { computed, onMounted, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/qr-codes';
const screen = window.__APP_CONTEXT__?.screen ?? 'qr-codes';
const isLoading = ref(true);
const copyButtonLabel = ref('Copy Link');
const paymentScreenshotName = ref('');
const paymentScreenshotPreview = ref('');
const openMenus = ref({});
const amount = ref('0.00');
const dashboard = ref({ meta: { brand: 'E-commerce' }, menu: [] });
const summary = ref({
    pending_deposits: 0,
    approved_deposits: 0,
    pending_withdrawals: 0,
    paid_withdrawals: 0,
});
const previewMerchant = ref({ shop_name: 'Merchant Shop' });
const providers = ref([]);
const selectedBank = ref('ABA');

const statCards = computed(() => [
    { label: 'Pending Deposits', value: String(summary.value.pending_deposits ?? 0) },
    { label: 'Approved Deposits', value: String(summary.value.approved_deposits ?? 0) },
    { label: 'Pending Withdrawals', value: String(summary.value.pending_withdrawals ?? 0) },
    { label: 'Paid Withdrawals', value: String(summary.value.paid_withdrawals ?? 0) },
]);

const preview = computed(() => {
    const provider = providers.value.find((item) => item.bank_name === selectedBank.value) ?? providers.value[0] ?? {
        bank_name: 'ABA',
        account_name: 'E-commerce KHQR Collection',
        account_number: '001 248 555',
        phone_number: '010 248 555',
        khqr_prefix: 'KHQR-ABA',
    };
    const shopName = previewMerchant.value.shop_name || 'Merchant Shop';
    const numericAmount = Number.parseFloat(amount.value || '0');
    const safeAmount = Number.isFinite(numericAmount) && numericAmount >= 0 ? numericAmount.toFixed(2) : '0.00';
    const khqrCode = [
        provider.khqr_prefix,
        `BANK:${provider.bank_name}`,
        `MERCHANT:${shopName}`,
        `ACCOUNT:${provider.account_number}`,
        `AMOUNT:${safeAmount}`,
        'COUNTRY:KH',
    ].join('|');

    return {
        bankName: provider.bank_name,
        shopName,
        provider,
        amount: safeAmount,
        khqrCode,
        imageUrl: buildDynamicQr(shopName, provider.bank_name, safeAmount, khqrCode),
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
        previewMerchant.value = response.data.deposit_preview?.merchant ?? previewMerchant.value;
        providers.value = response.data.deposit_preview?.providers ?? providers.value;
        selectedBank.value = providers.value[0]?.bank_name ?? selectedBank.value;
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

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

async function copyKhqr() {
    try {
        await window.navigator.clipboard.writeText(buildPreviewLink());
        copyButtonLabel.value = 'Copied';
        window.setTimeout(() => {
            copyButtonLabel.value = 'Copy Link';
        }, 1800);
    } catch {
        copyButtonLabel.value = 'Copy failed';
        window.setTimeout(() => {
            copyButtonLabel.value = 'Copy Link';
        }, 1800);
    }
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

function handleQrUploadChange(event) {
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

function persistPreviewImage() {
    if (!paymentScreenshotPreview.value) {
        return '';
    }

    const token = `admin-qr-${Date.now()}`;
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
    border-radius: 1.5rem;
    border: 1px solid #dbe4f0;
    background: #f7f9fc;
    padding: 0.95rem 1.25rem;
    font-size: 0.95rem;
    color: #0f172a;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.12);
}

.field-input[readonly] {
    color: #334155;
}
</style>
