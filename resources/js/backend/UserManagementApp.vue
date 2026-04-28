<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <ProductSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @quick-action="scrollToForm"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <ProductHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="scrollToForm"
                    @refresh="loadDashboard"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="admin-frosted mb-6 rounded-[26px] px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="admin-card rounded-[30px] px-6 py-14 text-center text-sm text-slate-500">
                        Loading {{ roleLabel.toLowerCase() }} data...
                    </div>

                    <template v-else>
                        <section class="mb-6 flex flex-wrap items-center gap-3">
                            <a
                                v-for="link in switchLinks"
                                :key="link.screen"
                                :href="link.href"
                                class="rounded-2xl border px-4 py-2.5 text-sm font-semibold transition"
                                :class="link.screen === screen ? 'border-[#A25F88] bg-[#F3E8F1] text-[#A25F88]' : 'border-[#dfe5f5] bg-white text-slate-600 hover:border-[#c7d0ef] hover:text-slate-900'"
                            >
                                {{ link.label }}
                            </a>
                        </section>

                        <section class="grid gap-4 xl:grid-cols-4">
                            <article
                                v-for="card in summaryCards"
                                :key="card.label"
                                class="admin-card rounded-[28px] px-5 py-5"
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</p>
                                <p class="mt-3 text-3xl font-extrabold tracking-[-0.05em] text-slate-950">{{ card.value }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-500">{{ card.detail }}</p>
                            </article>
                        </section>

                        <div class="mt-6 grid gap-6 xl:grid-cols-[380px,minmax(0,1fr)]">
                            <section ref="formSection" class="admin-card rounded-[30px] px-6 py-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Create {{ roleLabel.toLowerCase() }}</p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                            {{ submitLabel }}
                                        </h3>
                                    </div>

                                    <div class="rounded-2xl bg-[#F3E8F1] px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#A25F88]">
                                        {{ accountCount }} total
                                    </div>
                                </div>

                                <form class="mt-6 space-y-4" @submit.prevent="submitAccount">
                                    <label class="block space-y-2">
                                        <span class="chatgpt-label text-sm">Name</span>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 transition"
                                            :class="fieldClass('name')"
                                            :placeholder="screen === 'merchants' ? 'Northstar Seller' : 'Admin User'"
                                            required
                                        />
                                        <p v-if="errors.name" class="text-xs text-rose-600">{{ errors.name[0] }}</p>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="chatgpt-label text-sm">Email</span>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 transition"
                                            :class="fieldClass('email')"
                                            :placeholder="screen === 'merchants' ? 'seller@example.com' : 'admin@example.com'"
                                            required
                                        />
                                        <p v-if="errors.email" class="text-xs text-rose-600">{{ errors.email[0] }}</p>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="chatgpt-label text-sm">Password</span>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 transition"
                                            :class="fieldClass('password')"
                                            placeholder="Minimum 8 characters"
                                            required
                                        />
                                        <p v-if="errors.password" class="text-xs text-rose-600">{{ errors.password[0] }}</p>
                                    </label>

                                    <label class="block space-y-2">
                                        <span class="chatgpt-label text-sm">Confirm password</span>
                                        <input
                                            v-model="form.password_confirmation"
                                            type="password"
                                            class="w-full rounded-2xl border px-4 py-3 text-sm text-slate-900 transition"
                                            :class="fieldClass('password_confirmation')"
                                            placeholder="Repeat password"
                                            required
                                        />
                                        <p v-if="errors.password_confirmation" class="text-xs text-rose-600">{{ errors.password_confirmation[0] }}</p>
                                    </label>

                                    <div class="rounded-[24px] border border-[#ead9e3] bg-[#fcf7fa] px-4 py-4 text-sm text-slate-600">
                                        <p class="font-semibold text-slate-900">Access role</p>
                                        <p class="mt-1 leading-6">
                                            This form creates a <span class="font-semibold text-[#A25F88]">{{ roleLabel.toLowerCase() }}</span>
                                            account and gives backend access for that role.
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        class="admin-primary-button w-full rounded-2xl px-5 py-3 text-sm font-semibold transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="isSaving"
                                    >
                                        {{ isSaving ? `${submitLabel}...` : submitLabel }}
                                    </button>
                                </form>
                            </section>

                            <section class="admin-card rounded-[30px] px-6 py-6">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                            {{ screen === 'merchants' ? 'Seller directory' : 'Backend access directory' }}
                                        </p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                            {{ accountCount }} {{ screen === 'merchants' ? 'merchant accounts' : 'admin accounts' }}
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="admin-secondary-button rounded-2xl px-4 py-3 text-sm font-semibold transition hover:-translate-y-0.5"
                                        @click="loadDashboard"
                                    >
                                        Refresh directory
                                    </button>
                                </div>

                                <div v-if="accountRows.length === 0" class="admin-muted-panel mt-6 rounded-[24px] px-5 py-6 text-sm text-slate-500">
                                    No {{ screen === 'merchants' ? 'merchant' : 'admin' }} accounts found yet.
                                </div>

                                <div v-else class="mt-6 space-y-4">
                                    <article
                                        v-for="account in accountRows"
                                        :key="`account-${account.id}`"
                                        class="rounded-[28px] border border-[#e3e9f7] bg-[#fbfcff] px-5 py-5"
                                    >
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                                            <div class="flex min-w-0 items-start gap-4">
                                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#F3E8F1] text-sm font-extrabold text-[#A25F88]">
                                                    {{ account.initials }}
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <h4 class="truncate text-lg font-bold tracking-[-0.03em] text-slate-950">
                                                            {{ account.name }}
                                                        </h4>
                                                        <span
                                                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]"
                                                            :class="screen === 'merchants' ? 'bg-amber-100 text-amber-700' : 'bg-[#F3E8F1] text-[#A25F88]'"
                                                        >
                                                            {{ account.role }}
                                                        </span>
                                                        <span
                                                            v-if="isCurrentUser(account)"
                                                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700"
                                                        >
                                                            You
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 truncate text-sm text-slate-500">{{ account.email }}</p>
                                                    <p class="mt-2 text-xs font-medium uppercase tracking-[0.16em] text-slate-400">
                                                        Joined {{ account.joined_at }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[420px]">
                                                <div
                                                    v-for="metric in accountMetrics(account)"
                                                    :key="`${account.id}-${metric.label}`"
                                                    class="rounded-[22px] border border-[#e6ebf8] bg-white px-4 py-3"
                                                >
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                        {{ metric.label }}
                                                    </p>
                                                    <p class="mt-2 text-xl font-bold tracking-[-0.03em] text-slate-950">
                                                        {{ metric.value }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                    :disabled="deletingUserId === account.id || isCurrentUser(account)"
                                                    @click="deleteAccount(account)"
                                                >
                                                    {{ deletingUserId === account.id ? 'Deleting...' : 'Delete' }}
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </section>
                        </div>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

import ProductHeader from './products/sections/ProductHeader.vue';
import ProductSidebar from './products/sections/ProductSidebar.vue';

const screen = normalizeScreen(window.__APP_CONTEXT__?.screen);
const endpoint = window.__APP_CONTEXT__?.endpoint ?? (screen === 'merchants' ? '/admin/merchants' : '/admin/users');
const currentUserId = currentSignedInUserId();
const formSection = ref(null);
const dashboard = ref(initialDashboard(screen));
const deletingUserId = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const notice = ref(null);
const openMenus = ref({});
const form = reactive(initialForm(screen));
const errors = reactive({});

const accountCount = computed(() => dashboard.value.accounts?.count ?? 0);
const accountRows = computed(() => dashboard.value.accounts?.items ?? []);
const roleLabel = computed(() => dashboard.value.form?.role_label ?? defaultRoleLabel(screen));
const submitLabel = computed(() => dashboard.value.form?.submit_label ?? defaultSubmitLabel(screen));
const summaryCards = computed(() => dashboard.value.accounts?.summary ?? []);
const switchLinks = computed(() => [
    {
        screen: 'users',
        label: 'Admin users',
        href: dashboard.value.meta?.links?.admin_users ?? '/admin/users',
    },
    {
        screen: 'merchants',
        label: 'Merchants',
        href: dashboard.value.meta?.links?.admin_merchants ?? '/admin/merchants',
    },
]);

onMounted(async () => {
    await loadDashboard();
});

async function loadDashboard({ preserveNotice = false } = {}) {
    isLoading.value = true;

    try {
        const response = await window.axios.get(endpoint, {
            headers: {
                Accept: 'application/json',
            },
        });

        dashboard.value = response.data;
        form.role = response.data.form?.role ?? defaultRole(screen);
        syncOpenMenus(response.data.menu ?? []);

        if (!preserveNotice) {
            notice.value = null;
        }
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to load this directory right now.'),
        };
    } finally {
        isLoading.value = false;
    }
}

function syncOpenMenus(menuItems) {
    openMenus.value = menuItems.reduce((state, item) => {
        state[item.slug] = Boolean(item.is_expanded);

        return state;
    }, {});
}

function toggleMenu(slug) {
    openMenus.value = {
        ...openMenus.value,
        [slug]: !openMenus.value[slug],
    };
}

function isMenuOpen(slug) {
    return Boolean(openMenus.value[slug]);
}

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        await submitLogout();

        return;
    }

    if (!item.path || !(item.is_enabled || item.slug === 'logout')) {
        return;
    }

    const url = new URL(item.path, window.location.origin);

    if (url.pathname === window.location.pathname && !url.hash) {
        return;
    }

    if (url.pathname === window.location.pathname && url.hash) {
        document.querySelector(url.hash)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });

        return;
    }

    window.location.href = `${url.pathname}${url.search}${url.hash}`;
}

