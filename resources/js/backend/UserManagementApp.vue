<template>
    <div class="relative isolate">
        <div class="pointer-events-none absolute inset-0 mesh-overlay"></div>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
            <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-teal-200/80">Backend Vue</p>
                        <div>
                            <h1 class="font-display text-4xl leading-tight text-stone-50 sm:text-5xl">
                                Manage users from the backend.
                            </h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-stone-200/78 sm:text-base">
                                This backend page uses Vue for the form and list, while Laravel still validates and stores user data.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-stone-100 transition hover:-translate-y-0.5 hover:bg-white/10"
                            href="/frontend"
                        >
                            Open frontend
                        </a>
                        <span class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-stone-100">
                            `/admin/users/create`
                        </span>
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

            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">Create user</p>
                            <h2 class="mt-2 font-display text-3xl text-stone-50">Add a new user.</h2>
                        </div>
                        <p class="text-sm text-stone-300/70">{{ users.count }} registered</p>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submitUser">
                        <label class="block space-y-2 text-sm">
                            <span class="text-stone-200/75">Name</span>
                            <input
                                v-model="form.name"
                                class="w-full rounded-2xl border px-4 py-3 text-stone-50 outline-none transition"
                                :class="fieldClass('name')"
                                type="text"
                                placeholder="Jamie Carter"
                                required
                            />
                            <p v-if="errors.name" class="text-xs text-rose-200">{{ errors.name[0] }}</p>
                        </label>

                        <label class="block space-y-2 text-sm">
                            <span class="text-stone-200/75">Email</span>
                            <input
                                v-model="form.email"
                                class="w-full rounded-2xl border px-4 py-3 text-stone-50 outline-none transition"
                                :class="fieldClass('email')"
                                type="email"
                                placeholder="jamie@example.com"
                                required
                            />
                            <p v-if="errors.email" class="text-xs text-rose-200">{{ errors.email[0] }}</p>
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block space-y-2 text-sm">
                                <span class="text-stone-200/75">Password</span>
                                <input
                                    v-model="form.password"
                                    class="w-full rounded-2xl border px-4 py-3 text-stone-50 outline-none transition"
                                    :class="fieldClass('password')"
                                    type="password"
                                    required
                                />
                                <p v-if="errors.password" class="text-xs text-rose-200">{{ errors.password[0] }}</p>
                            </label>

                            <label class="block space-y-2 text-sm">
                                <span class="text-stone-200/75">Confirm password</span>
                                <input
                                    v-model="form.password_confirmation"
                                    class="w-full rounded-2xl border border-white/10 bg-black/12 px-4 py-3 text-stone-50 outline-none transition focus:border-teal-300/40"
                                    type="password"
                                    required
                                />
                            </label>
                        </div>

                        <button
                            class="w-full rounded-full bg-stone-50 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-white disabled:cursor-not-allowed disabled:bg-stone-200"
                            type="submit"
                            :disabled="isSaving"
                        >
                            {{ isSaving ? 'Creating user...' : 'Create user' }}
                        </button>
                    </form>
                </section>

                <section class="glass-panel rounded-[2rem] px-6 py-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-stone-300/65">Backend users</p>
                            <h2 class="mt-2 font-display text-3xl text-stone-50">Current user list</h2>
                        </div>
                        <button
                            class="rounded-full border border-white/10 bg-white/6 px-4 py-2 text-sm text-stone-100 transition hover:-translate-y-0.5 hover:bg-white/10"
                            type="button"
                            :disabled="isLoading"
                            @click="loadUsers"
                        >
                            Refresh
                        </button>
                    </div>

                    <div
                        v-if="isLoading"
                        class="mt-6 rounded-[1.5rem] border border-white/10 bg-white/5 p-6 text-sm text-stone-300/70"
                    >
                        Loading users...
                    </div>

                    <div
                        v-else-if="users.items.length === 0"
                        class="mt-6 rounded-[1.5rem] border border-dashed border-white/10 bg-white/5 p-6 text-sm text-stone-300/70"
                    >
                        No users found.
                    </div>

                    <div v-else class="mt-6 space-y-4">
                        <article
                            v-for="user in users.items"
                            :key="`backend-user-${user.id}`"
                            class="rounded-[1.5rem] border border-white/10 bg-black/12 p-5"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.25em] text-stone-300/60">User</p>
                                    <h3 class="mt-2 font-display text-2xl text-stone-50">{{ user.name }}</h3>
                                    <p class="mt-2 text-sm text-stone-300/75">{{ user.email }}</p>
                                </div>
                                <p class="text-xs uppercase tracking-[0.2em] text-stone-400">
                                    {{ user.joined_at ?? 'unknown' }}
                                </p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';

const isLoading = ref(true);
const isSaving = ref(false);
const notice = ref(null);
const users = ref({
    count: 0,
    items: [],
});

const form = reactive(initialForm());
const errors = reactive({});

onMounted(async () => {
    await loadUsers();
});

async function loadUsers() {
    isLoading.value = true;

    try {
        const response = await window.axios.get('/api/frontend/home');
        users.value = response.data.users;
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to load users right now.'),
        };
    } finally {
        isLoading.value = false;
    }
}

async function submitUser() {
    isSaving.value = true;
    clearErrors();

    try {
        const response = await window.axios.post('/admin/users', { ...form }, {
            headers: {
                Accept: 'application/json',
            },
        });

        notice.value = {
            type: 'success',
            text: response.data.message,
        };

        resetForm();
        await loadUsers();
    } catch (error) {
        if (error?.response?.status === 422) {
            assignErrors(error.response.data.errors ?? {});
            notice.value = {
                type: 'error',
                text: 'Please correct the highlighted fields.',
            };
        } else {
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to create the user.'),
            };
        }
    } finally {
        isSaving.value = false;
    }
}

function fieldClass(field) {
    return errors[field]
        ? 'border-rose-300/40 bg-rose-500/10 focus:border-rose-300/50'
        : 'border-white/10 bg-black/12 focus:border-teal-300/40';
}

function assignErrors(validationErrors) {
    Object.entries(validationErrors).forEach(([field, fieldErrors]) => {
        errors[field] = fieldErrors;
    });
}

function clearErrors() {
    Object.keys(errors).forEach((field) => {
        delete errors[field];
    });
}

function resetForm() {
    form.name = '';
    form.email = '';
    form.password = '';
    form.password_confirmation = '';
}

function initialForm() {
    return {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    };
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
</script>
