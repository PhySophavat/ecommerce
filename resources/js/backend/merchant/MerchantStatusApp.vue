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
                    @primary-action="refreshPage"
                    @refresh="refreshPage"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section class="rounded-[30px] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Profile</p>
                                <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-slate-950">{{ merchant.shop_name }}</h2>
                                <p class="mt-2 text-sm text-slate-500">Merchant information.</p>
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

                        <div class="mt-6 grid gap-5 xl:grid-cols-2">
                            <article class="rounded-[26px] border border-slate-200 bg-slate-50 px-5 py-5">
                                <p class="text-sm font-semibold text-slate-400">Owner</p>
                                <p class="mt-3 text-2xl font-bold tracking-[-0.03em] text-slate-950">{{ merchant.owner.name || '-' }}</p>
                                <p class="mt-2 text-slate-600">{{ merchant.owner.email || '-' }}</p>
                                <p class="text-slate-600">{{ merchant.owner.phone || '-' }}</p>
                            </article>

                            <article class="rounded-[26px] border border-slate-200 bg-slate-50 px-5 py-5">
                                <p class="text-sm font-semibold text-slate-400">Shop</p>
                                <p class="mt-3 text-2xl font-bold tracking-[-0.03em] text-slate-950">{{ merchant.status || '-' }}</p>
                                <p class="mt-2 text-slate-600">{{ merchant.location.province_city || '-' }}</p>
                                <p class="text-slate-600">{{ merchant.location.full_address || '-' }}</p>
                            </article>
                        </div>

                        <article class="mt-6 rounded-[26px] border border-slate-200 bg-slate-50 px-5 py-5">
                            <p class="text-sm font-semibold text-slate-400">Information</p>
                            <p class="mt-4 text-lg leading-8 text-slate-800">{{ statusMessage }}</p>

                            <div
                                v-if="merchant.rejection_reason"
                                class="mt-5 rounded-[22px] border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700"
                            >
                                {{ merchant.rejection_reason }}
                            </div>
                        </article>
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';

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
        Pending: 'bg-[#fff1b8] text-[#a16207]',
        Rejected: 'bg-[#ffd7de] text-[#be3455]',
        Suspended: 'bg-[#e8ecf3] text-[#5b6474]',
        Approved: 'bg-[#c9f3dd] text-[#047857]',
    }[status] ?? 'bg-slate-100 text-slate-700';
}

function verificationClass(status) {
    return {
        Pending: 'bg-[#fff1b8] text-[#a16207]',
        Verified: 'bg-[#d9def8] text-[#4f5fcf]',
        'Not Verified': 'bg-[#ffd7de] text-[#be3455]',
    }[status] ?? 'bg-slate-100 text-slate-700';
}
</script>