async function submitLogout() {
    try {
        const logoutUrl = dashboard.value?.meta?.links?.logout ?? '/auth/logout';

        await window.axios.post(logoutUrl);
        window.location.assign('/login');
    } catch (error) {
        notice.value = {
            type: 'error',
            text: 'Unable to sign out right now.',
        };
    }
}

async function submitAccount() {
    isSaving.value = true;
    clearErrors();
    notice.value = null;

    try {
        const response = await window.axios.post('/admin/users', {
            ...form,
            role: dashboard.value.form?.role ?? defaultRole(screen),
        }, {
            headers: {
                Accept: 'application/json',
            },
        });

        resetForm();
        await loadDashboard({ preserveNotice: true });
        await nextTick();

        notice.value = {
            type: 'success',
            text: response.data?.message ?? `${roleLabel.value} was created successfully.`,
        };

        formSection.value?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    } catch (error) {
        if (error?.response?.status === 422 && error.response.data?.errors) {
            assignErrors(error.response.data.errors);
        }

        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to create that account right now.'),
        };
    } finally {
        isSaving.value = false;
    }
}

async function deleteAccount(account) {
    if (!account?.id || isCurrentUser(account)) {
        return;
    }

    const confirmed = window.confirm(`Delete ${account.name}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    deletingUserId.value = account.id;

    try {
        const response = await window.axios.delete(`/admin/users/${account.id}`, {
            headers: {
                Accept: 'application/json',
            },
        });

        await loadDashboard({ preserveNotice: true });

        notice.value = {
            type: 'success',
            text: response.data?.message ?? `${account.name} was deleted successfully.`,
        };
    } catch (error) {
        notice.value = {
            type: 'error',
            text: extractMessage(error, 'Unable to delete that account right now.'),
        };
    } finally {
        deletingUserId.value = null;
    }
}

function scrollToForm() {
    formSection.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

function accountMetrics(account) {
    if (screen === 'merchants') {
        return [
            {
                label: 'Products',
                value: account.products_count,
            },
            {
                label: 'Pending',
                value: account.pending_products_count,
            },
            {
                label: 'Approved',
                value: account.approved_products_count,
            },
        ];
    }

    return [
        {
            label: 'Approved products',
            value: account.approved_products_count,
        },
        {
            label: 'Email',
            value: '@' + String(account.email ?? '').split('@')[0],
        },
        {
            label: 'Joined',
            value: account.joined_at,
        },
    ];
}

function isCurrentUser(account) {
    return currentUserId !== null && Number(account?.id) === currentUserId;
}

function fieldClass(field) {
    return errors[field]
        ? 'border-rose-300 bg-rose-50 focus:border-rose-400'
        : 'border-[#d8def1] bg-white focus:border-[#A25F88]';
}

function assignErrors(validationErrors) {
    clearErrors();

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
    form.role = defaultRole(screen);
}

function normalizeScreen(value) {
    return value === 'merchants' ? 'merchants' : 'users';
}

function defaultRole(currentScreen) {
    return currentScreen === 'merchants' ? 'merchant' : 'admin';
}

function defaultRoleLabel(currentScreen) {
    return currentScreen === 'merchants' ? 'Merchant' : 'Admin user';
}

function defaultSubmitLabel(currentScreen) {
    return currentScreen === 'merchants' ? 'Create merchant' : 'Create admin user';
}

function initialForm(currentScreen) {
    return {
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: defaultRole(currentScreen),
    };
}

function currentSignedInUserId() {
    const parsed = Number.parseInt(String(window.__APP_CONTEXT__?.currentUserId ?? ''), 10);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function initialDashboard(currentScreen) {
    const isMerchantScreen = currentScreen === 'merchants';

    return {
        screen: currentScreen,
        meta: {
            brand: 'Spodut',
            page_title: isMerchantScreen ? 'Merchants' : 'Admin Users',
            kicker: isMerchantScreen ? 'Seller management' : 'Admin access',
            subheadline: isMerchantScreen
                ? 'Loading merchant accounts...'
                : 'Loading admin accounts...',
            links: {
                frontend: '/frontend',
                admin_users: '/admin/users',
                admin_merchants: '/admin/merchants',
                logout: '/auth/logout',
            },
        },
        menu: [],
        form: {
            role: defaultRole(currentScreen),
            role_label: defaultRoleLabel(currentScreen),
            submit_label: defaultSubmitLabel(currentScreen),
        },
        accounts: {
            count: 0,
            summary: [],
            items: [],
        },
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
