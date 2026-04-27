import { computed, reactive, ref } from 'vue';

const currentScreen = 'sliders';

export function useSlideDashboard() {
    const dashboard = ref(initialDashboard());
    const isLoading = ref(true);
    const isSavingSlide = ref(false);
    const isDeletingSlideId = ref(null);
    const notice = ref(null);
    const openMenus = ref({});
    const slideFormResetToken = ref(0);
    const slideForm = reactive(initialSlideForm());
    const slideErrors = reactive({});
    const editingSlideId = ref(null);
    const editingSlideTitle = ref('');
    const editingSlideSnapshot = ref(null);
    const isEditingSlide = computed(() => Boolean(editingSlideId.value));

    async function loadDashboard({ preserveNotice = false } = {}) {
        isLoading.value = true;

        try {
            const response = await window.axios.get('/api/admin/slides/dashboard');

            dashboard.value = response.data;
            syncOpenMenus(response.data.menu ?? []);
            syncSlideEditor(response.data.slides?.items ?? []);

            if (!editingSlideId.value && isCreateFormPristine(slideForm)) {
                slideForm.sort_order = nextSlideSortOrder(dashboard.value);
            }

            if (!preserveNotice) {
                notice.value = null;
            }
        } catch (error) {
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to load the slide manager right now.'),
            };
        } finally {
            isLoading.value = false;
        }
    }

    function syncOpenMenus(menuItems) {
        openMenus.value = menuItems.reduce((state, item) => {
            state[item.slug] = Boolean(item.is_expanded);

            return state;
        }, {});
    }

    function toggleMenu(slug) {
        openMenus.value = {
            ...openMenus.value,
            [slug]: !openMenus.value[slug],
        };
    }

    function isMenuOpen(slug) {
        return Boolean(openMenus.value[slug]);
    }

    function clearSlideErrors() {
        Object.keys(slideErrors).forEach((key) => {
            delete slideErrors[key];
        });
    }

    function assignSlideErrors(errors = {}) {
        clearSlideErrors();

        Object.entries(errors).forEach(([key, value]) => {
            slideErrors[key] = value;
        });
    }

    function resetSlideForm() {
        clearSlideErrors();
        clearSlidePreviewUrl(slideForm);

        if (editingSlideSnapshot.value) {
            applySlideForm(editingSlideSnapshot.value);

            return;
        }

        Object.assign(slideForm, initialSlideForm());
        slideForm.sort_order = nextSlideSortOrder(dashboard.value);
        slideFormResetToken.value += 1;
    }

    function applySlideForm(slide) {
        clearSlidePreviewUrl(slideForm);
        Object.assign(slideForm, initialSlideForm(), cloneSlideForm(slide));
        slideFormResetToken.value += 1;
    }

    function syncSlideEditor(slides = []) {
        if (!editingSlideId.value) {
            return;
        }

        const currentSlide = slides.find((slide) => Number.parseInt(slide.id, 10) === editingSlideId.value);

        if (!currentSlide) {
            clearSlideEditingState();
            Object.assign(slideForm, initialSlideForm());
            slideForm.sort_order = nextSlideSortOrder(dashboard.value);
            slideFormResetToken.value += 1;

            return;
        }

        const editableSlide = normalizeEditableSlide(currentSlide);

        editingSlideTitle.value = editableSlide.title;
        editingSlideSnapshot.value = cloneSlideForm(editableSlide);
        applySlideForm(editableSlide);
    }

    function editSlide(slide) {
        const editableSlide = normalizeEditableSlide(slide);

        editingSlideId.value = editableSlide.id;
        editingSlideTitle.value = editableSlide.title;
        editingSlideSnapshot.value = cloneSlideForm(editableSlide);
        clearSlideErrors();
        applySlideForm(editableSlide);
    }

    async function submitSlide() {
        isSavingSlide.value = true;
        clearSlideErrors();
        notice.value = null;

        const formData = new FormData();

        if (slideForm.category_id) {
            formData.append('category_id', slideForm.category_id);
        }

        formData.append('eyebrow', slideForm.eyebrow);
        formData.append('title', slideForm.title);
        formData.append('highlight', slideForm.highlight);
        formData.append('description', slideForm.description);
        formData.append('button_text', slideForm.button_text);
        formData.append('button_url', slideForm.button_url);
        formData.append('badge_text', slideForm.badge_text);
        formData.append('is_active', slideForm.is_active ? '1' : '0');
        formData.append('sort_order', slideForm.sort_order);
        formData.append('remove_existing_image', slideForm.remove_existing_image ? '1' : '0');

        if (slideForm.image instanceof File) {
            formData.append('image', slideForm.image);
        }

        try {
            const requestUrl = isEditingSlide.value
                ? `/api/admin/slides/${editingSlideId.value}`
                : '/api/admin/slides';

            if (isEditingSlide.value) {
                formData.append('_method', 'PUT');
            }

            const response = await window.axios.post(requestUrl, formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            const editableSlide = normalizeEditableSlide(response.data?.slide ?? {});

            await loadDashboard({ preserveNotice: true });

            if (isEditingSlide.value) {
                editingSlideSnapshot.value = cloneSlideForm(editableSlide);
                editingSlideTitle.value = editableSlide.title;
                applySlideForm(editableSlide);
            } else {
                resetSlideForm();
            }

            notice.value = {
                type: 'success',
                text: response.data?.message ?? (isEditingSlide.value ? 'Slide was updated successfully.' : 'Slide was created successfully.'),
            };

            return true;
        } catch (error) {
            if (error?.response?.status === 422 && error.response.data?.errors) {
                assignSlideErrors(error.response.data.errors);
            }

            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Please fix the slide fields and try again.'),
            };

            return false;
        } finally {
            isSavingSlide.value = false;
        }
    }

    async function deleteSlide(slide) {
        const slideId = typeof slide === 'object' ? slide?.id : slide;
        const slideTitle = typeof slide === 'object' ? slide?.title : 'Slide';

        if (!slideId) {
            return false;
        }

        isDeletingSlideId.value = slideId;
        notice.value = null;

        try {
            const response = await window.axios.delete(`/api/admin/slides/${slideId}`);

            if (editingSlideId.value === slideId) {
                clearSlideEditingState();
                Object.assign(slideForm, initialSlideForm());
                slideFormResetToken.value += 1;
            }

            await loadDashboard({ preserveNotice: true });

            notice.value = {
                type: 'success',
                text: response.data?.message ?? `${slideTitle} slide was deleted successfully.`,
            };

            return true;
        } catch (error) {
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to delete that slide right now.'),
            };

            return false;
        } finally {
            isDeletingSlideId.value = null;
        }
    }

    function clearSlideEditingState() {
        editingSlideId.value = null;
        editingSlideTitle.value = '';
        editingSlideSnapshot.value = null;
    }

    return {
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
        screen: currentScreen,
        slideErrors,
        slideForm,
        slideFormResetToken,
        submitSlide,
        toggleMenu,
    };
}

