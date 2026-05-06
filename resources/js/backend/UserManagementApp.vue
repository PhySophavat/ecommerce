<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @quick-action="scrollToForm"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader
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

                        <div class="mt-6 grid gap-6" :class="isCustomerScreen ? 'xl:grid-cols-1' : 'xl:grid-cols-[380px,minmax(0,1fr)]'">
                            <section v-if="!isCustomerScreen" ref="formSection" class="admin-card rounded-[30px] px-6 py-6">
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
                                            :placeholder="screen === 'merchants' ? 'Northstar Seller' : (isCustomerScreen ? 'Customer Name' : 'Admin User')"
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
                                            :placeholder="screen === 'merchants' ? 'seller@example.com' : (isCustomerScreen ? 'customer@example.com' : 'admin@example.com')"
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
                                            account{{ isCustomerScreen ? ' for storefront ordering and account management.' : ' and gives backend access for that role.' }}
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

                            <section v-else class="admin-card rounded-[30px] px-6 py-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Frontend customer source</p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                            Customer records are read-only here
                                        </h3>
                                    </div>

                                    <div class="rounded-2xl bg-[#F3E8F1] px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#A25F88]">
                                        {{ accountCount }} total
                                    </div>
                                </div>

                                <div class="mt-6 rounded-[24px] border border-[#ead9e3] bg-[#fcf7fa] px-5 py-5 text-sm leading-7 text-slate-600">
                                    This customer section only receives information from users who register on the frontend storefront.
                                    Admin users cannot create customer accounts from this page.
                                </div>
                            </section>

                            <section class="admin-card rounded-[30px] px-6 py-6">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                                            {{ screen === 'merchants' ? 'Seller directory' : (isCustomerScreen ? 'Customer directory' : 'Backend access directory') }}
                                        </p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">
                                            {{ accountCount }} {{ screen === 'merchants' ? 'merchant accounts' : (isCustomerScreen ? 'customer accounts' : 'admin accounts') }}
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
                                    No {{ screen === 'merchants' ? 'merchant' : (isCustomerScreen ? 'customer' : 'admin') }} accounts found yet.
                                </div>

                                <div v-else class="mt-6 overflow-x-auto rounded-[28px] border border-[#e3e9f7] bg-[#fbfcff]">
                                    <table class="w-full min-w-[860px] text-sm">
                                        <thead class="text-left text-[11px] uppercase tracking-[0.18em] text-slate-400">
                                            <tr class="border-b border-[#e6ebf8]">
                                                <th class="px-5 py-4">Account</th>
                                                <th class="px-4 py-4">Role</th>
                                                <th class="px-4 py-4">{{ screen === 'merchants' ? 'Products' : (isCustomerScreen ? 'Orders' : 'Approved') }}</th>
                                                <th class="px-4 py-4">{{ screen === 'merchants' ? 'Pending' : 'Email' }}</th>
                                                <th class="px-4 py-4">{{ screen === 'merchants' ? 'Approved' : 'Joined' }}</th>
                                                <th class="px-5 py-4 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="account in accountRows"
                                                :key="`account-${account.id}`"
                                                class="border-b border-[#eef2fb] last:border-b-0"
                                            >
                                                <td class="px-5 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#F3E8F1] text-sm font-extrabold text-[#A25F88]">
                                                            {{ account.initials }}
                                                        </div>
                                                        <div class="min-w-0">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <p class="truncate font-bold text-slate-950">{{ account.name }}</p>
                                                                <span
                                                                    v-if="isCurrentUser(account)"
                                                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700"
                                                                >
                                                                    You
                                                                </span>
                                                            </div>
                                                            <p class="truncate text-sm text-slate-500">{{ account.email }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span
                                                        class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]"
                                                        :class="roleBadgeClass(account.role)"
                                                    >
                                                        {{ account.role }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4 font-semibold text-slate-900">
                                                    {{ screen === 'merchants' ? account.products_count : (isCustomerScreen ? account.orders_count : account.approved_products_count) }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600">
                                                    {{ screen === 'merchants' ? account.pending_products_count : '@' + String(account.email ?? '').split('@')[0] }}
                                                </td>
                                                <td class="px-4 py-4 text-slate-600">
                                                    {{ screen === 'merchants' ? account.approved_products_count : account.joined_at }}
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <button
                                                            v-if="isCustomerScreen"
                                                            type="button"
                                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                                                            title="View customer detail"
                                                            @click="openCustomerDetail(account)"
                                                        >
                                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z"/>
                                                                <circle cx="12" cy="12" r="3"/>
                                                            </svg>
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="rounded-xl border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                            :disabled="deletingUserId === account.id || isCurrentUser(account)"
                                                            @click="deleteAccount(account)"
                                                        >
                                                            {{ deletingUserId === account.id ? 'Deleting...' : 'Delete' }}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>

                        <div
                            v-if="selectedCustomer"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-8"
                            @click.self="closeCustomerDetail"
                        >
                            <div class="w-full max-w-2xl rounded-[30px] bg-white shadow-2xl">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">Customer detail</p>
                                        <h3 class="mt-2 text-2xl font-extrabold tracking-[-0.04em] text-slate-950">{{ selectedCustomer.name }}</h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                                        @click="closeCustomerDetail"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                                    <article class="rounded-[24px] border border-slate-200 bg-slate-50 px-5 py-5">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Account</p>
                                        <dl class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Name</dt>
                                                <dd class="text-right font-semibold text-slate-950">{{ selectedCustomer.name }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Email</dt>
                                                <dd class="text-right font-semibold text-slate-950">{{ selectedCustomer.email }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Role</dt>
                                                <dd class="text-right font-semibold uppercase text-slate-950">{{ selectedCustomer.role }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Joined</dt>
                                                <dd class="text-right font-semibold text-slate-950">{{ selectedCustomer.joined_at }}</dd>
                                            </div>
                                        </dl>
                                    </article>

                                    <article class="rounded-[24px] border border-slate-200 bg-slate-50 px-5 py-5">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Order activity</p>
                                        <dl class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Orders placed</dt>
                                                <dd class="text-right font-semibold text-slate-950">{{ selectedCustomer.orders_count ?? 0 }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Username</dt>
                                                <dd class="text-right font-semibold text-slate-950">@{{ String(selectedCustomer.email ?? '').split('@')[0] }}</dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-slate-500">Source</dt>
                                                <dd class="text-right font-semibold text-slate-950">Frontend registration</dd>
                                            </div>
                                        </dl>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

import AdminHeader from './layout/AdminHeader.vue';
import AdminSidebar from './layout/AdminSidebar.vue';

const screen = normalizeScreen(window.__APP_CONTEXT__?.screen);
const endpoint = window.__APP_CONTEXT__?.endpoint ?? defaultEndpoint(screen);
const currentUserId = currentSignedInUserId();
const formSection = ref(null);
const dashboard = ref(initialDashboard(screen));
const deletingUserId = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const notice = ref(null);
const openMenus = ref({});
const selectedCustomer = ref(null);
const form = reactive(initialForm(screen));
const errors = reactive({});

const accountCount = computed(() => dashboard.value.accounts?.count ?? 0);
const accountRows = computed(() => dashboard.value.accounts?.items ?? []);
const roleLabel = computed(() => dashboard.value.form?.role_label ?? defaultRoleLabel(screen));
const submitLabel = computed(() => dashboard.value.form?.submit_label ?? defaultSubmitLabel(screen));
const summaryCards = computed(() => dashboard.value.accounts?.summary ?? []);
const isCustomerScreen = computed(() => ['customers', 'customer-details', 'purchase-history'].includes(screen));
const switchLinks = computed(() => [
    {
        screen: 'users',
        label: 'Admin users',
        href: dashboard.value.meta?.links?.admin_users ?? '/admin/users',
    },
    {
        screen: 'customers',
        label: 'Customers',
        href: dashboard.value.meta?.links?.admin_customers ?? '/admin/customers',
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
    if (isCustomerScreen.value) {
        return;
    }

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

function openCustomerDetail(account) {
    selectedCustomer.value = account;
}

function closeCustomerDetail() {
    selectedCustomer.value = null;
}

function scrollToForm() {
    if (isCustomerScreen.value) {
        return;
    }

    formSection.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

function isCurrentUser(account) {
    return currentUserId !== null && Number(account?.id) === currentUserId;
}

function roleBadgeClass(role) {
    return role === 'merchant'
        ? 'bg-amber-100 text-amber-700'
        : 'bg-[#F3E8F1] text-[#A25F88]';
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
    if (value === 'merchants' || value === 'customers' || value === 'customer-details' || value === 'purchase-history') {
        return value;
    }

    return 'users';
}

function defaultRole(currentScreen) {
    if (currentScreen === 'merchants') {
        return 'merchant';
    }

    if (currentScreen === 'customers' || currentScreen === 'customer-details' || currentScreen === 'purchase-history') {
        return 'customer';
    }

    return 'admin';
}

function defaultRoleLabel(currentScreen) {
    if (currentScreen === 'merchants') {
        return 'Merchant';
    }

    if (currentScreen === 'customers' || currentScreen === 'customer-details' || currentScreen === 'purchase-history') {
        return 'Customer';
    }

    return 'Admin user';
}

function defaultSubmitLabel(currentScreen) {
    if (currentScreen === 'merchants') {
        return 'Create merchant';
    }

    if (currentScreen === 'customers' || currentScreen === 'customer-details' || currentScreen === 'purchase-history') {
        return 'Create customer';
    }

    return 'Create admin user';
}

function defaultEndpoint(currentScreen) {
    if (currentScreen === 'merchants') {
        return '/admin/merchants';
    }

    if (currentScreen === 'purchase-history') {
        return '/admin/customers/purchase-history';
    }

    if (currentScreen === 'customer-details') {
        return '/admin/customers/details';
    }

    if (currentScreen === 'customers') {
        return '/admin/customers';
    }

    return '/admin/users';
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
    const isCustomerDirectory = ['customers', 'customer-details', 'purchase-history'].includes(currentScreen);

    return {
        screen: currentScreen,
        meta: {
            brand: 'Spodut',
            page_title: isMerchantScreen ? 'Merchants' : (isCustomerDirectory ? 'Customers' : 'Admin Users'),
            kicker: isMerchantScreen ? 'Seller management' : (isCustomerDirectory ? 'Customer accounts' : 'Admin access'),
            subheadline: isMerchantScreen
                ? 'Loading merchant accounts...'
                : (isCustomerDirectory ? 'Loading customer accounts...' : 'Loading admin accounts...'),
            links: {
                frontend: '/frontend',
                admin_users: '/admin/users',
                admin_merchants: '/admin/merchants',
                admin_customers: '/admin/customers',
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
