<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#94A3B8]">Cart page</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">Review the products in your cart</h1>
        </div>

        <div class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <section class="space-y-4">
                <article v-for="item in store.cartItems" :key="item.line_id" class="rounded-[30px] border border-[#E5E7EB] bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-5 sm:flex-row">
                        <div class="h-28 w-full overflow-hidden rounded-[24px] bg-[linear-gradient(135deg,#fdf2f8,#eff6ff)] sm:w-28">
                            <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-xl font-black tracking-[-0.03em] text-[#111827]">{{ item.name }}</h2>
                                    <p class="mt-1 text-sm text-[#6B7280]">{{ item.variant }}</p>
                                    <p class="mt-1 text-sm text-[#6B7280]">{{ item.merchant_name }}</p>
                                </div>
                                <button type="button" class="text-sm font-semibold text-rose-500" @click="store.removeFromCart(item.line_id)">Remove</button>
                            </div>

                            <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="inline-flex items-center rounded-full border border-[#E5E7EB] bg-[#F8FAFC]">
                                    <button type="button" class="px-4 py-3 text-[#6B7280]" @click="store.updateCartItem(item.line_id, item.quantity - 1)">-</button>
                                    <span class="min-w-[3rem] text-center font-semibold text-[#111827]">{{ item.quantity }}</span>
                                    <button type="button" class="px-4 py-3 text-[#6B7280]" @click="store.updateCartItem(item.line_id, item.quantity + 1)">+</button>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-[#6B7280]">{{ item.price }} each</div>
                                    <div class="text-2xl font-black tracking-[-0.03em] text-[#111827]">${{ item.line_total.toFixed(2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <div v-if="!store.cartItems.length" class="rounded-[30px] border border-dashed border-[#D8B4C7] bg-white px-6 py-16 text-center text-[#6B7280]">
                    Your cart is empty.
                </div>
            </section>

            <OrderSummary title="Order summary" :lines="store.orderSummaryLines()">
                <Button to="/checkout" block size="lg" :disabled="!store.cartItems.length">Proceed to checkout</Button>
            </OrderSummary>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import Button from '../components/Button.vue';
import OrderSummary from '../components/OrderSummary.vue';
import { useStorefrontStore } from '../stores/storefront';

const store = useStorefrontStore();

onMounted(() => {
    store.initialize();
});
</script>
