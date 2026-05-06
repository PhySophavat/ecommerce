<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Order history</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Track recent customer orders</h1>
        </div>

        <div class="space-y-4">
            <article v-for="order in store.orders" :key="order.id" class="rounded-[30px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">{{ formatDate(order.placed_at) }}</div>
                        <h2 class="mt-2 text-2xl font-black tracking-[-0.03em] text-[#111827]">{{ order.number }}</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="badge badge-order">{{ order.status }}</span>
                            <span class="badge badge-payment">{{ order.payment_status }}</span>
                            <span class="rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#475569]">{{ order.payment_method_label }}</span>
                            <span class="rounded-full bg-[#F8FAFC] px-3 py-1 text-xs font-semibold text-[#475569]">${{ Number(order.total_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                    <RouterLink :to="`/orders/${order.id}`" class="inline-flex items-center rounded-full border border-[#E5E7EB] bg-white px-4 py-2 text-sm font-semibold text-[#111827] transition hover:border-[#A25F88] hover:text-[#A25F88]">
                        View detail
                    </RouterLink>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] p-4">
                        <h3 class="text-sm font-semibold text-[#111827]">Merchants</h3>
                        <div class="mt-3 space-y-2 text-sm text-[#6B7280]">
                            <div v-for="merchant in order.merchant_groups" :key="`${order.id}-${merchant.merchant_id}`" class="flex items-center justify-between gap-4">
                                <span>{{ merchant.merchant_name }}</span>
                                <span class="font-semibold text-[#111827]">${{ Number(merchant.subtotal).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-[24px] border border-[#E5E7EB] bg-[#F8FAFC] p-4">
                        <h3 class="text-sm font-semibold text-[#111827]">Summary</h3>
                        <div class="mt-3 space-y-2 text-sm text-[#6B7280]">
                            <div class="flex items-center justify-between gap-4">
                                <span>Items</span>
                                <span class="font-semibold text-[#111827]">{{ order.items_count }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span>Payment method</span>
                                <span class="font-semibold text-[#111827]">{{ order.payment_method_label }}</span>
                            </div>
                            <div v-if="order.payment_reference" class="flex items-center justify-between gap-4">
                                <span>Reference</span>
                                <span class="font-semibold text-[#111827]">{{ order.payment_reference }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <div v-if="!store.orders.length && !store.orderLoading" class="rounded-[30px] border border-dashed border-[#D8B4C7] bg-white px-6 py-16 text-center text-[#6B7280]">
                No orders yet. Place an order from checkout to see history here.
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();

onMounted(async () => {
    await store.initialize();
    await store.fetchOrders();
});

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Recent';
}
</script>

<style scoped>
.badge {
    border-radius: 9999px;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-order {
    background: #f3e8f1;
    color: #a25f88;
}

.badge-payment {
    background: #e0f2fe;
    color: #0369a1;
}
</style>
