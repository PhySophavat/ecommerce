<template>
    <header class="sticky top-0 z-50 border-b border-white/70 bg-[#F8FAFC]/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <RouterLink to="/" class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#A25F88] text-lg font-black text-white shadow-[0_10px_24px_rgba(162,95,136,0.28)]">
                    N
                </div>
                <div>
                    <div class="text-lg font-black tracking-[-0.04em] text-[#111827]">{{ store.meta?.brand || 'Northstar Goods' }}</div>
                    <div class="text-xs uppercase tracking-[0.18em] text-[#94A3B8]">Friendly commerce</div>
                </div>
            </RouterLink>

            <nav class="hidden items-center gap-1 lg:flex">
                <RouterLink v-for="link in links" :key="link.name" :to="link.to" class="rounded-full px-4 py-2 text-sm font-medium transition" :class="route.name === link.name ? 'bg-white text-[#111827] shadow-sm' : 'text-[#6B7280] hover:bg-white hover:text-[#111827]'">
                    {{ link.label }}
                </RouterLink>
            </nav>

            <div class="hidden min-w-0 flex-1 lg:block">
                <SearchBar v-model="search" placeholder="Search for products, merchants, or categories" />
            </div>

            <div class="ml-auto flex items-center gap-2">
                <RouterLink to="/wishlist" class="relative rounded-full border border-[#E5E7EB] bg-white p-3 text-[#6B7280] shadow-sm transition hover:border-[#A25F88] hover:text-[#A25F88]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 20.25-1.45-1.32C5.4 14.36 2 11.28 2 7.5 2 4.42 4.42 2 7.5 2c1.74 0 3.41.81 4.5 2.09A6 6 0 0 1 16.5 2C19.58 2 22 4.42 22 7.5c0 3.78-3.4 6.86-8.55 11.43L12 20.25Z" />
                    </svg>
                </RouterLink>

                <RouterLink to="/cart" class="relative rounded-full border border-[#E5E7EB] bg-white p-3 text-[#6B7280] shadow-sm transition hover:border-[#A25F88] hover:text-[#A25F88]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h1.5l1.8 10.2a2 2 0 0 0 2 1.65h8.95a2 2 0 0 0 1.96-1.57L21 6H6" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.5 20a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                    </svg>
                    <span v-if="store.cartCount" class="absolute -right-1 -top-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#A25F88] px-1 text-[11px] font-semibold text-white">
                        {{ store.cartCount }}
                    </span>
                </RouterLink>

                <template v-if="store.isAuthenticated">
                    <Button to="/orders" variant="secondary" size="sm" class="hidden md:inline-flex">My Orders</Button>
                    <Button to="/profile" variant="primary" size="sm" class="hidden md:inline-flex">{{ store.profile.name || 'Profile' }}</Button>
                </template>
                <template v-else>
                    <Button to="/login" variant="secondary" size="sm" class="hidden md:inline-flex">Login</Button>
                    <Button to="/register" variant="primary" size="sm" class="hidden md:inline-flex">Register</Button>
                </template>

                <button type="button" class="rounded-full border border-[#E5E7EB] bg-white p-3 text-[#6B7280] shadow-sm lg:hidden" @click="openMobile = !openMobile">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!openMobile" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div v-if="openMobile" class="border-t border-[#E5E7EB] bg-white px-4 py-4 shadow-sm lg:hidden">
            <SearchBar v-model="search" placeholder="Search storefront" />
            <nav class="mt-4 grid gap-2">
                <RouterLink v-for="link in links" :key="`mobile-${link.name}`" :to="link.to" class="rounded-2xl px-4 py-3 text-sm font-medium text-[#6B7280] hover:bg-[#F8FAFC] hover:text-[#111827]" @click="openMobile = false">
                    {{ link.label }}
                </RouterLink>
            </nav>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <Button v-if="!store.isAuthenticated" to="/login" variant="secondary" size="sm" block @click="openMobile = false">Login</Button>
                <Button v-if="!store.isAuthenticated" to="/register" variant="primary" size="sm" block @click="openMobile = false">Register</Button>
                <Button v-if="store.isAuthenticated" to="/orders" variant="secondary" size="sm" block @click="openMobile = false">My Orders</Button>
                <Button v-if="store.isAuthenticated" to="/profile" variant="primary" size="sm" block @click="openMobile = false">Profile</Button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';
import Button from './Button.vue';
import SearchBar from './SearchBar.vue';

const store = useStorefrontStore();
const route = useRoute();
const openMobile = ref(false);
const search = ref('');

const links = computed(() => [
    { name: 'home', label: 'Home', to: '/' },
    { name: 'shop', label: 'Shop', to: '/shop' },
    { name: 'wishlist', label: 'Wishlist', to: '/wishlist' },
    { name: 'orders', label: 'Orders', to: '/orders' },
    { name: 'profile', label: 'Profile', to: '/profile' },
]);

watch(
    () => route.fullPath,
    () => {
        openMobile.value = false;
    }
);
</script>
