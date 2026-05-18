<template>
    <div class="chatgpt-admin min-h-screen bg-[#F8FAFC] px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-x-clip rounded-[36px] border border-[#E5E7EB] bg-[#F8FAFC] shadow-[0_24px_60px_rgba(15,23,42,0.08)]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-collapsed="isSidebarCollapsed"
                :is-menu-open="isMenuOpen"
                :menu-list="menuList"
                :screen="screen"
                :user-info="userInfo"
                :user-role="userRole"
                @quick-action="$emit('quick-action', $event)"
                @scroll-add-product="$emit('scroll-add-product', $event)"
                @select-item="$emit('select-item', $event)"
                @toggle-collapse="toggleSidebarCollapse"
                @toggle-menu="$emit('toggle-menu', $event)"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <slot name="header" />
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

import AdminSidebar from './AdminSidebar.vue';

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

const storageKey = 'admin-sidebar-collapsed';
const isSidebarCollapsed = ref(false);

onMounted(() => {
    try {
        isSidebarCollapsed.value = window.localStorage.getItem(storageKey) === 'true';
    } catch {
        isSidebarCollapsed.value = false;
    }
});

watch(isSidebarCollapsed, (value) => {
    try {
        window.localStorage.setItem(storageKey, String(value));
    } catch {
        // Ignore storage failures and keep the in-memory UI state.
    }
});

function toggleSidebarCollapse() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
}
</script>
