<template>
    <aside class="hidden w-[300px] shrink-0 bg-[#EEF2F7] text-[#222] lg:flex lg:flex-col shadow-xl border-r border-[#e3e7ef]">
        <div class="px-5 pt-5">
            <div class="rounded-[30px] border border-[#e3e7ef] bg-white px-5 py-5 shadow-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8FAFC] text-base font-extrabold text-[#A25F88] shadow">
                        {{ brandInitials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#A0A4AE]">Admin</p>
                        <h1 class="truncate text-xl font-bold tracking-[-0.04em] text-[#222]">{{ dashboard.meta.brand }}</h1>
                    </div>
                </div>

                <div class="mt-5 relative">
                    <button type="button" class="flex w-full items-center justify-between rounded-2xl border border-[#e3e7ef] bg-[#F8FAFC] px-4 py-3 text-left text-sm font-semibold text-[#222] transition hover:bg-[#f0f3fa]">
                        <span>All branches</span>
                        <svg class="h-4 w-4 text-[#A0A4AE]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 8l4 4 4-4" />
                        </svg>
                    </button>
                    <!-- Dropdown can be implemented here if needed -->
                </div>
            </div>
        </div>

        <div class="soft-scroll flex-1 overflow-y-auto px-4 py-5">
            <nav class="space-y-1">
                <div v-for="item in dashboard.menu" :key="item.slug">
                    <button
                        v-if="item.children.length"
                        type="button"
                        class="group flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition text-sm"
                        :class="[parentItemClass(item), 'focus:ring-2 focus:ring-[#2563eb] outline-none']"
                        @click="$emit('toggle-menu', item.slug)"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg"
                              :class="parentIconWrapClass(item)">
                            <svg class="h-4 w-4"
                                 :class="parentIconClass(item)"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                        <svg
                            class="h-3 w-3 transition"
                            :class="[parentChevronClass(item), isMenuOpen(item.slug) ? 'rotate-180' : '']"
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
                        class="group flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left transition text-sm"
                        :class="[item.is_active ? 'bg-[#2563eb] text-white font-bold shadow' : 'hover:bg-[#e0eaff] text-[#222]/80', 'focus:ring-2 focus:ring-[#2563eb] outline-none']"
                        :disabled="!menuIsInteractive(item)"
                        @click="$emit('select-item', item)"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg"
                              :class="item.is_active ? 'bg-[#2563eb]' : 'bg-[#F8FAFC]'">
                            <svg class="h-4 w-4"
                                 :class="item.is_active ? 'text-white' : 'text-[#2563eb]'"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ item.label }}</span>
                    </button>
                    <div
                        v-if="item.children.length && isMenuOpen(item.slug)"
                        class="ml-3 mt-1 space-y-1 border-l-2 pl-3"
                        :class="hasActiveChild(item) ? 'border-[#bfd1ff]' : 'border-[#e3e7ef]'"
                    >
                        <button
                            v-for="child in item.children"
                            :key="child.slug"
                            type="button"
                            class="flex w-full items-center rounded px-2 py-1.5 text-left text-xs font-medium transition"
                            :class="[child.is_active ? 'bg-[#2563eb] text-white font-bold' : 'hover:bg-[#e0eaff] text-[#222]/70', 'focus:ring-2 focus:ring-[#2563eb] outline-none']"
                            :disabled="!menuIsInteractive(child)"
                            @click="$emit('select-item', child)"
                        >
                            <span class="truncate">{{ child.label }}</span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div class="px-5 pb-5">
            <div class="rounded-[30px] border border-[#e3e7ef] bg-white px-5 py-5 shadow">
                <div class="flex flex-col gap-2">
                    <a
                        class="flex items-center justify-between rounded-xl bg-[#A25F88] px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-[#8d4e74]"
                        :href="dashboard.meta.links.frontend"
                    >
                        <span>Storefront</span>
                        <span>&rarr;</span>
                    </a>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-xl bg-[#A25F88] px-4 py-3 text-sm font-semibold text-white shadow transition hover:bg-[#8d4e74]"
                        @click="emit('quick-action')"
                    >
                        <span>{{ quickActionLabel }}</span>
                        <span>&rarr;</span>
                    </button>
                </div>
                <div class="mt-5 border-t border-[#e3e7ef] pt-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#F8FAFC] text-sm font-bold text-[#A25F88]">
                            AD
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-[#222]">Admin workspace</p>
                            <p class="truncate text-xs text-[#A0A4AE]">{{ currentSectionLabel }}</p>
                        </div>
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';

const emit = defineEmits(['quick-action', 'scroll-add-product', 'select-item', 'toggle-menu']);

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

const brandInitials = computed(() => String(props.dashboard.meta.brand ?? 'AS')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((segment) => segment[0]?.toUpperCase() ?? '')
    .join('') || 'AS');
const currentSectionLabel = computed(() => ({
    sliders: 'Slides manager',
    dashboard: 'Overview dashboard',
    products: 'Product catalog',
    'featured-products': 'Featured catalog',
    'add-product': 'Product editor',
}[props.screen] ?? 'Admin workspace'));
const quickActionLabel = computed(() => ({
    sliders: 'Create slide',
    dashboard: 'View catalog',
    products: 'Add product',
    'featured-products': 'Add featured',
    'add-product': 'Open editor',
}[props.screen] ?? 'Open manager'));

function menuIsInteractive(item) {
    return item.is_enabled || item.slug === 'add-product';
}

function hasActiveChild(item) {
    return Array.isArray(item.children) && item.children.some((child) => child.is_active);
}

function parentItemClass(item) {
    if (hasActiveChild(item)) {
        return 'bg-[#e8efff] text-[#2c4ecf] font-semibold shadow-sm';
    }

    if (props.isMenuOpen(item.slug)) {
        return 'bg-white text-[#111827] shadow-sm ring-1 ring-[#dbe3f5]';
    }

    return 'hover:bg-[#e0eaff] text-[#222]/80';
}

function parentIconWrapClass(item) {
    if (hasActiveChild(item)) {
        return 'bg-white';
    }

    return props.isMenuOpen(item.slug)
        ? 'bg-[#F8FAFC]'
        : 'bg-[#F8FAFC]';
}

function parentIconClass(item) {
    return hasActiveChild(item) || props.isMenuOpen(item.slug)
        ? 'text-[#2563eb]'
        : 'text-[#2563eb]';
}

function parentChevronClass(item) {
    return hasActiveChild(item) || props.isMenuOpen(item.slug)
        ? 'text-[#2563eb]'
        : 'text-[#A0A4AE]';
}

function iconPath(icon) {
    return {
        dashboard: 'M4 12.5 12 4l8 8.5M6.5 10.5V20h11V10.5',
        sliders: 'M5 6h14M5 12h14M5 18h14',
        products: 'M4 7.5 12 3l8 4.5-8 4.5L4 7.5ZM4 7.5V16.5L12 21l8-4.5V7.5',
        orders: 'M7 7h10l2 3-7 7-7-7 2-3Zm5 10v4',
        customers: 'M16 19a4 4 0 0 0-8 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 7a3 3 0 0 0-3-3m-8-8a3 3 0 1 0-3-3m13 10a3 3 0 0 1 3 3',
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
