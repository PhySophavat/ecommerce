<template>
    <AppSidebar
        :dashboard="dashboard"
        :is-collapsed="isCollapsed"
        :is-menu-open="isMenuOpen"
        :menu-list="menuList"
        :screen="screen"
        :user-info="userInfo"
        :user-role="userRole"
        @quick-action="$emit('quick-action', $event)"
        @scroll-add-product="$emit('scroll-add-product', $event)"
        @select-item="$emit('select-item', $event)"
        @toggle-collapse="$emit('toggle-collapse')"
        @toggle-menu="$emit('toggle-menu', $event)"
    />
</template>

<script setup>
import AppSidebar from '../components/layout/AppSidebar.vue';

defineEmits(['quick-action', 'scroll-add-product', 'select-item', 'toggle-collapse', 'toggle-menu']);

defineProps({
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
</script>
