import { computed, reactive, ref } from 'vue';

const editorActions = [
    { label: 'Bold', command: 'bold', value: null },
    { label: 'Italic', command: 'italic', value: null },
    { label: 'Bullet', command: 'insertUnorderedList', value: null },
    { label: 'Heading', command: 'formatBlock', value: 'h3' },
];

const currentScreen = normalizeScreen(window.__APP_CONTEXT__?.screen);

export function useProductDashboard() {
    const dashboard = ref(initialDashboard());
    const isLoading = ref(true);
    const isLoadingEditor = ref(false);
    const isSavingProduct = ref(false);
    const isDeletingProductId = ref(null);
    const notice = ref(null);
    const openMenus = ref({});
    const formResetToken = ref(0);
    const productForm = reactive(initialProductForm());
    const productErrors = reactive({});
    const editingProductId = ref(currentEditProductId());
    const editingProductName = ref('');
    const editingProductSnapshot = ref(null);
    const isEditingProduct = computed(() => Boolean(editingProductId.value));

    async function loadDashboard({ preserveNotice = false } = {}) {
        isLoading.value = true;

        try {
            const response = await window.axios.get('/api/admin/products', {
                params: {
                    screen: currentScreen,
                },
            });

            dashboard.value = response.data;
            syncOpenMenus(response.data.menu ?? []);
            applyFormDefaults();

            if (!preserveNotice) {
                notice.value = null;
            }
        } catch (error) {
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to load the admin dashboard right now.'),
            };
        } finally {
            isLoading.value = false;
        }
    }

    function syncOpenMenus(menuItems) {
        openMenus.value = menuItems.reduce((state, item) => {
            state[item.slug] = Boolean(item.is_expanded || item.is_active);

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

    function clearProductErrors() {
        Object.keys(productErrors).forEach((key) => {
            delete productErrors[key];
        });
    }

    function assignProductErrors(errors = {}) {
        clearProductErrors();

        Object.entries(errors).forEach(([key, value]) => {
            productErrors[key] = value;
        });
    }

    function applyFormDefaults() {
        const categories = dashboard.value.form?.categories ?? [];
        const types = dashboard.value.form?.types ?? [];
        const statuses = dashboard.value.form?.statuses ?? [];

        if (!productForm.category_id && categories.length) {
            productForm.category_id = String(categories[0].id);
        }

        if (!types.find((item) => item.value === productForm.type) && types.length) {
            productForm.type = types[0].value;
        }

        if (!statuses.find((item) => item.value === productForm.status) && statuses.length) {
            productForm.status = statuses[0].value;
        }
    }

    function resetProductForm() {
        clearProductErrors();
        clearVariantPreviewUrls(productForm.variants);

        if (editingProductSnapshot.value) {
            applyProductForm(editingProductSnapshot.value);

            return;
        }

        Object.assign(productForm, initialProductForm());
        applyFormDefaults();
        formResetToken.value += 1;
    }

    function applyProductForm(product) {
        clearVariantPreviewUrls(productForm.variants);
        Object.assign(productForm, initialProductForm(), cloneProductForm(product));
        applyFormDefaults();
        formResetToken.value += 1;
    }

    async function loadProductForEdit(productId = editingProductId.value) {
        if (!productId) {
            return false;
        }

        isLoadingEditor.value = true;

        try {
            const response = await window.axios.get(`/api/admin/products/${productId}`);
            const editableProduct = normalizeEditableProduct(response.data?.product ?? {});

            editingProductId.value = editableProduct.id;
            editingProductName.value = editableProduct.name;
            editingProductSnapshot.value = cloneProductForm(editableProduct);
            clearProductErrors();
            applyProductForm(editableProduct);

            return true;
        } catch (error) {
            clearEditingState({ updateUrl: true });
            Object.assign(productForm, initialProductForm());
            applyFormDefaults();
            formResetToken.value += 1;
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to load that product for editing right now.'),
            };

            return false;
        } finally {
            isLoadingEditor.value = false;
        }
    }

    function clearEditingState({ updateUrl = false } = {}) {
        editingProductId.value = null;
        editingProductName.value = '';
        editingProductSnapshot.value = null;

        if (updateUrl && currentScreen === 'add-product') {
            const url = new URL(window.location.href);
            url.searchParams.delete('edit');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        }
    }

    async function submitProduct() {
        isSavingProduct.value = true;
        clearProductErrors();
        notice.value = null;

        const formData = new FormData();

        formData.append('name', productForm.name);
        formData.append('category_id', productForm.category_id);
        formData.append('type', productForm.type);
        formData.append('description', productForm.description);
        formData.append('price', productForm.price);
        formData.append('stock_quantity', productForm.stock_quantity);
        formData.append('sku', productForm.sku);
        formData.append('status', productForm.status);

        if (String(productForm.discount_price).trim() !== '') {
            formData.append('discount_price', productForm.discount_price);
        }

        productForm.removed_image_ids.forEach((imageId) => {
            formData.append('removed_image_ids[]', imageId);
        });

        productForm.images.forEach((file) => {
            formData.append('images[]', file);
        });

        productForm.variants.forEach((variant, index) => {
            formData.append(`variants[${index}][label]`, variant.label);
            formData.append(`variants[${index}][variant_sku]`, variant.variant_sku ?? '');

            variant.attributes.forEach((attribute, attributeIndex) => {
                formData.append(`variants[${index}][attributes][${attributeIndex}][name]`, attribute.name);
                formData.append(`variants[${index}][attributes][${attributeIndex}][value]`, attribute.value);
            });

            formData.append(`variants[${index}][existing_image_path]`, variant.existing_image_path ?? '');
            formData.append(`variants[${index}][remove_existing_image]`, variant.remove_existing_image ? '1' : '0');

            if (variant.image instanceof File) {
                formData.append(`variants[${index}][image]`, variant.image);
            }

            formData.append(`variants[${index}][price]`, variant.price);
            formData.append(`variants[${index}][stock]`, variant.stock);
        });

        try {
            const requestUrl = isEditingProduct.value
                ? `/api/admin/products/${editingProductId.value}`
                : '/api/admin/products';

            if (isEditingProduct.value) {
                formData.append('_method', 'PUT');
            }

            const response = await window.axios.post(requestUrl, formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            await loadDashboard({ preserveNotice: true });

            if (isEditingProduct.value) {
                await loadProductForEdit(editingProductId.value);
            } else {
                resetProductForm();
            }

            notice.value = {
                type: 'success',
                text: response.data?.message ?? (isEditingProduct.value ? 'Product was updated successfully.' : 'Product was created successfully.'),
            };

            return true;
        } catch (error) {
            if (error?.response?.status === 422 && error.response.data?.errors) {
                assignProductErrors(error.response.data.errors);
            }

            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Please fix the highlighted fields and try again.'),
            };

            return false;
        } finally {
            isSavingProduct.value = false;
        }
    }

    async function deleteProduct(product) {
        const productId = typeof product === 'object' ? product?.id : product;
        const productName = typeof product === 'object' ? product?.name : 'Product';

        if (!productId) {
            return false;
        }

        isDeletingProductId.value = productId;
        notice.value = null;

        try {
            const response = await window.axios.delete(`/api/admin/products/${productId}`);

            if (editingProductId.value === productId) {
                clearEditingState({ updateUrl: true });
                Object.assign(productForm, initialProductForm());
                applyFormDefaults();
                formResetToken.value += 1;
            }

            await loadDashboard({ preserveNotice: true });

            notice.value = {
                type: 'success',
                text: response.data?.message ?? `${productName} was deleted successfully.`,
            };

            return true;
        } catch (error) {
            notice.value = {
                type: 'error',
                text: extractMessage(error, 'Unable to delete that product right now.'),
            };

            return false;
        } finally {
            isDeletingProductId.value = null;
        }
    }

    return {
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
        screen: currentScreen,
        submitProduct,
        toggleMenu,
    };
}

function normalizeScreen(screen) {
    return ['dashboard', 'products', 'add-product'].includes(screen) ? screen : 'products';
}

function initialProductForm() {
    return {
        id: null,
        name: '',
        category_id: '',
        type: 'men',
        description: '',
        price: '',
        discount_price: '',
        stock_quantity: '',
        sku: '',
        status: 'active',
        images: [],
        existing_images: [],
        removed_image_ids: [],
        variant_groups: [],
        variant_group_source: null,
        variants: [],
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

function currentEditProductId() {
    const value = new URLSearchParams(window.location.search).get('edit');
    const parsed = Number.parseInt(value ?? '', 10);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
}

function normalizeEditableProduct(product) {
    return {
        id: product.id ?? null,
        name: product.name ?? '',
        category_id: product.category_id ? String(product.category_id) : '',
        type: product.type ?? 'men',
        description: product.description ?? '',
        price: product.price ?? '',
        discount_price: product.discount_price ?? '',
        stock_quantity: product.stock_quantity ?? '',
        sku: product.sku ?? '',
        status: product.status ?? 'active',
        images: [],
        existing_images: Array.isArray(product.existing_images)
            ? product.existing_images.map((image) => ({
                id: image.id,
                name: image.name,
                url: image.url,
            }))
            : [],
        removed_image_ids: [],
        variant_groups: normalizeVariantGroups(product.variant_groups),
        variant_group_source: product.variant_group_source ?? product.category_slug ?? null,
        variants: normalizeVariants(product.variants),
    };
}

function cloneProductForm(product) {
    return {
        id: product.id ?? null,
        name: product.name ?? '',
        category_id: product.category_id ?? '',
        type: product.type ?? 'men',
        description: product.description ?? '',
        price: product.price ?? '',
        discount_price: product.discount_price ?? '',
        stock_quantity: product.stock_quantity ?? '',
        sku: product.sku ?? '',
        status: product.status ?? 'active',
        images: [],
        existing_images: Array.isArray(product.existing_images)
            ? product.existing_images.map((image) => ({
                id: image.id,
                name: image.name,
                url: image.url,
            }))
            : [],
        removed_image_ids: Array.isArray(product.removed_image_ids)
            ? [...product.removed_image_ids]
            : [],
        variant_groups: normalizeVariantGroups(product.variant_groups),
        variant_group_source: product.variant_group_source ?? product.category_slug ?? null,
        variants: normalizeVariants(product.variants),
    };
}

function normalizeVariants(variants = []) {
    if (!Array.isArray(variants) || variants.length === 0) {
        return [];
    }

    return variants.map((variant) => ({
        label: variant.label ?? '',
        attributes: Array.isArray(variant.attributes)
            ? variant.attributes.map((attribute) => ({
                name: attribute.name ?? '',
                value: attribute.value ?? '',
            }))
            : [],
        variant_sku: variant.variant_sku ?? '',
        price: variant.price ?? '',
        stock: variant.stock ?? '',
        image: null,
        image_preview_url: '',
        existing_image_url: variant.existing_image_url ?? '',
        existing_image_path: variant.existing_image_path ?? '',
        remove_existing_image: Boolean(variant.remove_existing_image),
    }));
}

function normalizeVariantGroups(groups = []) {
    if (!Array.isArray(groups)) {
        return [];
    }

    return groups.map((group) => ({
        name: group.name ?? '',
        options_text: group.options_text ?? '',
    })).filter((group) => group.name !== '');
}

function clearVariantPreviewUrls(variants = []) {
    variants.forEach((variant) => {
        if (variant?.image_preview_url) {
            URL.revokeObjectURL(variant.image_preview_url);
        }
    });
}

function initialDashboard() {
    const meta = {
        dashboard: {
            page_title: 'Dashboard',
            kicker: 'Store overview',
            subheadline: 'Loading dashboard...',
        },
        products: {
            page_title: 'Products',
            kicker: 'Catalog control center',
            subheadline: 'Loading products...',
        },
        'add-product': {
            page_title: 'Add Product',
            kicker: 'Catalog creation',
            subheadline: 'Loading add product form...',
        },
    }[currentScreen];

    return {
        screen: currentScreen,
        meta: {
            brand: 'Spodut',
            page_title: meta.page_title,
            kicker: meta.kicker,
            subheadline: meta.subheadline,
            links: {
                frontend: '/frontend',
                admin_users: '/admin/products',
            },
        },
        form: {
            categories: [],
            types: [],
            statuses: [],
            sizes: [],
            colors: [],
        },
        summary: [],
        highlights: [],
        menu: [],
        products: {
            count: 0,
            items: [],
            pagination: {
                from: 0,
                to: 0,
                total: 0,
            },
        },
    };
}
