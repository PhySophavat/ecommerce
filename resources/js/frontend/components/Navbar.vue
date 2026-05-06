<template>
    <header class="border-b border-[#D9E6F2] bg-white">
        <div class="border-b border-[#E8EEF5] bg-[#F8FBFE] text-[#6B7280]">
            <div class="mx-auto flex max-w-[1240px] items-center justify-between gap-3 px-4 py-2 text-xs sm:px-6 lg:px-8">
                <p class="truncate font-medium">Welcome to worldwide {{ brandName }}!</p>
                <div class="hidden items-center gap-5 md:flex">
                    <RouterLink
                        v-for="link in utilityLinks"
                        :key="link.label"
                        :to="link.to"
                        class="inline-flex items-center gap-2 transition hover:text-[#1D9BF0]"
                    >
                        <span class="text-[#1D9BF0]" v-html="link.icon"></span>
                        <span>{{ link.label }}</span>
                    </RouterLink>
                </div>
            </div>
        </div>

        <div class="sticky top-0 z-50 border-b border-[#E8EEF5] bg-white/95 shadow-[0_8px_30px_rgba(15,23,42,0.04)] backdrop-blur-md">
            <div class="mx-auto max-w-[1240px] px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 py-4 lg:gap-5">
                    <button
                        type="button"
                        class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-[#D7E5F2] bg-[#EFF7FD] text-[#1495E8] transition hover:border-[#1495E8] hover:bg-[#E2F1FB]"
                        @click="openMobile = !openMobile"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!openMobile" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 7h16M4 12h16M4 17h10" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>

                    <RouterLink to="/" class="flex min-w-0 shrink-0 items-center gap-3">
                        <img
                            :src="logoUrl"
                            :alt="brandName"
                            class="h-12 w-12 rounded-2xl border border-[#D8E7F4] bg-white object-cover shadow-sm"
                        >
                        <div class="min-w-0">
                            <div class="truncate text-[2rem] font-black leading-none tracking-[-0.06em] text-[#1495E8]">
                                {{ brandWordmark }}
                            </div>
                        </div>
                    </RouterLink>

                    <form class="hidden min-w-0 flex-1 lg:block" @submit.prevent="submitSearch">
                        <label class="flex items-center gap-3 rounded-2xl border border-[#D8E7F4] bg-[#F3F9FD] px-4 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] transition focus-within:border-[#1495E8] focus-within:bg-white">
                            <span class="text-[#1495E8]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search essentials, groceries and more..."
                                class="min-w-0 flex-1 bg-transparent text-sm text-[#111827] outline-none placeholder:text-[#94A3B8]"
                            >
                            <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#1495E8] shadow-sm transition hover:bg-[#EAF5FD]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 6h16M4 12h16M4 18h16M17 4l3 3-3 3" />
                                </svg>
                            </button>
                        </label>
                    </form>

                    <div class="ml-auto flex shrink-0 items-center gap-2 text-[#374151] sm:gap-4">
                        <RouterLink
                            :to="store.isAuthenticated ? '/profile' : '/login'"
                            class="hidden items-center gap-2 border-r border-[#DDE7F1] pr-4 text-sm font-semibold transition hover:text-[#1495E8] md:inline-flex"
                        >
                            <svg class="h-5 w-5 text-[#1495E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 19.25a7.5 7.5 0 0 1 15 0" />
                            </svg>
                            <span>{{ accountLabel }}</span>
                        </RouterLink>

                        <RouterLink to="/cart" class="relative inline-flex items-center gap-2 text-sm font-semibold transition hover:text-[#1495E8]">
                            <svg class="h-5 w-5 text-[#1495E8]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M3 4h2l1.6 9.2a2 2 0 0 0 2 1.65h8.87a2 2 0 0 0 1.97-1.64L21 7H7.2" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" />
                            </svg>
                            <span>Cart</span>
                            <span
                                v-if="store.cartCount"
                                class="absolute -right-2 -top-2 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-[#1495E8] px-1 text-[11px] font-bold text-white"
                            >
                                {{ store.cartCount }}
                            </span>
                        </RouterLink>
                    </div>
                </div>

                <form class="pb-4 lg:hidden" @submit.prevent="submitSearch">
                    <label class="flex items-center gap-3 rounded-2xl border border-[#D8E7F4] bg-[#F3F9FD] px-4 py-3">
                        <span class="text-[#1495E8]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search essentials, groceries and more..."
                            class="min-w-0 flex-1 bg-transparent text-sm text-[#111827] outline-none placeholder:text-[#94A3B8]"
                        >
                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#1495E8] shadow-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 6h16M4 12h16M4 18h16M17 4l3 3-3 3" />
                            </svg>
                        </button>
                    </label>
                </form>
            </div>
        </div>

        <div class="mx-auto hidden max-w-[1240px] items-center gap-3 overflow-x-auto px-4 py-3 sm:px-6 lg:flex lg:px-8">
            <RouterLink
                v-for="category in categoryLinks"
                :key="category.slug"
                :to="category.to"
                class="inline-flex shrink-0 items-center gap-2 rounded-full px-5 py-3 text-sm font-medium transition"
                :class="category.slug === activeCategorySlug ? 'bg-[#1495E8] text-white shadow-[0_12px_24px_rgba(20,149,232,0.2)]' : 'bg-[#F2F7FB] text-[#374151] hover:bg-[#E4F1FB] hover:text-[#1495E8]'"
            >
                <span>{{ category.name }}</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="m6 9 6 6 6-6" />
                </svg>
            </RouterLink>
        </div>

        <div v-if="openMobile" class="border-t border-[#E8EEF5] bg-white px-4 py-4 shadow-sm lg:hidden">
            <div class="mx-auto max-w-[1240px]">
                <nav class="grid gap-2">
                    <RouterLink
                        v-for="category in categoryLinks"
                        :key="`mobile-${category.slug}`"
                        :to="category.to"
                        class="rounded-2xl bg-[#F2F7FB] px-4 py-3 text-sm font-medium text-[#374151] transition hover:bg-[#E4F1FB] hover:text-[#1495E8]"
                        @click="openMobile = false"
                    >
                        {{ category.name }}
                    </RouterLink>
                </nav>

                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    <RouterLink
                        v-for="link in utilityLinks"
                        :key="`utility-${link.label}`"
                        :to="link.to"
                        class="rounded-2xl border border-[#E5EDF4] px-4 py-3 text-sm font-medium text-[#6B7280] transition hover:border-[#1495E8] hover:text-[#1495E8]"
                        @click="openMobile = false"
                    >
                        {{ link.label }}
                    </RouterLink>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <RouterLink
                        v-if="!store.isAuthenticated"
                        to="/login"
                        class="rounded-2xl border border-[#D8E7F4] px-4 py-3 text-center text-sm font-semibold text-[#374151] transition hover:border-[#1495E8] hover:text-[#1495E8]"
                        @click="openMobile = false"
                    >
                        Login
                    </RouterLink>
                    <RouterLink
                        v-if="!store.isAuthenticated"
                        to="/register"
                        class="rounded-2xl bg-[#1495E8] px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#0D86D6]"
                        @click="openMobile = false"
                    >
                        Register
                    </RouterLink>
                    <RouterLink
                        v-if="store.isAuthenticated"
                        to="/orders"
                        class="rounded-2xl border border-[#D8E7F4] px-4 py-3 text-center text-sm font-semibold text-[#374151] transition hover:border-[#1495E8] hover:text-[#1495E8]"
                        @click="openMobile = false"
                    >
                        My Orders
                    </RouterLink>
                    <RouterLink
                        v-if="store.isAuthenticated"
                        to="/profile"
                        class="rounded-2xl bg-[#1495E8] px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#0D86D6]"
                        @click="openMobile = false"
                    >
                        Profile
                    </RouterLink>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const route = useRoute();
const router = useRouter();
const openMobile = ref(false);
const search = ref('');
const logoUrl = '/logo.jpg';

const fallbackCategories = [
    { slug: 'groceries', name: 'Groceries' },
    { slug: 'premium-fruits', name: 'Premium Fruits' },
    { slug: 'home-kitchen', name: 'Home & Kitchen' },
    { slug: 'fashion', name: 'Fashion' },
    { slug: 'electronics', name: 'Electronics' },
    { slug: 'beauty', name: 'Beauty' },
    { slug: 'home-improvement', name: 'Home Improvement' },
    { slug: 'sports-toys-luggage', name: 'Sports, Toys & Luggage' },
];

const utilityLinks = [
    {
        label: 'Deliver to 423651',
        to: '/shop',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 21s6-5.33 6-11a6 6 0 1 0-12 0c0 5.67 6 11 6 11Z" /><circle cx="12" cy="10" r="2.25" stroke-width="1.9" /></svg>',
    },
    {
        label: 'Track your order',
        to: '/orders',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M3 7h11v8H3zM14 10h3l4 4v1h-7zM7.5 18.5A1.5 1.5 0 1 0 7.5 15a1.5 1.5 0 0 0 0 3ZM17.5 18.5A1.5 1.5 0 1 0 17.5 15a1.5 1.5 0 0 0 0 3Z" /></svg>',
    },
    {
        label: 'All Offers',
        to: '/shop',
        icon: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 3 4.5 6v6c0 4.2 2.8 8.1 7.5 9 4.7-.9 7.5-4.8 7.5-9V6L12 3Zm0 4v5l3 1.5" /></svg>',
    },
];

const brandName = computed(() => store.meta?.brand || 'E-commerce');
const brandWordmark = computed(() => {
    const raw = String(brandName.value).trim();
    return raw || 'E-commerce';
});
const accountLabel = computed(() => (store.isAuthenticated ? (store.profile.name || 'My Account') : 'Sign Up/Sign In'));
const activeCategorySlug = computed(() => {
    const category = route.query.category;
    return typeof category === 'string' ? category : '';
});
const categoryLinks = computed(() => {
    const source = store.categories.length
        ? store.categories.slice(0, 8).map((category) => ({
            slug: category.slug,
            name: category.name,
        }))
        : fallbackCategories;

    return source.map((category) => ({
        ...category,
        to: { path: '/shop', query: { category: category.slug } },
    }));
});

watch(
    () => route.fullPath,
    () => {
        openMobile.value = false;
        search.value = typeof route.query.search === 'string' ? route.query.search : '';
    },
    { immediate: true }
);

function submitSearch() {
    const nextQuery = {};

    if (typeof route.query.category === 'string' && route.query.category) {
        nextQuery.category = route.query.category;
    }

    if (search.value.trim()) {
        nextQuery.search = search.value.trim();
    }

    router.push({
        path: '/shop',
        query: nextQuery,
    });
}
</script>
