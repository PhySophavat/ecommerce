<template>
    <aside class="hidden w-[280px] shrink-0 self-start border-r border-[#E5E7EB] bg-[#EEF2F7] text-[#111827] lg:sticky lg:top-0 lg:flex lg:h-[calc(100vh-1rem)] lg:flex-col">
        <div class="px-4 pt-4">
            <div class="rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 shadow-[0_10px_24px_rgba(17,24,39,0.06)]">
                <div class="flex items-center gap-3">
                    <img
                        :src="logoUrl"
                        alt="Store logo"
                        class="h-11 w-11 rounded-[16px] border border-[#EEF2F7] bg-[#F8FAFC] p-1.5 shadow-[0_6px_16px_rgba(17,24,39,0.06)]"
                    >
                    <div class="min-w-0">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-[#9CA3AF]">Backend access</p>
                        <h1 class="truncate text-[1.1rem] font-semibold tracking-[-0.03em] text-[#111827]">{{ brandName }}</h1>
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
                        class="group flex h-[52px] w-full items-center gap-3 rounded-[16px] px-3.5 text-left text-sm transition duration-200"
                        :class="[parentItemClass(item), 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        @click="$emit('toggle-menu', item.slug)"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-[12px] border border-[#EEF2F7]" :class="parentIconWrapClass(item)">
                            <svg
                                class="h-[18px] w-[18px]"
                                :class="parentIconClass(item)"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                        <svg
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
                        class="group flex h-[52px] w-full items-center gap-3 rounded-[16px] px-3.5 text-left text-sm transition duration-200"
                        :class="[item.is_active ? 'bg-[linear-gradient(135deg,#A25F88,#8E4F76)] font-semibold text-white shadow-[0_12px_24px_rgba(162,95,136,0.24)]' : 'text-[#111827] hover:bg-[#F8FAFC]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        :disabled="menuIsInteractive(item) === false"
                        @click="$emit('select-item', item)"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-[12px] border border-[#EEF2F7]" :class="item.is_active ? 'bg-white/12 border-white/15' : 'bg-white'">
                            <svg
                                class="h-[18px] w-[18px]"
                                :class="item.is_active ? 'text-white' : 'text-[#A25F88]'"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                    </button>
                    <div
                        v-if="itemChildren(item).length && isOpen(item)"
                        class="ml-5 mt-1 space-y-1.5 border-l pl-4"
                        :class="isParentMenuHighlighted(item) ? 'border-[#D7B3C8]' : 'border-[#E5E7EB]'"
                    >
                        <button
                            v-for="child in itemChildren(item)"
                            :key="child.slug"
                            type="button"
                            class="flex min-h-[40px] w-full items-center rounded-[12px] px-3 py-2 text-left text-[13px] font-medium transition duration-200"
                            :class="[child.is_active ? 'bg-[#A25F88] font-semibold text-white shadow-[0_8px_18px_rgba(162,95,136,0.20)]' : 'text-[#6B7280] hover:bg-[#F8FAFC] hover:text-[#111827]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                            :disabled="menuIsInteractive(child) === false"
                            @click="$emit('select-item', child)"
                        >
                            <span class="truncate">{{ child.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div class="mt-auto border-t border-[#E5E7EB] px-3 pb-4 pt-4">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-[20px] border border-[#E5E7EB] bg-white px-4 py-4 text-left shadow-[0_10px_24px_rgba(17,24,39,0.06)] transition hover:bg-[#F8FAFC]"
                @click="$emit('select-item', { slug: 'logout' })"
            >
                <img
                    v-if="resolvedUser?.profile_image"
                    :src="`/storage/${resolvedUser.profile_image}`"
                    :alt="resolvedUser.name || 'Signed-in user'"
                    class="h-11 w-11 rounded-full object-cover"
                >
                <span
                    v-else
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-[#EEF2FF] text-sm font-bold text-[#4F46E5]"
                >
                    {{ currentUserInitials }}
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-[#111827]">
                        {{ resolvedUser?.name || 'Admin User' }}
                    </span>
                    <span class="block truncate text-xs text-[#6B7280]">
                        {{ currentUserSubtitle }}
                    </span>
                </span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';

import { buildFallbackMenu } from '../../layout/adminMenuFallback.js';

defineEmits(['quick-action', 'scroll-add-product', 'select-item', 'toggle-menu']);

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
const resolvedRole = computed(() => props.userRole || props.dashboard?.role_scope || appContext.role_scope || appContext.currentUser?.role || 'admin');
const resolvedUser = computed(() => props.userInfo ?? appContext.currentUser ?? null);
const currentUserInitials = computed(() => {
    const name = resolvedUser.value?.name?.trim();

    if (!name) {
        return 'AD';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});
const currentUserSubtitle = computed(() => {
    if (!resolvedUser.value) {
        return 'Sign out';
    }

    return resolvedUser.value.email || resolvedUser.value.role || 'Sign out';
});
const brandName = computed(() => {
    if (resolvedRole.value === 'merchant') {
        return resolvedUser.value?.merchant?.shop_name
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
        return 'bg-[linear-gradient(135deg,#A25F88,#8E4F76)] font-semibold text-white shadow-[0_12px_24px_rgba(162,95,136,0.24)]';
    }

    return 'text-[#111827] hover:bg-[#F8FAFC]';
}

function parentIconWrapClass(item) {
    return isParentMenuHighlighted(item) ? 'bg-white/12 border-white/15' : 'bg-white';
}

function parentIconClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#A25F88]';
}

function parentChevronClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#9CA3AF]';
}

function iconPath(icon) {
    return {
        dashboard: 'M4 12.5 12 4l8 8.5M6.5 10.5V20h11V10.5',
        sliders: 'M5 6h14M5 12h14M5 18h14',
        products: 'M4 7.5 12 3l8 4.5-8 4.5L4 7.5ZM4 7.5V16.5L12 21l8-4.5V7.5',
        orders: 'M7 7h10l2 3-7 7-7-7 2-3Zm5 10v4',
        customers: 'M16 19a4 4 0 0 0-8 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7a3 3 0 0 0-3-3m-8-8a3 3 0 1 0-3-3m13 10a3 3 0 0 1 3 3',
        wallet: 'M5 8.5h14v9H5v-9Zm2 2.5h5M15 13h2m-7 4v2m4-2v2',
        'qr-codes': 'M7 7h4v4H7V7Zm6 0h4v4h-4V7ZM7 13h4v4H7v-4Zm8 0h2m-2 4h2m-8 0h2M5 5h14v14H5V5Z',
        'bank-accounts': 'M4 7h16v10H4V7Zm2 3h6m4 0h2m-8 4h8M7 5h10',
        payments: 'M3 7.5h18v9H3v-9Zm0 3h18M7 14h3',
        promotions: 'm7 7 10 10M7 7h5v5H7V7Zm5 5 5-5',
        reports: 'M5 19V9m7 10V5m7 14v-7',
        users: 'M16 18v-1a4 4 0 0 0-8 0v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 8v-1a3 3 0 0 0-2-2.83M4 19v-1a3 3 0 0 1 2-2.83',
        content: 'M4 5h16v14H4V5Zm4 0v14M4 10h16',
        settings: 'M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm7 3.5-.7-.4a1 1 0 0 1-.48-1.1l.2-.8-1.7-2.94-.82.2a1 1 0 0 1-1.06-.45L14 5h-4l-.37.9a1 1 0 0 1-1.06.45l-.82-.2-1.7 2.94.2.8a1 1 0 0 1-.48 1.1L5 12l.77.4a1 1 0 0 1 .48 1.1l-.2.8 1.7 2.94.82-.2a1 1 0 0 1 1.06.45L10 19h4l.37-.9a1 1 0 0 1 1.06-.45l.82.2 1.7-2.94-.2-.8a1 1 0 0 1 .48-1.1L19 12Z',
        notifications: 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0',
        logout: 'M9 6V4h10v16H9v-2m4-6H4m0 0 3-3m-3 3 3 3',
    }[icon] ?? 'M12 5v14M5 12h14';
}
</script>
