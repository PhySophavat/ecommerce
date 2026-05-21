<template>
    <div class="mx-auto w-full max-w-[980px] px-4 py-10 sm:px-6 lg:px-8">
        <div class="rounded-[30px] border border-[#E5E7EB] bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:p-8">
            <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center rounded-full bg-[rgba(162,95,136,0.08)] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#A25F88]">
                        Order success
                    </div>
                    <h1 class="mt-4 text-[2.15rem] font-black tracking-[-0.05em] text-[#111827] sm:text-[2.5rem]">
                        Your order has been placed.
                    </h1>
                    <p class="mt-4 text-[15px] leading-8 text-[#6B7280]">
                        Order <span class="font-semibold text-[#111827]">{{ order?.number || `#${id}` }}</span> was created successfully.
                        Merchants can now process their own items from their dashboards.
                    </p>
                </div>

                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 shadow-[0_10px_24px_rgba(16,185,129,0.12)]">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13 9 17 19 7" />
                    </svg>
                </div>
            </div>

            <div v-if="order" class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[22px] border border-[#EEF2F7] bg-[#F8FAFC] px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Order status</p>
                    <p class="mt-3 text-[1.05rem] font-black capitalize text-[#111827]">{{ order.status }}</p>
                </article>

                <article class="rounded-[22px] border border-[#EEF2F7] bg-[#F8FAFC] px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Payment status</p>
                    <div class="mt-3 inline-flex items-center rounded-full bg-[rgba(16,185,129,0.12)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-emerald-600">
                        {{ order.payment_status }}
                    </div>
                </article>

                <article class="rounded-[22px] border border-[#EEF2F7] bg-[#F8FAFC] px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Payment method</p>
                    <p class="mt-3 text-[1.05rem] font-black text-[#111827]">{{ order.payment_method_label }}</p>
                </article>

                <article class="rounded-[22px] border border-[#EEF2F7] bg-[#F8FAFC] px-5 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Total</p>
                    <p class="mt-3 text-[1.2rem] font-black text-[#111827]">${{ Number(order.total_amount || 0).toFixed(2) }}</p>
                </article>
            </div>

            <div v-if="order" class="mt-8 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                <section class="rounded-[24px] border border-[#EEF2F7] bg-[#FCFAFB] p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Payment summary</p>
                    <div class="mt-4 space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <span class="text-sm font-medium text-[#6B7280]">Payment flow</span>
                            <span class="max-w-[70%] text-right text-sm leading-6 text-[#111827]">{{ order.payment_instructions }}</span>
                        </div>
                        <div v-if="order.payment_reference" class="flex items-center justify-between gap-4">
                            <span class="text-sm font-medium text-[#6B7280]">Reference</span>
                            <span class="text-sm font-semibold text-[#111827]">{{ order.payment_reference }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-medium text-[#6B7280]">Order ID</span>
                            <span class="text-sm font-semibold text-[#111827]">{{ order.number }}</span>
                        </div>
                    </div>
                </section>

                <section class="rounded-[24px] border border-[#EEF2F7] bg-white p-5">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">Next step</p>
                    <h2 class="mt-3 text-[1.15rem] font-black tracking-[-0.03em] text-[#111827]">Processing has started</h2>
                    <p class="mt-3 text-sm leading-7 text-[#6B7280]">
                        You can review the order details, track payment confirmation, and check merchant progress from your order history.
                    </p>
                </section>
            </div>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <RouterLink
                    :to="`/orders/${id}`"
                    class="inline-flex min-h-[48px] items-center justify-center rounded-full bg-[#A25F88] px-5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]"
                >
                    View order detail
                </RouterLink>
                <RouterLink
                    to="/orders"
                    class="inline-flex min-h-[48px] items-center justify-center rounded-full border border-[#E5E7EB] bg-white px-5 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                >
                    My orders
                </RouterLink>
                <RouterLink
                    to="/shop"
                    class="inline-flex min-h-[48px] items-center justify-center rounded-full border border-[#E5E7EB] bg-white px-5 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]"
                >
                    Back to shop
                </RouterLink>
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
