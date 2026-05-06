<template>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div v-if="product" class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="space-y-4">
                <div class="overflow-hidden rounded-[34px] border border-[#D8E7F4] bg-white shadow-sm">
                    <div class="aspect-square bg-[linear-gradient(135deg,#eff7fd,#f8fbfe)]">
                        <img v-if="selectedImage" :src="selectedImage" :alt="product.name" class="h-full w-full object-cover">
                        <div v-else class="flex h-full items-center justify-center text-sm font-semibold uppercase tracking-[0.2em] text-[#64748B]">
                            {{ product.category }}
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <button v-for="thumb in gallery" :key="thumb" type="button" class="overflow-hidden rounded-[22px] border transition" :class="selectedImage === thumb ? 'border-[#1495E8]' : 'border-[#D8E7F4]'" @click="selectedImage = thumb">
                        <img :src="thumb" :alt="product.name" class="aspect-square h-full w-full object-cover">
                    </button>
                </div>
            </section>

            <section class="rounded-[34px] border border-[#D8E7F4] bg-white p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#94A3B8]">{{ product.category }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-[-0.05em] text-[#111827]">{{ product.name }}</h1>

                <div class="mt-4 flex items-center gap-3 text-sm text-[#6B7280]">
                    <span class="rounded-full bg-[#F3F9FD] px-3 py-1 font-semibold text-[#1495E8]">{{ Number(product.rating_value || 4.8).toFixed(1) }} rating</span>
                    <span>{{ product.reviews_count }} reviews</span>
                    <span>{{ product.merchant_name }}</span>
                </div>

                <div class="mt-6 flex items-end gap-4">
                    <div class="text-4xl font-black tracking-[-0.05em] text-[#1495E8]">{{ product.price }}</div>
                    <div v-if="product.compare_at_price" class="pb-1 text-lg text-[#94A3B8] line-through">{{ product.compare_at_price }}</div>
                </div>

                <p class="mt-6 text-sm leading-7 text-[#6B7280]">
                    {{ product.description || product.tagline }}
                </p>

                <div class="mt-8 space-y-6">
                    <div>
                        <h3 class="text-sm font-semibold text-[#111827]">Variant</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button v-for="option in product.variant_options" :key="option" type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="selectedVariant === option ? 'border-[#1495E8] bg-[#F3F9FD] text-[#1495E8]' : 'border-[#D8E7F4] text-[#6B7280]'" @click="selectedVariant = option">
                                {{ option }}
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold text-[#111827]">Color</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button v-for="color in product.color_options" :key="color" type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="selectedColor === color ? 'border-[#1495E8] bg-[#F3F9FD] text-[#1495E8]' : 'border-[#D8E7F4] text-[#6B7280]'" @click="selectedColor = color">
                                    {{ color }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-[#111827]">Size</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button v-for="size in product.size_options" :key="size" type="button" class="rounded-full border px-4 py-2 text-sm font-semibold transition" :class="selectedSize === size ? 'border-[#1495E8] bg-[#F3F9FD] text-[#1495E8]' : 'border-[#D8E7F4] text-[#6B7280]'" @click="selectedSize = size">
                                    {{ size }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="inline-flex items-center rounded-full border border-[#D8E7F4] bg-[#F3F9FD]">
                            <button type="button" class="px-4 py-3 text-lg text-[#1495E8]" @click="quantity = Math.max(1, quantity - 1)">-</button>
                            <span class="min-w-[3rem] text-center font-semibold text-[#111827]">{{ quantity }}</span>
                            <button type="button" class="px-4 py-3 text-lg text-[#1495E8]" @click="quantity = Math.min(product.inventory || quantity + 1, quantity + 1)">+</button>
                        </div>
                        <Button block size="lg" :disabled="!product.is_orderable" @click="addToCart">
                            {{ product.is_orderable ? 'Add to Cart' : 'Unavailable' }}
                        </Button>
                        <Button block variant="secondary" size="lg" :disabled="!product.is_orderable" @click="buyNow">Buy Now</Button>
                    </div>

                    <p v-if="!product.is_orderable" class="text-sm font-medium text-amber-600">
                        This product cannot be ordered until it is approved and in stock.
                    </p>

                    <div class="rounded-[26px] border border-[#D8E7F4] bg-[#F8FBFE] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#94A3B8]">Merchant information</p>
                        <h3 class="mt-2 text-lg font-black tracking-[-0.03em] text-[#111827]">{{ product.merchant_name }}</h3>
                        <p class="mt-2 text-sm text-[#6B7280]">Managed by {{ product.merchant_owner }} with storefront-ready payout and wallet tracking.</p>
                    </div>
                </div>
            </section>
        </div>

        <section v-if="related.length" class="mt-14">
            <div class="mb-6">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#94A3B8]">Related products</p>
                <h2 class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#111827]">More products you may like</h2>
            </div>
            <ProductGrid :products="related" :wishlist-ids="store.wishlist" @add-to-cart="store.addToCart($event)" @toggle-wishlist="store.toggleWishlist($event)" />
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import Button from '../components/Button.vue';
import ProductGrid from '../components/ProductGrid.vue';
import { useStorefrontStore } from '../stores/storefront';

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
});

const store = useStorefrontStore();
const router = useRouter();
const selectedImage = ref('');
const selectedVariant = ref('Standard');
const selectedColor = ref('Rose');
const selectedSize = ref('M');
const quantity = ref(1);

onMounted(async () => {
    await store.initialize();
    hydrateSelections();
});

watch(() => props.id, hydrateSelections);

const product = computed(() => store.productById(props.id));
const related = computed(() => store.relatedProducts(props.id));
const gallery = computed(() => {
    if (!product.value) {
        return [];
    }

    return [product.value.image_url, product.value.image_url, product.value.image_url, product.value.image_url].filter(Boolean);
});

function hydrateSelections() {
    if (!product.value) {
        return;
    }

    selectedImage.value = product.value.image_url || '';
    selectedVariant.value = product.value.variant_options?.[0] || 'Standard';
    selectedColor.value = product.value.color_options?.[0] || 'Rose';
    selectedSize.value = product.value.size_options?.[0] || 'M';
    quantity.value = 1;
}

function addToCart() {
    store.addToCart(product.value.id, quantity.value, `${selectedVariant.value} / ${selectedColor.value} / ${selectedSize.value}`);
}

function buyNow() {
    addToCart();
    router.push('/checkout');
}
</script>
