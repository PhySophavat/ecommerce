<template>
    <aside class="hidden w-[290px] shrink-0 border-r border-slate-200/80 bg-white/90 lg:flex lg:flex-col">
        <div class="border-b border-slate-200/80 px-8 py-9">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#3457ff,#0f766e)] text-sm font-semibold text-white">
                    SC
                </div>
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Admin suite</p>
                    <h1 class="chatgpt-title mt-1 text-2xl text-slate-950">{{ dashboard.meta.brand }}</h1>
                </div>
            </div>
        </div>

        <div class="soft-scroll flex-1 overflow-y-auto px-5 py-6">
            <nav class="space-y-2">
                <div v-for="item in dashboard.menu" :key="item.slug" class="rounded-[24px]">
                    <button
                        v-if="item.children.length"
                        type="button"
                        class="group flex w-full items-center gap-3 rounded-[20px] px-4 py-3 text-left transition"
                        :class="menuItemClass(item)"
                        @click="$emit('toggle-menu', item.slug)"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            :class="isMenuOpen(item.slug) || item.is_active ? 'bg-[#eef3ff] text-[#3457ff]' : 'bg-slate-100 text-slate-500'"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ item.label }}</span>
                        <svg
                            class="h-4 w-4 text-slate-300 transition"
                            :class="isMenuOpen(item.slug) ? 'rotate-180 text-[#3457ff]' : ''"
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
                        class="group flex w-full items-center gap-3 rounded-[20px] px-4 py-3 text-left transition"
                        :class="menuItemClass(item)"
                        :disabled="!menuIsInteractive(item)"
                        @click="$emit('select-item', item)"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            :class="item.is_active ? 'bg-[#eef3ff] text-[#3457ff]' : 'bg-slate-100 text-slate-500'"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(item.icon)" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ item.label }}</span>
                    </button>

                    <div v-if="item.children.length && isMenuOpen(item.slug)" class="mt-1 space-y-1 border-l border-slate-200/80 pl-6">
                        <button
                            v-for="child in item.children"
                            :key="child.slug"
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl px-4 py-2.5 text-left text-sm transition"
                            :class="submenuItemClass(child)"
                            :disabled="!menuIsInteractive(child)"
                            @click="$emit('select-item', child)"
                        >
                            <span>{{ child.label }}</span>
                            <span
                                v-if="menuBadgeLabel(child)"
                                class="chatgpt-pill rounded-full border px-2 py-0.5 text-[10px] uppercase"
                                :class="child.slug === 'add-product' ? 'border-[#d7defe] bg-[#eef3ff] text-[#3457ff]' : 'border-slate-200 bg-slate-100 text-slate-400'"
                            >
                                {{ menuBadgeLabel(child) }}
                            </span>
                        </button>
                    </div>
                </div>
            </nav>
        </div>

        <div class="border-t border-slate-200/80 px-6 py-5">
            <div class="rounded-[24px] bg-[linear-gradient(135deg,#f5f7ff,#edfdf8)] px-5 py-4">
                <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Quick links</p>
                <div class="mt-4 space-y-2">
                    <a
                        class="flex items-center justify-between rounded-2xl bg-white/90 px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                        :href="dashboard.meta.links.frontend"
                    >
                        <span>Open storefront</span><span>&rarr;</span>
                    </a>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-2xl bg-white/90 px-4 py-3 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:text-slate-950"
                        @click="$emit('scroll-add-product')"
                    >
                        <span>Add product form</span><span>&rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
defineEmits(['scroll-add-product', 'select-item', 'toggle-menu']);

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

function menuIsInteractive(item) {
    return item.is_enabled || item.slug === 'add-product';
}

function menuBadgeLabel(item) {
    if (item.slug === 'add-product') {
        return 'Open';
    }

    return item.is_enabled ? '' : 'Soon';
}

function menuItemClass(item) {
    if (item.children.length) {
        return item.is_active || props.isMenuOpen(item.slug)
            ? 'bg-[linear-gradient(135deg,#f5f8ff,#eefdf9)] text-slate-900 shadow-[0_12px_24px_rgba(15,23,42,0.05)]'
            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900';
    }

    if (!menuIsInteractive(item)) {
        return 'cursor-not-allowed text-slate-300';
    }

    return item.is_active
        ? 'bg-[linear-gradient(135deg,#f5f8ff,#eefdf9)] text-slate-900 shadow-[0_12px_24px_rgba(15,23,42,0.05)]'
        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900';
}

function submenuItemClass(item) {
    if (!menuIsInteractive(item)) {
        return 'cursor-not-allowed text-slate-300';
    }

    return item.is_active
        ? 'bg-[#eef3ff] font-semibold text-[#3457ff]'
        : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900';
}

function iconPath(icon) {
    return {
        dashboard: 'M4 12.5 12 4l8 8.5M6.5 10.5V20h11V10.5',
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
