<template>
    <div class="flex min-h-screen items-center justify-center bg-[#F8FAFC] px-4 py-10">
        <div class="grid w-full max-w-6xl gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="rounded-[36px] border border-[#E5E7EB] bg-white p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Register page</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Create your customer account</h1>
                <form class="mt-8 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <input v-model.trim="form.name" type="text" placeholder="Full name" class="field-input">
                    <input v-model.trim="form.phone" type="text" placeholder="Phone number" class="field-input">
                    <input v-model.trim="form.email" type="email" placeholder="Email address" class="field-input sm:col-span-2">
                    <input v-model="form.password" type="password" placeholder="Password" class="field-input">
                    <input v-model="form.password_confirmation" type="password" placeholder="Confirm password" class="field-input">
                    <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:col-span-2">{{ error }}</div>
                    <Button block size="lg" class="sm:col-span-2" type="submit" :disabled="submitting">
                        {{ submitting ? 'Creating account...' : 'Register' }}
                    </Button>
                </form>
            </section>

            <section class="rounded-[36px] bg-[linear-gradient(145deg,#A25F88,#8B4E73)] p-8 text-white shadow-[0_28px_70px_rgba(162,95,136,0.24)]">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/80">Join the storefront</p>
                <h2 class="mt-3 text-4xl font-black tracking-[-0.05em]">Save favorites, track orders, and checkout faster.</h2>
                <p class="mt-4 max-w-md text-sm leading-7 text-white/80">
                    A customer account keeps your wishlist, order history, and delivery details ready across mobile and desktop.
                </p>
            </section>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const router = useRouter();
const submitting = ref(false);
const error = ref('');
const form = reactive({
    name: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
});

async function submit() {
    error.value = '';
    submitting.value = true;

    try {
        await store.register(form);
        await router.push('/checkout');
    } catch (requestError) {
        error.value = requestError?.response?.data?.message
            || Object.values(requestError?.response?.data?.errors ?? {}).flat()?.[0]
            || 'Unable to create the account.';
    } finally {
        submitting.value = false;
    }
}
</script>

<style scoped>
.field-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    padding: 0.95rem 1rem;
    outline: none;
}

.field-input:focus {
    border-color: #a25f88;
    box-shadow: 0 0 0 4px rgba(162, 95, 136, 0.1);
}
</style>
