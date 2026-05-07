<template>
    <div class="mx-auto w-full lg:w-[80%] px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Customer profile</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Manage account details</h1>
        </div>

        <div class="grid gap-8 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                <div class="flex h-20 w-20 items-center justify-center rounded-[28px] bg-[#F3E8F1] text-2xl font-black text-[#A25F88]">
                    {{ initials }}
                </div>
                <h2 class="mt-4 text-2xl font-black tracking-[-0.03em] text-[#111827]">{{ form.name }}</h2>
                <p class="mt-2 text-sm text-[#6B7280]">{{ form.email }}</p>
                <p class="mt-1 text-sm text-[#6B7280]">{{ form.phone || 'No phone saved yet' }}</p>
            </aside>

            <section class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Profile details</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <input v-model.trim="form.name" type="text" class="field-input">
                    <input v-model.trim="form.phone" type="text" class="field-input">
                    <input v-model.trim="form.email" type="email" class="field-input sm:col-span-2">
                </div>
                <div v-if="notice" class="mt-6 rounded-2xl px-4 py-3 text-sm" :class="notice.type === 'error' ? 'border border-rose-200 bg-rose-50 text-rose-700' : 'border border-emerald-200 bg-emerald-50 text-emerald-700'">
                    {{ notice.text }}
                </div>
                <div class="mt-6 flex gap-3">
                    <Button :disabled="saving" @click="saveProfile">{{ saving ? 'Saving...' : 'Save profile' }}</Button>
                    <Button variant="secondary" :disabled="saving" @click="logout">Logout</Button>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();
const router = useRouter();
const saving = ref(false);
const notice = ref(null);
const form = reactive({
    name: '',
    email: '',
    phone: '',
});

onMounted(async () => {
    await store.initialize();
    syncForm();
});

const initials = computed(() => form.name.split(' ').filter(Boolean).map((part) => part[0]).join('').slice(0, 2).toUpperCase() || 'CU');

function syncForm() {
    form.name = store.profile.name || '';
    form.email = store.profile.email || '';
    form.phone = store.profile.phone || '';
}

async function saveProfile() {
    saving.value = true;
    notice.value = null;

    try {
        await store.updateProfile(form);
        syncForm();
        notice.value = { type: 'success', text: 'Profile updated successfully.' };
    } catch (requestError) {
        notice.value = {
            type: 'error',
            text: requestError?.response?.data?.message
                || Object.values(requestError?.response?.data?.errors ?? {}).flat()?.[0]
                || 'Unable to update the profile.',
        };
    } finally {
        saving.value = false;
    }
}

async function logout() {
    await store.logout();
    await router.push('/');
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
</style>