function extractMessage(error, fallback) {
    const response = error?.response?.data;

    if (response?.errors) {
        const firstError = Object.values(response.errors).flat()[0];

        if (firstError) {
            return firstError;
        }
    }

    return response?.message ?? fallback;
}

function normalizeEditableSlide(slide) {
    return {
        id: slide.id ? Number.parseInt(slide.id, 10) || slide.id : null,
        category_id: slide.category_id ? String(slide.category_id) : '',
        eyebrow: slide.eyebrow ?? '',
        title: slide.title ?? '',
        highlight: slide.highlight ?? '',
        description: slide.description ?? '',
        button_text: slide.button_text ?? '',
        button_url: slide.button_url ?? '/frontend',
        badge_text: slide.badge_text ?? '',
        image: null,
        image_preview_url: '',
        existing_image_url: slide.existing_image_url ?? slide.image_url ?? '',
        existing_image_name: slide.existing_image_name ?? slide.image_name ?? '',
        remove_existing_image: Boolean(slide.remove_existing_image),
        is_active: Boolean(slide.is_active ?? true),
        sort_order: slide.sort_order ? String(slide.sort_order) : '1',
    };
}

function cloneSlideForm(slide) {
    return {
        id: slide.id ?? null,
        category_id: slide.category_id ? String(slide.category_id) : '',
        eyebrow: slide.eyebrow ?? '',
        title: slide.title ?? '',
        highlight: slide.highlight ?? '',
        description: slide.description ?? '',
        button_text: slide.button_text ?? '',
        button_url: slide.button_url ?? '/frontend',
        badge_text: slide.badge_text ?? '',
        image: null,
        image_preview_url: '',
        existing_image_url: slide.existing_image_url ?? slide.image_url ?? '',
        existing_image_name: slide.existing_image_name ?? slide.image_name ?? '',
        remove_existing_image: Boolean(slide.remove_existing_image),
        is_active: Boolean(slide.is_active ?? true),
        sort_order: slide.sort_order ? String(slide.sort_order) : '1',
    };
}

function initialSlideForm() {
    return {
        id: null,
        category_id: '',
        eyebrow: '',
        title: '',
        highlight: '',
        description: '',
        button_text: '',
        button_url: '/frontend',
        badge_text: '',
        image: null,
        image_preview_url: '',
        existing_image_url: '',
        existing_image_name: '',
        remove_existing_image: false,
        is_active: true,
        sort_order: '1',
    };
}

function clearSlidePreviewUrl(slide) {
    if (slide?.image_preview_url) {
        URL.revokeObjectURL(slide.image_preview_url);
    }
}

function initialDashboard() {
    return {
        screen: currentScreen,
        meta: {
            brand: 'Spodut',
            page_title: 'Slides',
            kicker: '',
            subheadline: 'Loading slider manager...',
            links: {
                frontend: '/frontend',
                admin_users: '/admin/products',
            },
        },
        form: {
            categories: [],
        },
        menu: [],
        slides: {
            count: 0,
            active_count: 0,
            next_sort_order: 1,
            items: [],
        },
    };
}

function nextSlideSortOrder(dashboard) {
    const value = Number.parseInt(dashboard?.slides?.next_sort_order ?? '', 10);

    return Number.isInteger(value) && value > 0 ? String(value) : '1';
}

function isCreateFormPristine(form) {
    return !form.id
        && !String(form.category_id ?? '').trim()
        && !String(form.eyebrow ?? '').trim()
        && !String(form.title ?? '').trim()
        && !String(form.highlight ?? '').trim()
        && !String(form.description ?? '').trim()
        && !String(form.button_text ?? '').trim()
        && String(form.button_url ?? '/frontend').trim() === '/frontend'
        && !String(form.badge_text ?? '').trim()
        && !form.image
        && !String(form.image_preview_url ?? '').trim()
        && !String(form.existing_image_url ?? '').trim()
        && !form.remove_existing_image
        && Boolean(form.is_active)
        && String(form.sort_order ?? '1').trim() === '1';
}
