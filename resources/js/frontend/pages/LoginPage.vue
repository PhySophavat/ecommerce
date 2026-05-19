<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F8FAFC] px-4 py-8 sm:px-6 sm:py-10">
        <div class="w-full max-w-[580px]">
            <section class="rounded-[32px] border border-[#E5E7EB] bg-white px-6 py-7 shadow-[0_18px_40px_rgba(15,23,42,0.06)] sm:px-8 sm:py-8">
                <div class="mx-auto w-full max-w-[520px]">
                <p class="text-center text-[11px] font-semibold uppercase tracking-[0.24em] text-[#94A3B8]">LOGIN PAGE</p>
                <h2 class="mt-3 text-center text-[2rem] font-black tracking-[-0.05em] text-[#111827] sm:text-[2.35rem]">Welcome back</h2>
                <p class="mt-3 text-center text-sm leading-7 text-[#6B7280]">
                    Sign in to continue shopping and manage your orders.
                </p>
                <form class="mt-7 space-y-4" @submit.prevent="submit">
                    <input v-model.trim="form.email" type="email" placeholder="Email address" class="field-input">
                    <input v-model="form.password" type="password" placeholder="Password" class="field-input">
                    <div v-if="error" class="error-alert">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-8.75-3.25a.75.75 0 0 1 1.5 0v3.5a.75.75 0 0 1-1.5 0v-3.5ZM10 13a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ error }}</span>
                    </div>
                    <Button block size="lg" type="submit" :disabled="submitting">
                        {{ submitting ? 'Signing in...' : 'Login' }}
                    </Button>

                    <div class="relative py-1">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-[#E5E7EB]"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-4 text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">OR</span>
                        </div>
                    </div>

                    <a :href="googleAuthUrl" class="google-button">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M21.6 12.23c0-.68-.06-1.33-.17-1.96H12v3.7h5.39a4.6 4.6 0 0 1-2 3.02v2.5h3.24c1.9-1.75 2.97-4.34 2.97-7.26Z"/>
                            <path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.63-2.45l-3.24-2.5c-.9.6-2.04.96-3.39.96-2.6 0-4.8-1.76-5.59-4.12H3.06v2.58A10 10 0 0 0 12 22Z"/>
                            <path fill="#FBBC05" d="M6.41 13.89A6 6 0 0 1 6.1 12c0-.66.11-1.3.31-1.89V7.53H3.06A10 10 0 0 0 2 12c0 1.61.39 3.13 1.06 4.47l3.35-2.58Z"/>
                            <path fill="#EA4335" d="M12 5.98c1.47 0 2.8.5 3.85 1.5l2.88-2.88C16.96 2.96 14.7 2 12 2A10 10 0 0 0 3.06 7.53l3.35 2.58C7.2 7.74 9.4 5.98 12 5.98Z"/>
                        </svg>
                        <span>Continue with Google</span>
                    </a>
                </form>
                <p class="mt-5 text-center text-sm text-[#6B7280]">
                    New here?
                    <RouterLink to="/register" class="font-semibold text-[#1495E8]">Create an account</RouterLink>
                </p>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const route = useRoute();
const router = useRouter();
const submitting = ref(false);
const error = ref('');
const googleAuthUrl = `${window.location.origin}/auth/google/redirect`;
const form = reactive({
    email: '',
    password: '',
});

onMounted(() => {
    const googleError = typeof route.query.google_error === 'string' ? route.query.google_error : '';

    if (googleError) {
        error.value = googleError;
        const nextQuery = { ...route.query };
        delete nextQuery.google_error;
        router.replace({ path: route.path, query: nextQuery });
    }
});

async function submit() {
    error.value = '';
    submitting.value = true;

    try {
        await store.login(form);
        await router.push(route.query.redirect || '/orders');
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || 'Unable to sign in.';
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.field-input {
    width: 100%;
    min-height: 52px;
    border-radius: 1.1rem;
    border: 1px solid #E5E7EB;
    background: #F8FAFC;
    padding: 0.9rem 1rem;
    color: #111827;
    transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
    outline: none;
}

.field-input:focus {
    background: #ffffff;
    border-color: #A25F88;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.1);
}

.error-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    border-radius: 0.95rem;
    border: 1px solid #fecaca;
    background: #fef2f2;
    padding: 0.8rem 0.95rem;
    color: #dc2626;
    font-size: 0.875rem;
    line-height: 1.45;
}

.google-button {
    width: 100%;
    min-height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    border-radius: 9999px;
    border: 1px solid #E5E7EB;
    background: #ffffff;
    color: #111827;
    font-size: 0.95rem;
    font-weight: 600;
    padding: 0.9rem 1.25rem;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
    transition: background-color 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    text-decoration: none;
}

.google-button:hover {
    background: #F8FAFC;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    border-color: #d8e0ea;
}

.google-button:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.16);
}
</style>
