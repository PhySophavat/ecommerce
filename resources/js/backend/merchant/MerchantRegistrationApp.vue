<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <ProductSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                screen="merchants"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <ProductHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    screen="merchants"
                    @primary-action="goBackToMerchants"
                    @refresh="refreshCurrentStep"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <div class="mx-auto max-w-[920px]">
                        <section class="admin-card mb-6 rounded-[30px] px-6 py-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#A25F88]">Merchant Registration</p>
                            <div class="mt-5 flex flex-col gap-4 lg:flex-row lg:items-center">
                                <div
                                    v-for="item in steps"
                                    :key="item.id"
                                    class="flex flex-1 items-center gap-3"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border text-base font-bold"
                                        :class="stepCircleClass(item.id)"
                                    >
                                        {{ item.number }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-base font-bold text-slate-950">{{ item.label }}</p>
                                        <p class="text-sm text-slate-500">{{ item.caption }}</p>
                                    </div>
                                    <div
                                        v-if="item.id !== 'step3'"
                                        class="ml-auto hidden h-px flex-1 lg:block"
                                        :class="stepLineClass(item.id)"
                                    ></div>
                                </div>
                            </div>
                        </section>

                        <section class="admin-card rounded-[30px] px-7 py-7">
                            <div class="mb-7 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div class="max-w-2xl">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#A25F88]">{{ currentStepConfig.kicker }}</p>
                                    <h1 class="mt-2 text-3xl font-extrabold tracking-[-0.04em] text-slate-950">{{ currentStepConfig.title }}</h1>
                                    <p class="mt-3 text-sm leading-7 text-slate-500">{{ currentStepConfig.description }}</p>
                                </div>
                                <div class="rounded-2xl bg-[#F3E8F1] px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#A25F88]">
                                    {{ currentStepConfig.badge }}
                                </div>
                            </div>

                            <form
                                v-if="currentStep === 'step1'"
                                :action="routes.step1Store"
                                method="POST"
                                enctype="multipart/form-data"
                                class="space-y-5"
                            >
                                <input type="hidden" name="_token" :value="csrfToken">

                                <FieldBlock label="Merchant Name / Shop Name *" field="shop_name">
                                    <input
                                        name="shop_name"
                                        type="text"
                                        :value="form.step1.shop_name"
                                        class="field-input"
                                        :class="fieldClass('shop_name')"
                                        placeholder="Enter your shop name"
                                        required
                                    >
                                </FieldBlock>

                                <FieldBlock label="Business Type *" field="business_type">
                                    <select
                                        name="business_type"
                                        class="field-input"
                                        :class="fieldClass('business_type')"
                                        required
                                    >
                                        <option value="">Select business type</option>
                                        <option
                                            v-for="businessType in options.businessTypes"
                                            :key="businessType"
                                            :value="businessType"
                                            :selected="form.step1.business_type === businessType"
                                        >
                                            {{ businessType }}
                                        </option>
                                    </select>
                                </FieldBlock>

                                <FieldBlock label="Business Description" field="business_description">
                                    <textarea
                                        name="business_description"
                                        rows="4"
                                        class="field-input"
                                        :class="fieldClass('business_description')"
                                        placeholder="Describe your business"
                                    >{{ form.step1.business_description }}</textarea>
                                </FieldBlock>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <FieldBlock label="Shop Logo" field="shop_logo">
                                        <p v-if="form.step1.shop_logo_uploaded" class="mb-2 text-xs text-[#A25F88]">Uploaded file already saved.</p>
                                        <input
                                            name="shop_logo"
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/gif"
                                            class="file-input"
                                            :class="fieldClass('shop_logo')"
                                        >
                                    </FieldBlock>

                                    <FieldBlock label="Cover Image" field="cover_image">
                                        <p v-if="form.step1.cover_image_uploaded" class="mb-2 text-xs text-[#A25F88]">Uploaded file already saved.</p>
                                        <input
                                            name="cover_image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/gif"
                                            class="file-input"
                                            :class="fieldClass('cover_image')"
                                        >
                                    </FieldBlock>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="primary-button">Next Step</button>
                                </div>
                            </form>

                            <form
                                v-else-if="currentStep === 'step2'"
                                :action="routes.step2Store"
                                method="POST"
                                enctype="multipart/form-data"
                                class="space-y-5"
                            >
                                <input type="hidden" name="_token" :value="csrfToken">

                                <FieldBlock label="Owner Full Name *" field="owner_name">
                                    <input
                                        name="owner_name"
                                        type="text"
                                        :value="form.step2.owner_name"
                                        class="field-input"
                                        :class="fieldClass('owner_name')"
                                        placeholder="Enter owner full name"
                                        required
                                    >
                                </FieldBlock>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <FieldBlock label="Phone Number *" field="phone">
                                        <input
                                            name="phone"
                                            type="text"
                                            :value="form.step2.phone"
                                            class="field-input"
                                            :class="fieldClass('phone')"
                                            placeholder="+855 12 345 678"
                                            required
                                        >
                                    </FieldBlock>

                                    <FieldBlock label="Email *" field="email">
                                        <input
                                            name="email"
                                            type="email"
                                            :value="form.step2.email"
                                            class="field-input"
                                            :class="fieldClass('email')"
                                            placeholder="merchant@example.com"
                                            required
                                        >
                                    </FieldBlock>
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <FieldBlock label="Password *" field="password">
                                        <input
                                            name="password"
                                            type="password"
                                            class="field-input"
                                            :class="fieldClass('password')"
                                            placeholder="Minimum 8 characters"
                                            required
                                        >
                                    </FieldBlock>

                                    <FieldBlock label="Confirm Password *" field="password_confirmation">
                                        <input
                                            name="password_confirmation"
                                            type="password"
                                            class="field-input"
                                            :class="fieldClass('password_confirmation')"
                                            placeholder="Repeat password"
                                            required
                                        >
                                    </FieldBlock>
                                </div>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <FieldBlock label="Profile Image" field="profile_image">
                                        <p v-if="form.step2.profile_image_uploaded" class="mb-2 text-xs text-[#A25F88]">Uploaded file already saved.</p>
                                        <input
                                            name="profile_image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/jpg,image/gif"
                                            class="file-input"
                                            :class="fieldClass('profile_image')"
                                        >
                                    </FieldBlock>

                                    <FieldBlock label="ID Card / Document Upload" field="id_card">
                                        <p v-if="form.step2.id_card_uploaded" class="mb-2 text-xs text-[#A25F88]">Uploaded file already saved.</p>
                                        <input
                                            name="id_card"
                                            type="file"
                                            accept="application/pdf,image/jpeg,image/png,image/jpg"
                                            class="file-input"
                                            :class="fieldClass('id_card')"
                                        >
                                    </FieldBlock>
                                </div>

                                <div class="flex justify-between">
                                    <a :href="routes.step1" class="secondary-button">Back</a>
                                    <button type="submit" class="primary-button">Next Step</button>
                                </div>
                            </form>

                            <form
                                v-else
                                :action="routes.step3Store"
                                method="POST"
                                class="space-y-5"
                            >
                                <input type="hidden" name="_token" :value="csrfToken">

                                <FieldBlock label="Full Address *" field="full_address">
                                    <input
                                        name="full_address"
                                        type="text"
                                        :value="form.step3.full_address"
                                        class="field-input"
                                        :class="fieldClass('full_address')"
                                        placeholder="Street address, building number"
                                        required
                                    >
                                </FieldBlock>

                                <FieldBlock label="Province / City *" field="province_city">
                                    <select
                                        name="province_city"
                                        class="field-input"
                                        :class="fieldClass('province_city')"
                                        required
                                    >
                                        <option value="">Select Province / City</option>
                                        <option
                                            v-for="province in options.provinces"
                                            :key="province"
                                            :value="province"
                                            :selected="form.step3.province_city === province"
                                        >
                                            {{ province }}
                                        </option>
                                    </select>
                                </FieldBlock>

                                <div class="grid gap-5 md:grid-cols-2">
                                    <FieldBlock label="District" field="district">
                                        <input
                                            name="district"
                                            type="text"
                                            :value="form.step3.district"
                                            class="field-input"
                                            :class="fieldClass('district')"
                                            placeholder="District"
                                        >
                                    </FieldBlock>

                                    <FieldBlock label="Commune" field="commune">
                                        <input
                                            name="commune"
                                            type="text"
                                            :value="form.step3.commune"
                                            class="field-input"
                                            :class="fieldClass('commune')"
                                            placeholder="Commune"
                                        >
                                    </FieldBlock>
                                </div>

                                <FieldBlock label="Google Map Link" field="google_map_link">
                                    <input
                                        name="google_map_link"
                                        type="url"
                                        :value="form.step3.google_map_link"
                                        class="field-input"
                                        :class="fieldClass('google_map_link')"
                                        placeholder="https://maps.google.com/..."
                                    >
                                </FieldBlock>

                                <FieldBlock label="Delivery Area" field="delivery_area">
                                    <textarea
                                        name="delivery_area"
                                        rows="4"
                                        class="field-input"
                                        :class="fieldClass('delivery_area')"
                                        placeholder="Delivery area details"
                                    >{{ form.step3.delivery_area }}</textarea>
                                </FieldBlock>

                                <div class="rounded-[24px] border border-[#ead9e3] bg-[#fcf7fa] px-4 py-4 text-sm leading-6 text-slate-600">
                                    After submit, merchant status will be <span class="font-semibold text-[#A25F88]">Pending</span>. Admin approval is required before selling.
                                </div>

                                <div class="flex justify-between">
                                    <a :href="routes.step2" class="secondary-button">Back</a>
                                    <button type="submit" class="primary-button">Submit Registration</button>
                                </div>
                            </form>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue';
import ProductHeader from '../products/sections/ProductHeader.vue';
import ProductSidebar from '../products/sections/ProductSidebar.vue';

const context = window.__APP_CONTEXT__ ?? {};
const currentStep = context.step ?? 'step1';
const csrfToken = context.csrfToken ?? '';
const routes = context.routes ?? {};
const options = context.options ?? {
    businessTypes: [],
    provinces: [],
};
const form = context.form ?? {
    step1: {},
    step2: {},
    step3: {},
};
const errors = context.errors ?? {};
const dashboardContext = context.dashboard ?? null;
const openMenus = ref({
    'users-admin-management': true,
});

const dashboard = computed(() => dashboardContext ?? {
    meta: {
        brand: 'E-commerce',
        page_title: 'Create Merchant',
        kicker: 'Seller management',
        subheadline: 'Create merchant accounts in three small steps with the same admin navigation.',
        links: {
            admin_users: '/admin/users',
            admin_merchants: '/admin/merchants',
            logout: '/auth/logout',
        },
    },
    menu: [],
});

const steps = [
    { id: 'step1', number: 1, label: 'Business Info', caption: 'Shop and merchant profile' },
    { id: 'step2', number: 2, label: 'Owner Info', caption: 'Login and verification' },
    { id: 'step3', number: 3, label: 'Location', caption: 'Address and delivery area' },
];

const currentStepConfig = computed(() => ({
    step1: {
        kicker: 'Merchant Profile',
        title: 'Merchant Profile / Business Info',
        description: 'Tell the admin about the shop before moving to owner verification.',
        badge: 'Step 1 of 3',
    },
    step2: {
        kicker: 'Owner Information',
        title: 'Owner Information',
        description: 'Use this section for login, contact, account recovery, and merchant verification.',
        badge: 'Step 2 of 3',
    },
    step3: {
        kicker: 'Location Information',
        title: 'Location Information',
        description: 'Provide address and delivery details that build trust for customers and admins.',
        badge: 'Step 3 of 3',
    },
}[currentStep]));

const FieldBlock = defineComponent({
    name: 'FieldBlock',
    props: {
        label: { type: String, required: true },
        field: { type: String, required: true },
    },
    setup(props, { slots }) {
        return () => h('label', { class: 'block space-y-2' }, [
            h('span', { class: 'text-sm font-semibold text-slate-800' }, props.label),
            ...(slots.default ? slots.default() : []),
            errors[props.field]
                ? h('p', { class: 'text-xs text-rose-600' }, String(errors[props.field][0]))
                : null,
        ]);
    },
});

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
        window.location.href = '/login';
        return;
    }

    if (!item.path || !item.is_enabled) {
        return;
    }

    window.location.href = item.path;
}

