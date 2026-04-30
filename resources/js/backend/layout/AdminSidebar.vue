<template>
    <aside class="hidden w-[300px] shrink-0 border-r border-[#e3e7ef] bg-[#EEF2F7] text-[#222] shadow-xl lg:flex lg:flex-col">
        <div class="px-5 pt-5">
            <div class="rounded-[30px] border border-[#e3e7ef] bg-white px-5 py-5 shadow-md">
                <div class="flex items-center gap-3">
                    <img
                        :src="logoUrl"
                        alt="Store logo"
                        class="h-12 w-12 rounded-2xl bg-[#F8FAFC] p-1 shadow"
                    >
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#A0A4AE]">{{ currentRoleLabel }}</p>
                        <h1 class="truncate text-xl font-bold tracking-[-0.04em] text-[#222]">{{ dashboard.meta.brand }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="soft-scroll flex-1 overflow-y-auto px-4 py-5">
            <nav class="space-y-1">
                <div v-for="item in menuItems" :key="item.slug">
                    <button
                        v-if="itemChildren(item).length"
                        type="button"
                        class="group flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="[parentItemClass(item), 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        @click="$emit('toggle-menu', item.slug)"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg" :class="parentIconWrapClass(item)">
                            <svg
                                class="h-4 w-4"
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
                            class="h-3 w-3 transition"
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
                        class="group flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="[item.is_active ? 'bg-[#A25F88] font-bold text-white shadow' : 'text-[#222]/80 hover:bg-[#F3E8F1]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                        :disabled="menuIsInteractive(item) === false"
                        @click="$emit('select-item', item)"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg" :class="item.is_active ? 'bg-[#A25F88]' : 'bg-[#F8FAFC]'">
                            <svg
                                class="h-4 w-4"
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
                        class="ml-3 mt-1 space-y-1 border-l-2 pl-3"
                        :class="isParentMenuHighlighted(item) ? 'border-[#A25F88]/40' : 'border-[#e3e7ef]'"
                    >
                        <button
                            v-for="child in itemChildren(item)"
                            :key="child.slug"
                            type="button"
                            class="flex w-full items-center rounded px-2 py-1.5 text-left text-xs font-medium transition"
                            :class="[child.is_active ? 'bg-[#A25F88] font-bold text-white' : 'text-[#222]/70 hover:bg-[#F3E8F1]', 'outline-none focus:ring-2 focus:ring-[#A25F88]']"
                            :disabled="menuIsInteractive(child) === false"
                            @click="$emit('select-item', child)"
                        >
                            <span class="truncate">{{ child.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div class="border-t border-[#dfe5f5] px-4 py-4">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-2xl bg-white px-3 py-3 text-left shadow-sm transition hover:bg-[#f8fafc]"
                @click="$emit('select-item', { slug: 'logout' })"
            >
                <img
                    v-if="currentUser?.profile_image"
                    :src="`/storage/${currentUser.profile_image}`"
                    :alt="currentUser.name || 'Signed-in user'"
                    class="h-11 w-11 rounded-full object-cover"
                >
                <span
                    v-else
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-[#edf3ff] text-sm font-bold text-[#355eea]"
                >
                    {{ currentUserInitials }}
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-slate-900">
                        {{ currentUser?.name || 'Admin User' }}
                    </span>
                    <span class="block truncate text-xs text-slate-500">
                        {{ currentUserSubtitle }}
                    </span>
                </span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';

import { buildFallbackMenu } from './adminMenuFallback.js';

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
});

const logoUrl = '/logo.jpg';
const currentUser = computed(() => window.__APP_CONTEXT__?.currentUser ?? null);
const currentUserInitials = computed(() => {
    const name = currentUser.value?.name?.trim();

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
    if (!currentUser.value) {
        return 'Sign out';
    }

    return currentUser.value.email || currentUser.value.role || 'Sign out';
});
const currentRoleLabel = computed(() => {
    const role = currentUser.value?.role;

    return role === 'merchant' ? 'Merchant' : 'Admin';
});
const menuItems = computed(() => {
    const normalized = (props.dashboard?.menu ?? []).map(normalizeItem).filter(Boolean);

    if (normalized.length > 0) {
        return normalized;
    }

    return buildFallbackMenu(props.screen).map(normalizeItem).filter(Boolean);
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
    if ((props.dashboard?.menu ?? []).length === 0) {
        return Boolean(item.is_expanded);
    }

    return props.isMenuOpen(item.slug);
}

function parentItemClass(item) {
    if (isParentMenuHighlighted(item)) {
        return 'bg-[#A25F88] font-bold text-white shadow';
    }

    return 'text-[#222]/80 hover:bg-[#F3E8F1]';
}

function parentIconWrapClass(item) {
    return isParentMenuHighlighted(item) ? 'bg-[#A25F88]' : 'bg-[#F8FAFC]';
}

function parentIconClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#A25F88]';
}

function parentChevronClass(item) {
    return isParentMenuHighlighted(item) ? 'text-white' : 'text-[#A0A4AE]';
}

function iconPath(icon) {
    return {
        dashboard: 'M4 12.5 12 4l8 8.5M6.5 10.5V20h11V10.5',
        sliders: 'M5 6h14M5 12h14M5 18h14',
        products: 'M4 7.5 12 3l8 4.5-8 4.5L4 7.5ZM4 7.5V16.5L12 21l8-4.5V7.5',
        orders: 'M7 7h10l2 3-7 7-7-7 2-3Zm5 10v4',
        customers: 'M16 19a4 4 0 0 0-8 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7a3 3 0 0 0-3-3m-8-8a3 3 0 1 0-3-3m13 10a3 3 0 0 1 3 3',
        wallet: 'M5 8.5h14v9H5v-9Zm2 2.5h5M15 13h2m-7 4v2m4-2v2',
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
