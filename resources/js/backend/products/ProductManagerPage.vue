<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
            <ProductSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @quick-action="handlePrimaryAction"
                @scroll-add-product="scrollToAddProduct"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <ProductHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @refresh="loadDashboard"
                    @primary-action="handlePrimaryAction"
                    @scroll-add-product="scrollToAddProduct"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">
                    <section
                        v-if="notice"
                        class="admin-frosted mb-6 rounded-[26px] px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="admin-card rounded-[30px] px-6 py-14 text-center text-sm text-slate-500">
                        Loading dashboard data...
                    </div>

                    <div v-else-if="isLoadingEditor" class="admin-card rounded-[30px] px-6 py-14 text-center text-sm text-slate-500">
                        Loading product editor...
                    </div>

                    <template v-else>
                        <template v-if="screen === 'dashboard'">
                            <SummaryCardsGrid :cards="dashboard.summary" />
                            <MenuHighlights :highlights="dashboard.highlights" />
                            <DashboardOverview :products="dashboard.products.items" @open-add-product="scrollToAddProduct" @open-products="openProductsView()" />
                        </template>

                        <template v-else-if="screen === 'products' || screen === 'featured-products'">
                            <ProductsTable
                                :categories="dashboard.form.categories"
                                :deleting-product-id="isDeletingProductId"
                                :products="dashboard.products"
                                :screen="screen"
                                @delete-product="handleProductDelete"
                                @edit-product="openProductEditor"
                                @scroll-add-product="scrollToAddProduct"
                                @scroll-categories="scrollToSection('#categories')"
                                @scroll-inventory="scrollToSection('#inventory')"
                            />
                        </template>

                        <section v-else id="add-product" ref="addProductSection" class="mx-auto w-full max-w-[980px]">
                            <AddProductForm
                                :dashboard="dashboard"
                                :editor-actions="editorActions"
                                :errors="productErrors"
                                :form="productForm"
                                :is-saving="isSavingProduct"
                                :mode="isEditingProduct ? 'edit' : 'create'"
                                :product-name="editingProductName"
                                :reset-token="formResetToken"
                                @reset="resetProductForm"
                                @submit="handleProductSubmit"
                            />
                        </section>
                    </template>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue';

import AddProductForm from './add-product/AddProductForm.vue';
import DashboardOverview from './sections/DashboardOverview.vue';
import MenuHighlights from './sections/MenuHighlights.vue';
import ProductHeader from './sections/ProductHeader.vue';
import ProductSidebar from './sections/ProductSidebar.vue';
import ProductsTable from './sections/ProductsTable.vue';
import SummaryCardsGrid from './sections/SummaryCardsGrid.vue';
import { useProductDashboard } from './state/useProductDashboard.js';

const addProductSection = ref(null);

const {
    dashboard,
    deleteProduct,
    editingProductId,
    editingProductName,
    editorActions,
    formResetToken,
    isDeletingProductId,
    isEditingProduct,
    isLoading,
    isLoadingEditor,
    isMenuOpen,
    isSavingProduct,
    loadDashboard,
    loadProductForEdit,
    notice,
    productErrors,
    productForm,
    resetProductForm,
    screen,
    submitProduct,
    toggleMenu,
} = useProductDashboard();

onMounted(async () => {
    await loadDashboard();

    if (screen === 'add-product' && editingProductId.value) {
        await loadProductForEdit(editingProductId.value);
    }

    await nextTick();

    if (window.location.hash) {
        if (screen === 'add-product') {
            scrollToAddProduct(false);

            return;
        }

        scrollToSection(window.location.hash, false);
    }
});

async function handleProductDelete(product) {
    if (!product?.id) {
        return;
    }

    const confirmed = window.confirm(`Delete ${product.name}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    await deleteProduct(product);
}

async function handleProductSubmit() {
    const created = await submitProduct();

    if (created) {
        scrollToSection(document.querySelector('main'));

        return;
    }

    scrollToAddProduct(false);
}

function handleMenuSelection(item) {
    if (item.slug === 'add-product') {
        scrollToAddProduct();

        return;
    }

    if (!(item.is_enabled || item.slug === 'add-product') || !item.path) {
        return;
    }

    const url = new URL(item.path, window.location.origin);

    if (url.pathname === window.location.pathname && url.hash) {
        scrollToSection(url.hash);

        return;
    }

    window.location.href = `${url.pathname}${url.search}${url.hash}`;
}

function handlePrimaryAction() {
    if (screen === 'dashboard') {
        openProductsView();

        return;
    }

    if (screen === 'products') {
        scrollToAddProduct();

        return;
    }

    if (screen === 'featured-products') {
        scrollToAddProduct();

        return;
    }

    openProductsView();
}

async function openProductEditor(product) {
    const productId = typeof product === 'object' ? product?.id : product;

    if (!productId) {
        return;
    }

    const targetUrl = `/admin/products/create?edit=${productId}`;

    if (screen !== 'add-product') {
        window.location.href = targetUrl;

        return;
    }

    window.history.replaceState({}, '', targetUrl);
    await loadProductForEdit(productId);
    scrollToAddProduct(false);
}

function scrollToAddProduct(shouldFocus = true) {
    if (screen !== 'add-product') {
        window.location.href = '/admin/products/create';

        return;
    }

    scrollToSection(addProductSection.value, shouldFocus);
}

function scrollToSection(target, shouldFocus = false) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;

    if (!element) {
        if (typeof target === 'string' && screen !== 'products') {
            openProductsView(target);
        }

        return;
    }

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });

    if (element.id) {
        window.history.replaceState({}, '', `${window.location.pathname}${window.location.search}#${element.id}`);
    }

    if (shouldFocus) {
        queueMicrotask(() => {
            document.getElementById('product-name')?.focus();
        });
    }
}

function openProductsView(hash = '') {
    window.location.href = `/admin/products${hash}`;
}
</script>
