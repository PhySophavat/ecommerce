<template>
    <footer class="mt-16 border-t border-[#E5E7EB] bg-[#FCFAFB]">
        <div class="mx-auto w-full max-w-[1280px] px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[1.25fr_repeat(3,minmax(0,1fr))]">
                <div>
                    <div class="inline-flex items-center rounded-full border border-[rgba(162,95,136,0.14)] bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#A25F88]">
                        {{ store.meta?.brand || 'E-commerce' }}
                    </div>
                    <h2 class="mt-4 text-[1.7rem] font-black tracking-[-0.04em] text-[#111827]">
                        A cleaner storefront for modern shopping.
                    </h2>
                    <p class="mt-4 max-w-md text-[15px] leading-7 text-[#6B7280]">
                        Clean, modern shopping for everyday discovery. Browse trusted merchants, save favorites, and checkout in a smooth customer flow.
                    </p>
                </div>

                <div v-for="section in sections" :key="section.title">
                    <h3 class="text-[12px] font-bold uppercase tracking-[0.18em] text-[#94A3B8]">{{ section.title }}</h3>
                    <ul class="mt-4 space-y-3">
                        <li v-for="item in section.items" :key="item.label">
                            <RouterLink
                                :to="item.to"
                                class="inline-flex text-[15px] font-medium text-[#475569] transition duration-200 hover:text-[#A25F88]"
                            >
                                {{ item.label }}
                            </RouterLink>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="border-t border-[#E5E7EB] bg-white">
            <div class="mx-auto flex w-full max-w-[1280px] flex-col gap-3 px-4 py-4 text-sm text-[#6B7280] sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                <p>&copy; {{ new Date().getFullYear() }} {{ store.meta?.brand || 'E-commerce' }}. Crafted with precision for a smarter shopping experience.</p>
                <div class="flex flex-wrap items-center gap-4 text-[13px]">
                    <RouterLink to="/shop" class="transition hover:text-[#A25F88]">Shop</RouterLink>
                    <RouterLink to="/orders" class="transition hover:text-[#A25F88]">Orders</RouterLink>
                    <RouterLink to="/profile" class="transition hover:text-[#A25F88]">Account</RouterLink>
                </div>
            </div>
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
