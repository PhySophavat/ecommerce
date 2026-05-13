<template>
    <div class="storefront-shell min-h-screen bg-[#F8FAFC]">
        <Navbar />
        <main>
            <RouterView />
        </main>
        <Footer />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import Footer from '../components/Footer.vue';
import Navbar from '../components/Navbar.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();

onMounted(() => {
    store.initialize();
});
</script>
