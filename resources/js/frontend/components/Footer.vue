<template>
    <footer class="mt-16 border-t border-[rgba(255,255,255,0.18)] bg-[linear-gradient(135deg,#8E4F76_0%,#A25F88_45%,#C084FC_100%)]">
        <div class="mx-auto grid w-full lg:w-[80%] gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.3fr_repeat(3,1fr)] lg:px-8">
            <div>
                <div class="text-[1.65rem] font-bold tracking-[-0.03em] text-white">{{ store.meta?.brand || 'E-commerce' }}</div>
                <p class="mt-4 max-w-sm text-[15px] font-normal leading-8 text-[#FCE7F3]">
                    Clean, modern shopping for everyday discovery. Browse trusted merchants, save favorites, and checkout in a smooth customer flow.
                </p>
            </div>

            <div v-for="section in sections" :key="section.title">
                <h3 class="text-[13px] font-bold uppercase tracking-[0.18em] text-white">{{ section.title }}</h3>
                <ul class="mt-4 space-y-3 text-[15px] text-[#FCE7F3]">
                    <li v-for="item in section.items" :key="item.label">
                        <RouterLink :to="item.to" class="inline-flex font-medium transition duration-200 hover:translate-x-1 hover:text-white">{{ item.label }}</RouterLink>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-[rgba(255,255,255,0.18)] px-4 py-5 text-center text-[15px] text-[#FCE7F3]">
            &copy; {{ new Date().getFullYear() }} {{ store.meta?.brand || 'E-commerce' }}. Built for a friendly storefront experience.
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