function goBackToMerchants() {
    window.location.href = '/admin/merchants';
}

function refreshCurrentStep() {
    window.location.reload();
}

function fieldClass(field) {
    return errors[field]
        ? 'border-rose-300 bg-rose-50 text-slate-900 placeholder:text-rose-300'
        : 'border-[#d8def1] bg-white text-slate-900 placeholder:text-slate-400';
}

function stepCircleClass(stepId) {
    return stepOrder(stepId) <= stepOrder(currentStep)
        ? 'border-[#A25F88] bg-[#A25F88] text-white'
        : 'border-[#d8def1] bg-white text-slate-400';
}

function stepLineClass(stepId) {
    return stepOrder(stepId) < stepOrder(currentStep)
        ? 'bg-[#A25F88]'
        : 'bg-[#e3e9f7]';
}

function stepOrder(stepId) {
    return ({ step1: 1, step2: 2, step3: 3 })[stepId] ?? 1;
}
</script>

<style scoped>
.field-input {
    width: 100%;
    border-radius: 1.25rem;
    border-width: 1px;
    padding: 0.9rem 1rem;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 150ms ease, box-shadow 150ms ease;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 3px rgba(162, 95, 136, 0.12);
}

.file-input {
    width: 100%;
    border-radius: 1.25rem;
    border: 1px dashed #d8def1;
    background: white;
    padding: 0.9rem 1rem;
    color: #334155;
}

.primary-button {
    border-radius: 1rem;
    background: #a25f88;
    padding: 0.85rem 1.4rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
    transition: transform 150ms ease, background-color 150ms ease;
}

.primary-button:hover {
    transform: translateY(-1px);
    background: #8d4d75;
}

.secondary-button {
    border-radius: 1rem;
    border: 1px solid #d8def1;
    background: white;
    padding: 0.85rem 1.4rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #64748b;
    transition: transform 150ms ease, color 150ms ease;
}

.secondary-button:hover {
    transform: translateY(-1px);
    color: #0f172a;
}
</style>
