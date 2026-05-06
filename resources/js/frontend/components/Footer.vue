<template>
    <footer class="mt-16 border-t border-[#E5E7EB] bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.3fr_repeat(3,1fr)] lg:px-8">
            <div>
                <div class="text-2xl font-black tracking-[-0.04em] text-[#111827]">{{ store.meta?.brand || 'Northstar Goods' }}</div>
                <p class="mt-4 max-w-sm text-sm leading-7 text-[#6B7280]">
                    Clean, modern shopping for everyday discovery. Browse trusted merchants, save favorites, and checkout in a smooth customer flow.
                </p>
            </div>

            <div v-for="section in sections" :key="section.title">
                <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-[#111827]">{{ section.title }}</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#6B7280]">
                    <li v-for="item in section.items" :key="item.label">
                        <RouterLink :to="item.to" class="transition hover:text-[#A25F88]">{{ item.label }}</RouterLink>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-[#E5E7EB] px-4 py-5 text-center text-sm text-[#94A3B8]">
            &copy; {{ new Date().getFullYear() }} {{ store.meta?.brand || 'Northstar Goods' }}. Built for a friendly storefront experience.
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();

const sections = computed(() => [
    {
        title: 'Shop',
        items: [
            { label: 'All products', to: '/shop' },
            { label: 'Featured picks', to: '/' },
            { label: 'Wishlist', to: '/wishlist' },
        ],
    },
    {
        title: 'Account',
        items: [
            { label: 'Login', to: '/login' },
            { label: 'Register', to: '/register' },
            { label: 'Profile', to: '/profile' },
        ],
    },
    {
        title: 'Orders',
        items: [
            { label: 'Cart', to: '/cart' },
            { label: 'Checkout', to: '/checkout' },
            { label: 'Order history', to: '/orders' },
        ],
    },
]);
</script>
