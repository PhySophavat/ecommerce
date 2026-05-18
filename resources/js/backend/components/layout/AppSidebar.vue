<template>
    <aside
        class="hidden h-screen shrink-0 self-start border-r border-[#E5E7EB] bg-[#F8FAFC] text-[#111827] transition-[width] duration-300 ease-out lg:sticky lg:top-0 lg:flex lg:flex-col"
        :class="isCollapsed ? 'w-[88px]' : 'w-[280px]'"
    >
        <div class="px-3 pt-4" :class="isCollapsed ? 'pb-2' : ''">
            <div class="rounded-[18px] border border-[#E5E7EB] bg-white px-3 py-3 shadow-[0_10px_24px_rgba(17,24,39,0.04)]">
                <div class="flex items-center gap-3" :class="isCollapsed ? 'justify-center' : ''">
                    <img
                        :src="logoUrl"
                        alt="Store logo"
                        class="h-10 w-10 rounded-[14px] border border-[#E5E7EB] bg-[#F8FAFC] p-1.5 shadow-[0_6px_14px_rgba(17,24,39,0.04)]"
                    >
                    <div v-if="!isCollapsed" class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-[#9CA3AF]">Backend access</p>
                        <h1 class="truncate text-base font-semibold tracking-[-0.03em] text-[#111827]">{{ brandName }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="soft-scroll flex-1 overflow-y-auto px-3 py-5">
            <nav class="space-y-1.5">
                <div v-for="item in menuItems" :key="item.slug">
                    <button
                        v-if="itemChildren(item).length"
                        type="button"
                        class="group relative flex h-12 w-full items-center rounded-[14px] text-left text-sm transition duration-200"
                        :class="[isCollapsed ? 'justify-center px-2' : 'gap-3 px-3 pl-5 pr-3', parentItemClass(item), 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        :title="isCollapsed ? item.label : null"
                        @click="$emit('toggle-menu', item.slug)"
                    >
                        <span
                            class="absolute left-1.5 top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-[#A25F88] transition"
                            :class="isParentMenuHighlighted(item) ? 'opacity-100' : 'opacity-0 group-hover:opacity-50'"
                        ></span>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" :class="parentIconWrapClass(item)">
                            <svg
                                class="h-5 w-5 shrink-0"
                                :class="parentIconClass(item)"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item)" />
                            </svg>
                        </span>
                        <span v-if="!isCollapsed" class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                        <svg
                            v-if="!isCollapsed"
                            class="h-4 w-4 shrink-0 transition"
                            :class="[parentChevronClass(item), isOpen(item) ? 'rotate-180' : '']"
                            viewBox="0 0 20 20"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4" />
                        </svg>
                    </button>
                    <button
                        v-else
                        type="button"
                        class="group relative flex h-12 w-full items-center rounded-[14px] text-left text-sm transition duration-200"
                        :class="[isCollapsed ? 'justify-center px-2' : 'gap-3 px-3 pl-5 pr-3', item.is_active ? 'bg-[#A25F88] font-semibold text-white shadow-[0_8px_18px_rgba(162,95,136,0.18)]' : 'text-[#111827] hover:bg-[rgba(162,95,136,0.08)] hover:text-[#A25F88]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        :title="isCollapsed ? item.label : null"
                        :disabled="menuIsInteractive(item) === false"
                        @click="$emit('select-item', item)"
                    >
                        <span
                            class="absolute left-1.5 top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-[#A25F88] transition"
                            :class="item.is_active ? 'opacity-100' : 'opacity-0 group-hover:opacity-50'"
                        ></span>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border" :class="item.is_active ? 'border-[rgba(162,95,136,0.45)] bg-white/12 shadow-[0_6px_14px_rgba(162,95,136,0.12)]' : 'border-[#E5E7EB] bg-white group-hover:border-[rgba(162,95,136,0.35)]'">
                            <svg
                                class="h-5 w-5 shrink-0"
                                :class="item.is_active ? 'text-white' : 'text-[#A25F88] group-hover:text-[#A25F88]'"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item)" />
                            </svg>
                        </span>
                        <span v-if="!isCollapsed" class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                    </button>
                    <div
                        v-if="!isCollapsed && itemChildren(item).length && isOpen(item)"
                        class="ml-5 mt-1.5 space-y-1 border-l pl-4"
                        :class="isParentMenuHighlighted(item) ? 'border-[rgba(162,95,136,0.35)]' : 'border-[#E5E7EB]'"
                    >
                        <button
                            v-for="child in itemChildren(item)"
                            :key="child.slug"
                            type="button"
                            class="group relative flex min-h-[38px] w-full items-center rounded-[12px] px-3 py-2 pl-4 text-left text-[13px] font-medium transition duration-200"
                            :class="[child.is_active ? 'bg-[#A25F88] font-semibold text-white shadow-[0_8px_16px_rgba(162,95,136,0.16)]' : 'text-[#6B7280] hover:bg-[rgba(162,95,136,0.08)] hover:text-[#A25F88]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                            :disabled="menuIsInteractive(child) === false"
                            @click="$emit('select-item', child)"
                        >
                            <span
                                class="absolute left-1.5 top-1/2 h-5 w-1 -translate-y-1/2 rounded-full bg-[#A25F88] transition"
                                :class="child.is_active ? 'opacity-100' : 'opacity-0 group-hover:opacity-50'"
                            ></span>
                            <span class="truncate">{{ child.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div class="mt-auto border-t border-[#E5E7EB] px-3 pb-4 pt-3">
            <div class="flex justify-center">
                <button
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-[14px] border border-[#E5E7EB] bg-white text-[#A25F88] transition hover:bg-[rgba(162,95,136,0.08)] focus:outline-none focus:ring-2 focus:ring-[#A25F88]"
                    @click="toggleCollapsedState"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path
                            v-if="isCollapsed"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 5h16v14H4V5Zm5 0v14m4-7h5M16 9l3 3-3 3"
                        />
                        <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 5h16v14H4V5Zm5 0v14m8-7h-5M8 9l-3 3 3 3"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';

import { buildFallbackMenu } from '../../layout/adminMenuFallback.js';

const emit = defineEmits(['quick-action', 'scroll-add-product', 'select-item', 'toggle-collapse', 'toggle-menu']);

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    isMenuOpen: {
        type: Function,
        required: true,
    },
    screen: {
        type: String,
        required: true,
    },
    isCollapsed: {
        type: [Boolean, Number],
        default: null,
    },
    userRole: {
        type: String,
        default: '',
    },
    userInfo: {
        type: Object,
        default: null,
    },
    menuList: {
        type: Array,
        default: null,
    },
});

const logoUrl = '/logo.jpg';
const appContext = window.__APP_CONTEXT__ ?? {};
const storageKey = 'admin-sidebar-collapsed';
const internalCollapsed = ref(false);
const resolvedRole = computed(() => props.userRole || props.dashboard?.role_scope || appContext.role_scope || appContext.currentUser?.role || 'admin');
const isCollapsed = computed(() => typeof props.isCollapsed === 'boolean' ? props.isCollapsed : internalCollapsed.value);
const brandName = computed(() => {
    if (resolvedRole.value === 'merchant') {
        return props.userInfo?.merchant?.shop_name
            || appContext.merchant?.shop_name
            || props.dashboard?.meta?.brand
            || 'Merchant Shop';
    }

    return props.dashboard?.meta?.brand || 'E-commerce';
});
const menuItems = computed(() => {
    const source = props.menuList ?? props.dashboard?.menu ?? [];
    const normalized = source.map(normalizeItem).filter(Boolean);

    if (normalized.length > 0) {
        return normalized;
    }

    return buildFallbackMenu(props.screen, resolvedRole.value).map(normalizeItem).filter(Boolean);
});

function normalizeItem(item) {
    if (!item || !item.slug) {
        return null;
    }

    return {
        ...item,
        children: Array.isArray(item.children) ? item.children.map(normalizeItem).filter(Boolean) : [],
    };
}

function itemChildren(item) {
    return Array.isArray(item?.children) ? item.children : [];
}

function menuIsInteractive(item) {
    return item.is_enabled || item.slug === 'add-product' || item.slug === 'logout';
}

onMounted(() => {
    if (typeof props.isCollapsed === 'boolean') {
        return;
    }

    try {
        internalCollapsed.value = window.localStorage.getItem(storageKey) === 'true';
    } catch {
        internalCollapsed.value = false;
    }
});

watch(internalCollapsed, (value) => {
    try {
        window.localStorage.setItem(storageKey, String(value));
    } catch {
        // Ignore persistence failures and keep the current UI state.
    }
});

function isParentMenuHighlighted(item) {
    return Boolean(item.is_active || hasActiveChild(item));
}

function hasActiveChild(item) {
    return itemChildren(item).some((child) => child.is_active);
}

function isOpen(item) {
    if ((props.dashboard?.menu ?? []).length === 0 && (props.menuList ?? []).length === 0) {
        return Boolean(item.is_expanded);
    }

    return props.isMenuOpen(item.slug);
}

function parentItemClass(item) {
    if (isParentMenuHighlighted(item)) {
        return 'bg-[#A25F88] font-semibold text-white shadow-[0_8px_18px_rgba(162,95,136,0.18)]';
    }

    return 'text-[#111827] hover:bg-[rgba(162,95,136,0.08)] hover:text-[#A25F88]';
}

function parentIconWrapClass(item) {
    return isParentMenuHighlighted(item) ? 'border-[rgba(162,95,136,0.45)] bg-white/12 shadow-[0_6px_14px_rgba(162,95,136,0.12)]' : 'border-[#E5E7EB] bg-white group-hover:border-[rgba(162,95,136,0.35)]';
}

function parentIconClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#A25F88]';
}

function parentChevronClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#9CA3AF]';
}

function toggleCollapsedState() {
    if (typeof props.isCollapsed === 'boolean') {
        emit('toggle-collapse');
        return;
    }

    internalCollapsed.value = !internalCollapsed.value;
    emit('toggle-collapse');
}

function iconPath(item) {
    const key = resolveIconKey(item);

    return {
        dashboard: 'M3 11.5 12 4l9 7.5M5.5 10.5V20h13V10.5M9 20v-5h6v5',
        sliders: 'M5 7h14M5 12h14M5 17h14M9 5v4M15 10v4M11 15v4',
        products: 'M12 3 4.5 7 12 11l7.5-4L12 3ZM4.5 7v10L12 21l7.5-4V7',
        orders: 'M4 6h2l2.2 9.2a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.8L21 8H8M10 18.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm9 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
        customers: 'M16 19a4 4 0 0 0-8 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7a3 3 0 0 0-3-3m-8-8a3 3 0 1 0-3-3m13 10a3 3 0 0 1 3 3',
        'finance-overview': 'M5 19V10m7 9V5m7 14v-7M3 19h18',
        'merchant-balance': 'M4 10 12 4l8 6v9H4v-9Zm3 2v5m5-5v5m5-5v5M2 20h20',
        wallet: 'M4 8.5A2.5 2.5 0 0 1 6.5 6H18a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6.5A2.5 2.5 0 0 1 4 15.5v-7ZM16 12h4m-3 0a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z',
        'qr-codes': 'M5 5h6v6H5V5Zm8 0h6v6h-6V5ZM5 13h6v6H5v-6Zm10 0h1m-1 3h4m-4 3h2m-6-2h2m2-2v-2h2v2h-2v2',
        'bank-accounts': 'M3 10 12 4l9 6M5 10v8m4-8v8m4-8v8m4-8v8M3 20h18',
        'platform-fee-settings': 'M12 5v14M7 8.5c0-1.93 2.24-3.5 5-3.5s5 1.57 5 3.5-2.24 3.5-5 3.5-5 1.57-5 3.5S9.24 19 12 19s5-1.57 5-3.5',
        settings: 'M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm7 3.5-.7-.4a1 1 0 0 1-.48-1.1l.2-.8-1.7-2.94-.82.2a1 1 0 0 1-1.06-.45L14 5h-4l-.37.9a1 1 0 0 1-1.06.45l-.82-.2-1.7 2.94.2.8a1 1 0 0 1-.48 1.1L5 12l.77.4a1 1 0 0 1 .48 1.1l-.2.8 1.7 2.94.82-.2a1 1 0 0 1 1.06.45L10 19h4l.37-.9a1 1 0 0 1 1.06-.45l.82.2 1.7-2.94-.2-.8a1 1 0 0 1 .48-1.1L19 12Z',
        logout: 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h9',
        promotions: 'm7 7 10 10M7 7h5v5H7V7Zm5 5 5-5',
        users: 'M16 18v-1a4 4 0 0 0-8 0v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 8v-1a3 3 0 0 0-2-2.83M4 19v-1a3 3 0 0 1 2-2.83',
        content: 'M4 5h16v14H4V5Zm4 0v14M4 10h16',
        notifications: 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0',
        reports: 'M5 19V9m7 10V5m7 14v-7M3 19h18',
        payments: 'M3 7.5h18v9H3v-9Zm0 3h18M7 14h3',
    }[key] ?? 'M12 5v14M5 12h14';
}

function resolveIconKey(item) {
    const icon = item?.icon;
    const slug = item?.slug;

    if (icon && [
        'dashboard',
        'sliders',
        'products',
        'orders',
        'customers',
        'wallet',
        'qr-codes',
        'bank-accounts',
        'settings',
        'logout',
        'users',
        'content',
        'notifications',
        'reports',
        'payments',
        'promotions',
    ].includes(icon)) {
        return icon;
    }

    return {
        dashboard: 'dashboard',
        products: 'products',
        orders: 'orders',
        customers: 'customers',
        'finance-overview': 'finance-overview',
        'merchant-balance': 'merchant-balance',
        'qr-codes': 'qr-codes',
        wallet: 'wallet',
        'bank-accounts': 'bank-accounts',
        'platform-fee-settings': 'platform-fee-settings',
        settings: 'settings',
        logout: 'logout',
    }[slug] ?? icon ?? slug ?? 'settings';
}
</script>
