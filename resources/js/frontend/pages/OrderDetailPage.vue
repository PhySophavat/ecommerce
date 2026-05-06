<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Order detail</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">{{ order?.number || 'Loading order' }}</h1>
            </div>
            <RouterLink to="/orders" class="rounded-full border border-[#E5E7EB] bg-white px-4 py-2 text-sm font-semibold text-[#111827]">Back to orders</RouterLink>
        </div>

        <div v-if="order" class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <section class="space-y-6">
                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap gap-3">
                        <span class="badge badge-order">{{ order.status }}</span>
                        <span class="badge badge-payment">{{ order.payment_status }}</span>
                        <span class="badge bg-[#F8FAFC] text-[#475569]">{{ order.payment_method_label }}</span>
                    </div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm font-semibold text-[#111827]">Customer</p>
                            <p class="mt-1 text-sm text-[#6B7280]">{{ order.customer_name }}</p>
                            <p class="text-sm text-[#6B7280]">{{ order.email }}</p>
                            <p class="text-sm text-[#6B7280]">{{ order.phone }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[#111827]">Shipping address</p>
                            <p class="mt-1 text-sm text-[#6B7280]">{{ order.address_line1 }}</p>
                            <p v-if="order.address_line2" class="text-sm text-[#6B7280]">{{ order.address_line2 }}</p>
                            <p class="text-sm text-[#6B7280]">{{ order.city }}, {{ order.postal_code }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Items by merchant</h2>
                    <div class="mt-5 space-y-5">
                        <div v-for="group in order.merchant_groups" :key="group.merchant_id || group.merchant_name" class="rounded-[24px] border border-[#E5E7EB] p-4">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-bold text-[#111827]">{{ group.merchant_name }}</h3>
                                <span class="text-sm font-semibold text-[#6B7280]">${{ Number(group.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div v-for="item in group.items" :key="item.id" class="flex items-center justify-between gap-4 rounded-[20px] bg-[#F8FAFC] px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <img v-if="item.product_image" :src="item.product_image" :alt="item.product_name" class="h-14 w-14 rounded-2xl object-cover">
                                        <div v-else class="h-14 w-14 rounded-2xl bg-[#E5E7EB]"></div>
                                        <div>
                                            <p class="font-semibold text-[#111827]">{{ item.product_name }}</p>
                                            <p class="text-sm text-[#6B7280]">${{ Number(item.price).toFixed(2) }} × {{ item.quantity }}</p>
                                        </div>
                                    </div>
                                    <div class="font-semibold text-[#111827]">${{ Number(item.total).toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="space-y-5">
                <div class="rounded-[32px] border border-[#E5E7EB] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">Order summary</h2>
                    <div class="mt-5 space-y-3 text-sm text-[#6B7280]">
                        <div class="flex items-center justify-between">
                            <span>Payment method</span>
                            <span class="font-semibold text-[#111827]">{{ order.payment_method_label }}</span>
                        </div>
                        <div v-if="order.payment_reference" class="flex items-center justify-between">
                            <span>Payment reference</span>
                            <span class="font-semibold text-[#111827]">{{ order.payment_reference }}</span>
                        </div>
                        <div v-if="order.paid_at" class="flex items-center justify-between">
                            <span>Paid at</span>
                            <span class="font-semibold text-[#111827]">{{ formatDateTime(order.paid_at) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Subtotal</span>
                            <span class="font-semibold text-[#111827]">${{ Number(order.subtotal_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Shipping</span>
                            <span class="font-semibold text-[#111827]">${{ Number(order.shipping_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-[#E5E7EB] pt-3 text-base">
                            <span class="font-semibold text-[#111827]">Total</span>
                            <span class="font-black text-[#111827]">${{ Number(order.total_amount).toFixed(2) }}</span>
                        </div>
                    </div>
                    <div class="mt-5 rounded-[24px] bg-[#F8FAFC] p-4 text-sm leading-7 text-[#6B7280]">
                        {{ order.payment_instructions }}
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useStorefrontStore } from '../stores/storefront';

const props = defineProps({
    id: {
        type: [String, Number],
        required: true,
    },
});

const store = useStorefrontStore();
const order = ref(null);

onMounted(async () => {
    await store.initialize();
    order.value = await store.fetchOrder(props.id);
});

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Pending';
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
