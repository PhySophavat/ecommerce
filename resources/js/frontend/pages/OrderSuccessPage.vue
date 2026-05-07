<template>
    <div class="mx-auto w-full lg:w-[80%] max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-[36px] border border-[#E5E7EB] bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Order success</p>
            <h1 class="mt-2 text-4xl font-black tracking-[-0.05em] text-[#111827]">Your order has been placed.</h1>
            <p class="mt-4 text-sm leading-7 text-[#6B7280]">
                Order <span class="font-semibold text-[#111827]">{{ order?.number || `#${id}` }}</span> was created successfully. Merchants can now process their own items from their dashboards.
            </p>

            <div v-if="order" class="mt-8 grid gap-4 sm:grid-cols-4">
                <div class="rounded-[24px] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Order status</p>
                    <p class="mt-3 text-xl font-black text-[#111827]">{{ order.status }}</p>
                </div>
                <div class="rounded-[24px] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Payment status</p>
                    <p class="mt-3 text-xl font-black text-[#111827]">{{ order.payment_status }}</p>
                </div>
                <div class="rounded-[24px] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Payment method</p>
                    <p class="mt-3 text-xl font-black text-[#111827]">{{ order.payment_method_label }}</p>
                </div>
                <div class="rounded-[24px] bg-[#F8FAFC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Total</p>
                    <p class="mt-3 text-xl font-black text-[#111827]">${{ Number(order.total_amount || 0).toFixed(2) }}</p>
                </div>
            </div>

            <div v-if="order" class="mt-6 rounded-[24px] bg-[#F8FAFC] p-5 text-sm leading-7 text-[#6B7280]">
                <p><span class="font-semibold text-[#111827]">Payment flow:</span> {{ order.payment_instructions }}</p>
                <p v-if="order.payment_reference" class="mt-2"><span class="font-semibold text-[#111827]">Reference:</span> {{ order.payment_reference }}</p>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <RouterLink :to="`/orders/${id}`" class="inline-flex items-center rounded-full bg-[#A25F88] px-5 py-3 text-sm font-semibold text-white">View order detail</RouterLink>
                <RouterLink to="/orders" class="inline-flex items-center rounded-full border border-[#E5E7EB] bg-white px-5 py-3 text-sm font-semibold text-[#111827]">My orders</RouterLink>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const props = defineProps({
    id: {
        type: [String, Number],
        required: true,
    },
});

const store = useStorefrontStore();
const order = computed(() => {
    if (store.latestOrder?.id && String(store.latestOrder.id) === String(props.id)) {
        return store.latestOrder;
    }

    return store.orders.find((item) => String(item.id) === String(props.id)) || null;
});
</script>
