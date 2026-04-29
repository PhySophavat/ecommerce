<template>
    <div class="chatgpt-admin min-h-screen px-3 py-3 sm:px-5 lg:px-8 lg:py-6">
        <div class="admin-panel mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1540px] overflow-hidden rounded-[36px]">
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
                    :screen="screen"
                    @primary-action="scrollToSlideForm"
                    @refresh="loadDashboard"
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
    if (!slide?.id) {
        return;
    }

    const confirmed = window.confirm(`Delete ${slide.title}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    await deleteSlide(slide);
}

async function submitLogout() {
    try {
        const logoutUrl = dashboard.value?.meta?.links?.logout ?? '/auth/logout';

        await window.axios.post(logoutUrl);
        window.location.assign('/login');
    } catch (error) {
        notice.value = {
            type: 'error',
            text: 'Unable to sign out right now.',
        };
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
