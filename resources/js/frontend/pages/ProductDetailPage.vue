<template>
    <div class="mx-auto w-full lg:w-[80%] px-4 py-8 sm:px-6 lg:px-8">
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
                <div v-if="gallery.length > 1" class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <button
                        v-for="(thumb, index) in gallery"
                        :key="`${thumb}-${index}`"
                        type="button"
                        class="overflow-hidden rounded-[22px] border bg-white transition"
                        :class="selectedImage === thumb ? 'border-[#1495E8] ring-2 ring-[#DCEEFF]' : 'border-[#D8E7F4]'"
                        @click="selectedImage = thumb"
                    >
                        <img :src="thumb" :alt="`${product.name} ${index + 1}`" class="aspect-square h-full w-full object-cover">
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
                    <div class="text-4xl font-black tracking-[-0.05em] text-[#1495E8]">{{ displayPrice }}</div>
                    <div v-if="product.compare_at_price" class="pb-1 text-lg text-[#94A3B8] line-through">{{ product.compare_at_price }}</div>
                </div>

                <p class="mt-6 text-sm leading-7 text-[#6B7280]">
                    {{ product.description || product.tagline }}
                </p>

                <div class="mt-8 space-y-6">
                    <div v-for="group in variantGroups" :key="group.name">
                        <h3 class="text-sm font-semibold text-[#111827]">{{ group.name }}</h3>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="option in group.options"
                                :key="`${group.name}-${option}`"
                                type="button"
                                class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                                :class="selectedOption(group.name) === option ? 'border-[#1495E8] bg-[#F3F9FD] text-[#1495E8]' : 'border-[#D8E7F4] text-[#6B7280]'"
                                @click="setSelectedOption(group.name, option)"
                            >
                                {{ option }}
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="inline-flex items-center rounded-full border border-[#D8E7F4] bg-[#F3F9FD]">
                            <button type="button" class="px-4 py-3 text-lg text-[#1495E8]" @click="quantity = Math.max(1, quantity - 1)">-</button>
                            <span class="min-w-[3rem] text-center font-semibold text-[#111827]">{{ quantity }}</span>
                            <button type="button" class="px-4 py-3 text-lg text-[#1495E8]" @click="quantity = Math.min(maxQuantity || quantity + 1, quantity + 1)">+</button>
                        </div>
                        <Button block size="lg" :disabled="!canOrderSelection" @click="addToCart">
                            {{ canOrderSelection ? 'Add to Cart' : 'Unavailable' }}
                        </Button>
                        <Button block variant="secondary" size="lg" :disabled="!canOrderSelection" @click="buyNow">Buy Now</Button>
                    </div>

                    <p v-if="selectedVariantRecord && selectedVariantRecord.sku" class="text-sm text-[#6B7280]">
                        SKU: {{ selectedVariantRecord.sku }}
                    </p>

                    <p v-if="!canOrderSelection" class="text-sm font-medium text-amber-600">
                        This selection cannot be ordered until it is approved and in stock.
                    </p>

                   
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
const selectedAttributes = reactive({});
const quantity = ref(1);

onMounted(async () => {
    await store.initialize();
    hydrateSelections();
});

const product = computed(() => store.productById(props.id));
const related = computed(() => store.relatedProducts(props.id));
const variantGroups = computed(() => product.value?.variant_groups ?? []);
const selectedVariantRecord = computed(() => {
    if (!product.value?.variants?.length) {
        return null;
    }

    return product.value.variants.find((variant) => variant.attributes.every((attribute) => selectedAttributes[attribute.name] === attribute.value))
        ?? null;
});
const displayPrice = computed(() => selectedVariantRecord.value?.price || product.value?.price || '$0.00');
const maxQuantity = computed(() => Number(selectedVariantRecord.value?.stock ?? product.value?.inventory ?? 0));
const canOrderSelection = computed(() => Boolean(product.value?.is_orderable) && maxQuantity.value > 0);
const gallery = computed(() => {
    if (!product.value) {
        return [];
    }

    const productGallery = Array.isArray(product.value.image_urls) && product.value.image_urls.length
        ? product.value.image_urls
        : [product.value.image_url].filter(Boolean);

    if (selectedVariantRecord.value?.image_url) {
        return [selectedVariantRecord.value.image_url, ...productGallery.filter((image) => image !== selectedVariantRecord.value.image_url)];
    }

    return productGallery;
});

watch(() => props.id, hydrateSelections);
watch(product, hydrateSelections);
watch(selectedVariantRecord, () => {
    selectedImage.value = gallery.value[0] || '';
    quantity.value = Math.min(quantity.value, maxQuantity.value || 1);
});

function hydrateSelections() {
    if (!product.value) {
        return;
    }

    selectedImage.value = gallery.value[0] || '';
    Object.keys(selectedAttributes).forEach((key) => {
        delete selectedAttributes[key];
    });

    const seedVariant = product.value.variants?.[0] ?? null;
    seedVariant?.attributes?.forEach((attribute) => {
        selectedAttributes[attribute.name] = attribute.value;
    });

    quantity.value = 1;
}

function addToCart() {
    if (!product.value) {
        return false;
    }

    return store.addToCart(product.value.id, quantity.value, selectedVariantRecord.value
        ? {
            variant_id: selectedVariantRecord.value.id,
        }
        : null);
}

function buyNow() {
    if (!addToCart()) {
        return;
    }

    router.push('/checkout');
}

function selectedOption(name) {
    return selectedAttributes[name] ?? '';
}

function setSelectedOption(name, value) {
    selectedAttributes[name] = value;
    quantity.value = Math.min(quantity.value, maxQuantity.value || 1);
}
</script>
