<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1480px] overflow-hidden rounded-[32px]">
            <ProductSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @quick-action="scrollToSlideForm"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <ProductHeader
                    :dashboard="dashboard"
                    :screen="screen"
                    @primary-action="scrollToSlideForm"
                    @refresh="loadDashboard"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 bg-[linear-gradient(180deg,rgba(247,248,252,0.75),rgba(239,244,255,0.92))] p-4 sm:p-6 lg:p-8">
                    <section
                        v-if="notice"
                        class="mb-6 rounded-[24px] border px-5 py-4 text-sm"
                        :class="notice.type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
                    >
                        {{ notice.text }}
                    </section>

                    <div v-if="isLoading" class="admin-card rounded-[28px] px-6 py-12 text-center text-sm text-slate-500">
                        Loading slider manager...
                    </div>

                    <section v-else class="w-full">
                        <SlidesManager
                            :dashboard="dashboard"
                            :deleting-slide-id="isDeletingSlideId"
                            :errors="slideErrors"
                            :form="slideForm"
                            :is-saving="isSavingSlide"
                            :mode="isEditingSlide ? 'edit' : 'create'"
                            :reset-token="slideFormResetToken"
                            :slide-title="editingSlideTitle"
                            @delete-slide="handleSlideDelete"
                            @edit-slide="handleSlideEdit"
                            @reset="resetSlideForm"
                            @submit="handleSlideSubmit"
                        />
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { nextTick, onMounted } from 'vue';

import ProductHeader from '../products/sections/ProductHeader.vue';
import ProductSidebar from '../products/sections/ProductSidebar.vue';
import SlidesManager from './sections/SlidesManager.vue';
import { useSlideDashboard } from './state/useSlideDashboard.js';

const {
    dashboard,
    deleteSlide,
    editSlide,
    editingSlideTitle,
    isDeletingSlideId,
    isEditingSlide,
    isLoading,
    isMenuOpen,
    isSavingSlide,
    loadDashboard,
    notice,
    resetSlideForm,
    screen,
    slideErrors,
    slideForm,
    slideFormResetToken,
    submitSlide,
    toggleMenu,
} = useSlideDashboard();

onMounted(async () => {
    await loadDashboard();
    await nextTick();

    if (window.location.hash) {
        scrollToSection(window.location.hash, false);
    }
});

async function handleSlideSubmit() {
    const saved = await submitSlide();

    if (saved) {
        scrollToSection(document.querySelector('main'));
    }
}

async function handleSlideDelete(slide) {
    if (!slide?.id) {
        return;
    }

    const confirmed = window.confirm(`Delete ${slide.title}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    await deleteSlide(slide);
}

function handleSlideEdit(slide) {
    editSlide(slide);
    scrollToSlideForm(false);
}

function handleMenuSelection(item) {
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

function scrollToSlideForm(shouldFocus = true) {
    scrollToSection(document.getElementById('slides-form'), shouldFocus);
}

function scrollToSection(target, shouldFocus = false) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;

    if (!element) {
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
            document.getElementById('slide-title')?.focus();
        });
    }
}
</script>
