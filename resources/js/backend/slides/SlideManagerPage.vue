<template>
    <div class="admin-root min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-x-clip rounded-[36px]">
            <AdminSidebar
                :dashboard="dashboard"
                :is-menu-open="isMenuOpen"
                :screen="screen"
                @quick-action="scrollToSlideForm"
                @select-item="handleMenuSelection"
                @toggle-menu="toggleMenu"
            />

            <div class="flex min-w-0 flex-1 flex-col">
                <AdminHeader
                    :dashboard="dashboard"
                    :is-menu-open="isMenuOpen"
                    :screen="screen"
                    @primary-action="scrollToSlideForm"
                    @refresh="loadDashboard"
                    @select-item="handleMenuSelection"
                    @toggle-menu="toggleMenu"
                />

                <main class="flex-1 p-4 sm:p-6 lg:p-7">

                    <!-- Notice banner -->
                    <transition name="fade">
                        <section
                            v-if="notice"
                            class="notice mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium flex items-center gap-2"
                            :class="notice.type === 'error' ? 'notice--error' : 'notice--success'"
                        >
                            <span class="notice-dot"></span>
                            {{ notice.text }}
                        </section>
                    </transition>

                    <!-- Loading -->
                    <div
                        v-if="isLoading"
                        class="loading-card rounded-[30px] px-6 py-16 text-center text-sm text-slate-400"
                    >
                        <div class="loading-spinner mx-auto mb-3"></div>
                        Loading slider manager…
                    </div>

                    <!-- Content -->
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

import AdminHeader from '../layout/AdminHeader.vue';
import AdminSidebar from '../layout/AdminSidebar.vue';
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
        scrollToSection(document.getElementById('slides-table'));
    }
}

async function handleSlideDelete(slide) {
    if (!slide?.id) return;

    const confirmed = window.confirm(`Delete "${slide.title}"? This cannot be undone.`);
    if (!confirmed) return;

    await deleteSlide(slide);
}

async function submitLogout() {
    try {
        const logoutUrl = dashboard.value?.meta?.links?.logout ?? '/auth/logout';
        await window.axios.post(logoutUrl);
        window.location.assign('/login');
    } catch {
        notice.value = { type: 'error', text: 'Unable to sign out right now.' };
    }
}

function handleSlideEdit(slide) {
    editSlide(slide);
    scrollToSlideForm(false);
}

async function handleMenuSelection(item) {
    if (item.slug === 'logout') {
        await submitLogout();
        return;
    }

    if (!(item.is_enabled || item.slug === 'add-product') || !item.path) return;

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
    if (!element) return;

    element.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (element.id) {
        window.history.replaceState(
            {},
            '',
            `${window.location.pathname}${window.location.search}#${element.id}`
        );
    }

    if (shouldFocus) {
        queueMicrotask(() => document.getElementById('slide-title')?.focus());
    }
}
</script>

<style scoped>
/* Page background */
.admin-root {
    background: #faf7f9;
}

/* Outer panel */
.admin-panel {
    background: #ffffff;
    box-shadow: 0 8px 40px 0 rgba(162, 95, 136, 0.08);
}

/* Notice */
.notice {
    border: 1px solid transparent;
}
.notice--success {
    background: #f0faf4;
    border-color: #bbf7d0;
    color: #166534;
}
.notice--error {
    background: #fff1f2;
    border-color: #fecdd3;
    color: #be123c;
}
.notice-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 9999px;
    flex-shrink: 0;
}
.notice--success .notice-dot { background: #22c55e; }
.notice--error   .notice-dot { background: #f43f5e; }

/* Loading card */
.loading-card {
    background: #ffffff;
    border: 1px solid #f0e6ed;
    box-shadow: 0 1px 4px 0 rgba(162, 95, 136, 0.06);
}

/* Spinner */
.loading-spinner {
    width: 28px;
    height: 28px;
    border: 2.5px solid #f0e6ed;
    border-top-color: #A25F88;
    border-radius: 9999px;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Fade transition for notice */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s, transform 0.25s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
