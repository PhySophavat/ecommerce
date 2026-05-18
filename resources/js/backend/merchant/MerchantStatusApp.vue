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
                    @primary-action="refreshPage"
                    @refresh="refreshPage"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />
        </template>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <section class="rounded-[28px] border border-[#E5E7EB] bg-white px-6 py-6 shadow-[0_18px_44px_rgba(15,23,42,0.06)] sm:px-7 sm:py-7">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Profile</p>
                                <h2 class="mt-2 text-[2rem] font-bold tracking-[-0.04em] text-[#111827]">{{ merchant.shop_name }}</h2>
                                <p class="mt-2 text-sm leading-7 text-[#6B7280]">Merchant information.</p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <span class="rounded-full px-4 py-2 text-sm font-semibold" :class="statusClass(merchant.status)">
                                    {{ merchant.status }}
                                </span>
                                <span class="rounded-full px-4 py-2 text-sm font-semibold" :class="verificationClass(merchant.verification_status)">
                                    {{ merchant.verification_status }}
                                </span>
                            </div>
                        </div>

                        <section class="mt-6 rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-4 py-4 sm:px-5">
                            <div class="grid gap-3 lg:grid-cols-3 lg:gap-4">
                                <div
                                    v-for="item in profileSteps"
                                    :key="item.id"
                                    class="rounded-[18px] border border-[#E5E7EB] bg-white px-4 py-3 shadow-[0_8px_24px_rgba(15,23,42,0.04)]"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold"
                                            :class="stepCircleClass(item.id)"
                                        >
                                            {{ item.number }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-[#111827]">{{ item.label }}</p>
                                            <p class="text-xs text-[#6B7280]">{{ item.caption }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="mt-6 space-y-4">
                            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[#E8D3DF] bg-[#F7EEF4] text-sm font-semibold text-[#A25F88]">1</div>
                                    <div>
                                        <p class="text-sm font-medium text-[#94A3B8]">Step 1</p>
                                        <h3 class="text-[1.35rem] font-semibold tracking-[-0.03em] text-[#111827]">Business Information</h3>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 xl:grid-cols-[minmax(0,1.12fr),300px]">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Shop Name</p>
                                            <p class="mt-2 text-lg font-semibold text-[#111827]">{{ merchant.shop_name || '-' }}</p>
                                        </div>
                                        <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Business Type</p>
                                            <p class="mt-2 text-lg font-semibold text-[#111827]">{{ merchant.business_type || '-' }}</p>
                                        </div>
                                        <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)] sm:col-span-2">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Business Description</p>
                                            <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.business_description || '-' }}</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                                        <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Shop Logo</p>
                                            <img v-if="merchant.shop_logo" :src="storageUrl(merchant.shop_logo)" alt="Shop logo" class="mt-3 h-24 w-full rounded-[16px] object-cover">
                                            <p v-else class="mt-2 text-sm text-[#6B7280]">No image uploaded.</p>
                                        </div>
                                        <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Cover Image</p>
                                            <img v-if="merchant.cover_image" :src="storageUrl(merchant.cover_image)" alt="Cover image" class="mt-3 h-24 w-full rounded-[16px] object-cover">
                                            <p v-else class="mt-2 text-sm text-[#6B7280]">No image uploaded.</p>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[#E8D3DF] bg-[#F7EEF4] text-sm font-semibold text-[#A25F88]">2</div>
                                    <div>
                                        <p class="text-sm font-medium text-[#94A3B8]">Step 2</p>
                                        <h3 class="text-[1.35rem] font-semibold tracking-[-0.03em] text-[#111827]">Owner Information</h3>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Owner Name</p>
                                        <p class="mt-2 text-lg font-semibold text-[#111827]">{{ merchant.owner.name || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Email</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280] break-all">{{ merchant.owner.email || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Phone</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.owner.phone || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">ID Card</p>
                                        <a
                                            v-if="merchant.id_card_document"
                                            :href="storageUrl(merchant.id_card_document)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-2 inline-flex text-sm font-medium text-[#A25F88] hover:underline"
                                        >
                                            View uploaded document
                                        </a>
                                        <p v-else class="mt-2 text-sm text-[#6B7280]">No document uploaded.</p>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full border border-[#E8D3DF] bg-[#F7EEF4] text-sm font-semibold text-[#A25F88]">3</div>
                                    <div>
                                        <p class="text-sm font-medium text-[#94A3B8]">Step 3</p>
                                        <h3 class="text-[1.35rem] font-semibold tracking-[-0.03em] text-[#111827]">Location Information</h3>
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Province / City</p>
                                        <p class="mt-2 text-base font-semibold text-[#111827]">{{ merchant.location.province_city || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">District</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.location.district || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Commune</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.location.commune || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)] md:col-span-2 xl:col-span-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Full Address</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.location.full_address || '-' }}</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Google Map Link</p>
                                        <a
                                            v-if="merchant.location.google_map_link"
                                            :href="merchant.location.google_map_link"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-2 inline-flex text-sm font-medium text-[#A25F88] hover:underline break-all"
                                        >
                                            Open map
                                        </a>
                                        <p v-else class="mt-2 text-sm text-[#6B7280]">-</p>
                                    </div>
                                    <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_8px_18px_rgba(15,23,42,0.03)] md:col-span-2">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Delivery Area</p>
                                        <p class="mt-2 text-sm leading-7 text-[#6B7280]">{{ merchant.location.delivery_area || '-' }}</p>
                                    </div>
                                </div>
                            </article>

                            <article class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] px-5 py-5 shadow-[0_10px_24px_rgba(15,23,42,0.04)]">
                                <p class="text-sm font-semibold text-[#94A3B8]">Information</p>
                                <p class="mt-3 text-sm leading-7 text-[#6B7280]">{{ statusMessage }}</p>

                                <div
                                    v-if="merchant.rejection_reason"
                                    class="mt-4 rounded-[18px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm leading-7 text-rose-700"
                                >
                                    {{ merchant.rejection_reason }}
                                </div>
                            </article>
                        </div>
                    </section>
        </main>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminLayout from '../layout/AdminLayout.vue';

const context = window.__APP_CONTEXT__ ?? {};
const screen = context.screen ?? 'merchant-status';
const merchant = computed(() => context.merchant ?? {
    shop_name: '',
    status: 'Pending',
    verification_status: 'Pending',
    rejection_reason: null,
    owner: {},
    location: {},
});
const dashboard = computed(() => ({
    meta: context.meta ?? {
        brand: 'E-commerce',
        page_title: 'Merchant Profile',
        kicker: 'Profile',
        subheadline: 'View your merchant profile information.',
    },
    menu: context.menu ?? [],
}));
const openMenus = ref({});
const profileSteps = [
    { id: 'step1', number: 1, label: 'Business Info', caption: 'Shop and business details' },
    { id: 'step2', number: 2, label: 'Owner Info', caption: 'Owner and account details' },
    { id: 'step3', number: 3, label: 'Location', caption: 'Address and delivery details' },
];

const statusMessage = computed(() => {
    if (merchant.value.status === 'Rejected') {
        return 'Your merchant profile is rejected. Review the note below.';
    }

    if (merchant.value.status === 'Suspended') {
        return 'Your merchant profile is suspended.';
    }

    return 'Your merchant profile information is shown here.';
});

function toggleMenu(slug) {
    openMenus.value = {
        ...openMenus.value,
        [slug]: !openMenus.value[slug],
    };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        window.location.href = '/login';
        return;
    }

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

function refreshPage() {
    window.location.reload();
}

function statusClass(status) {
    return {
        Pending: 'border border-amber-200 bg-amber-50 text-amber-700',
        Rejected: 'border border-rose-200 bg-rose-50 text-rose-700',
        Suspended: 'border border-slate-200 bg-slate-100 text-slate-600',
        Approved: 'border border-emerald-200 bg-emerald-50 text-emerald-700',
    }[status] ?? 'border border-slate-200 bg-slate-100 text-slate-700';
}

function verificationClass(status) {
    return {
        Pending: 'border border-amber-200 bg-amber-50 text-amber-700',
        Verified: 'border border-indigo-200 bg-indigo-50 text-indigo-600',
        'Not Verified': 'border border-rose-200 bg-rose-50 text-rose-700',
    }[status] ?? 'border border-slate-200 bg-slate-100 text-slate-700';
}

function stepCircleClass(stepId) {
    return {
        step1: 'border-[#E8D3DF] bg-[#F7EEF4] text-[#A25F88]',
        step2: 'border-[#E8D3DF] bg-[#F7EEF4] text-[#A25F88]',
        step3: 'border-[#E8D3DF] bg-[#F7EEF4] text-[#A25F88]',
    }[stepId] ?? 'border-[#E5E7EB] bg-white text-[#6B7280]';
}

function storageUrl(path) {
    return `/storage/${path}`;
}
</script>
