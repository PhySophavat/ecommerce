<template>
    <div class="relative isolate">
        <div class="pointer-events-none absolute inset-0 mesh-overlay"></div>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-200/80">
                            {{ frontend.meta.brand }}
                        </p>
                        <div>
                            <h1 class="font-display text-4xl leading-tight text-stone-50 sm:text-5xl">
                                {{ frontend.meta.headline }}
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-200/78 sm:text-base">
                                {{ frontend.meta.subheadline }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="stackItem in frontend.meta.stack"
                                :key="stackItem"
                                class="rounded-full border border-white/10 bg-white/6 px-3 py-1 text-xs uppercase tracking-[0.2em] text-stone-200/80"
                            >
                                {{ stackItem }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-stone-100 transition hover:-translate-y-0.5 hover:bg-white/10"
                            :href="frontend.links.frontend"
                        >
                            Frontend
                        </a>
                        <a
                            class="rounded-full bg-stone-50 px-4 py-2 text-sm font-medium text-stone-950 transition hover:-translate-y-0.5 hover:bg-white"
                            :href="frontend.links.admin_users"
                        >
                            Add user
                        </a>
                    </div>
                </div>
            </section>

            <section
                v-if="notice"
                :class="notice.type === 'error' ? 'border-rose-300/30 bg-rose-500/10 text-rose-100' : 'border-emerald-300/30 bg-emerald-500/10 text-emerald-100'"
                class="glass-panel mt-6 rounded-[1.5rem] border px-5 py-4 text-sm"
            >
                {{ notice.text }}
            </section>

            <section class="mt-6 grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
                <aside class="space-y-6">
                    <section class="glass-panel rounded-[2rem] px-6 py-6">
                        <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">User stats</p>
                        <div class="mt-5 space-y-4">
                            <article
                                v-for="stat in frontend.meta.stats"
                                :key="stat.label"
                                class="rounded-[1.5rem] border border-white/10 bg-black/12 p-4"
                            >
                                <p class="text-3xl font-semibold text-stone-50">{{ stat.value }}</p>
                                <p class="mt-2 text-xs uppercase tracking-[0.2em] text-stone-300/65">
                                    {{ stat.label }}
                                </p>
                            </article>
                        </div>
                    </section>

                    <section class="glass-panel rounded-[2rem] px-6 py-6">
                        <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">Directory</p>
                        <h2 class="mt-2 font-display text-3xl text-stone-50">
                            {{ frontend.users.count }} user{{ frontend.users.count === 1 ? '' : 's' }}
                        </h2>
                        <p class="mt-3 text-sm text-stone-300/70">
                            Open the Vue backend page to create users, then refresh this frontend page to see them here.
                        </p>
                    </section>
                </aside>

                <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">Frontend users</p>
                            <h2 class="mt-2 font-display text-3xl text-stone-50">All registered users.</h2>
                        </div>
                        <p class="text-sm text-stone-300/70">Sorted by newest first</p>
                    </div>

                    <div
                        v-if="isLoading"
                        class="mt-6 rounded-[1.5rem] border border-white/10 bg-white/5 p-6 text-sm text-stone-300/70"
                    >
                        Loading users...
                    </div>

                    <div
                        v-else-if="frontend.users.items.length === 0"
                        class="mt-6 rounded-[1.5rem] border border-dashed border-white/10 bg-white/5 p-6 text-sm text-stone-300/70"
                    >
                        No users have been created yet.
                    </div>

                    <div v-else class="mt-6 grid gap-4 md:grid-cols-2">
                        <article
                            v-for="user in frontend.users.items"
                            :key="`user-${user.id}`"
                            class="rounded-[1.75rem] border border-white/10 bg-black/12 p-5"
                        >
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-300/60">Registered user</p>
                            <h3 class="mt-3 font-display text-2xl text-stone-50">{{ user.name }}</h3>
                            <p class="mt-2 text-sm text-stone-300/75">{{ user.email }}</p>
                            <p class="mt-4 text-xs uppercase tracking-[0.2em] text-stone-400">
                                Joined {{ user.joined_at ?? 'unknown' }}
                            </p>
                        </article>
                    </div>
                </section>
            </section>
        </main>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const isLoading = ref(true);
const notice = ref(null);
const frontend = ref(initialFrontend());

onMounted(async () => {
    await loadUsers();
});

async function loadUsers() {
    isLoading.value = true;

    try {
        const response = await window.axios.get('/api/frontend/home');
        frontend.value = response.data;
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to load users right now.'),
        };
    } finally {
        isLoading.value = false;
    }
}

function extractMessage(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const firstError = Object.values(response.errors).flat()[0];

        if (firstError) {
            return firstError;
        }
    }

    return response?.message ?? fallback;
}

function initialFrontend() {
    return {
        meta: {
            brand: 'Northstar Users',
            headline: 'Frontend user directory powered by Vue.',
            subheadline: 'Loading users...',
            stack: [],
            stats: [],
        },
        links: {
            frontend: '/frontend',
            admin_users: '/admin/users/create',
        },
        users: {
            count: 0,
            items: [],
        },
    };
}
</script>
