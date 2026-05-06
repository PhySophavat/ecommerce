<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F8FBFE] px-4 py-10">
        <div class="grid w-full max-w-6xl gap-8 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-[36px] border border-[#D8E7F4] bg-[linear-gradient(145deg,#0F172A,#1495E8)] p-8 text-white shadow-[0_28px_70px_rgba(15,23,42,0.14)]">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[#BFDBFE]">Welcome back</p>
                <h1 class="mt-3 text-4xl font-black tracking-[-0.05em]">Sign in to continue shopping.</h1>
                <p class="mt-4 max-w-md text-sm leading-7 text-slate-300">
                    Access saved items, your order history, and a smooth checkout flow from one customer account.
                </p>
            </section>

            <section class="rounded-[36px] border border-[#D8E7F4] bg-white p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Login page</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Customer login</h2>
                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <input v-model.trim="form.email" type="email" placeholder="Email address" class="field-input">
                    <input v-model="form.password" type="password" placeholder="Password" class="field-input">
                    <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
                    <Button block size="lg" type="submit" :disabled="submitting">
                        {{ submitting ? 'Signing in...' : 'Login' }}
                    </Button>
                </form>
                <p class="mt-6 text-sm text-[#6B7280]">
                    Need an account?
                    <RouterLink to="/register" class="font-semibold text-[#1495E8]">Register here</RouterLink>
                </p>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const route = useRoute();
const router = useRouter();
const submitting = ref(false);
const error = ref('');
const form = reactive({
    email: '',
    password: '',
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
    border-radius: 1rem;
    border: 1px solid #d8e7f4;
    background: #f8fbfe;
    padding: 0.95rem 1rem;
    outline: none;
}

.field-input:focus {
    border-color: #1495e8;
    box-shadow: 0 0 0 4px rgba(20, 149, 232, 0.1);
}
</style>
