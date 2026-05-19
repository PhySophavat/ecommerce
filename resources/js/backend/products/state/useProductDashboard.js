import { computed, reactive, ref } from 'vue';

import { emptyVariantValues, variantValuesFromAttributes } from '../add-product/categoryConfig.js';

const editorActions = [
    { label: 'Bold', command: 'bold', value: null },
    { label: 'Italic', command: 'italic', value: null },
    { label: 'Bullet', command: 'insertUnorderedList', value: null },
    { label: 'Heading', command: 'formatBlock', value: 'h3' },
];

const currentScreen = normalizeScreen(window.__APP_CONTEXT__?.screen);
const endpoint = window.__APP_CONTEXT__?.endpoint ?? '/api/admin/products';
const resourceBase = window.__APP_CONTEXT__?.resource_base ?? endpoint;

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
            const response = await window.axios.get(endpoint, {
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
        const statuses = dashboard.value.form?.statuses ?? [];

        if (!statuses.find((item) => item.value === productForm.status) && statuses.length) {
            productForm.status = statuses.find((item) => item.value === 'active')?.value ?? statuses[0].value;
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
            const response = await window.axios.get(`${resourceBase}/${productId}`);
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

        const productPayload = buildProductPayload(productForm, dashboard.value.form?.categories ?? []);
        const validationErrors = validateProductPayload(productPayload);

        if (Object.keys(validationErrors).length > 0) {
            assignProductErrors(validationErrors);
            notice.value = {
                type: 'error',
                text: 'Please fix the highlighted fields and try again.',
            };
            isSavingProduct.value = false;

            return false;
        }

        const submissionVariants = buildSubmissionVariants(productPayload);
        const formData = new FormData();

        formData.append('name', productPayload.name);
        formData.append('category_id', productForm.category_id);
        formData.append('description', productPayload.description);
        formData.append('price', String(productPayload.price));
        formData.append('stock_quantity', String(productPayload.stock));
        formData.append('status', productPayload.status);
        formData.append('is_featured', productForm.is_featured ? '1' : '0');

        if (String(productForm.type ?? '').trim() !== '') {
            formData.append('type', productForm.type);
        }

        if (String(productForm.sku ?? '').trim() !== '') {
            formData.append('sku', productForm.sku);
        }

        if (String(productForm.discount_price).trim() !== '') {
            formData.append('discount_price', productForm.discount_price);
        }

        productForm.removed_image_ids.forEach((imageId) => {
            formData.append('removed_image_ids[]', imageId);
        });

        productForm.images.forEach((file) => {
            formData.append('images[]', file);
        });

        submissionVariants.forEach((variant, index) => {
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
                ? `${resourceBase}/${editingProductId.value}`
                : resourceBase;

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
            const response = await window.axios.delete(`${resourceBase}/${productId}`);

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
    return ['dashboard', 'products', 'add-product', 'featured-products', 'merchant-pending-products', 'merchant-approved-products', 'merchant-rejected-products'].includes(screen) ? screen : 'products';
}

function initialProductForm() {
    return {
        id: null,
        name: '',
        category_id: '',
        type: '',
        description: '',
        price: '',
        discount_price: '',
        stock_quantity: '',
        sku: '',
        status: 'active',
        is_featured: false,
        images: [],
        existing_images: [],
        removed_image_ids: [],
        variant_groups: [],
        variant_group_source: null,
        variants: [],
        variant_values: emptyVariantValues(),
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
    const normalizedVariants = normalizeVariants(product.variants);

    return {
        id: product.id ?? null,
        name: product.name ?? '',
        category_id: product.category_id ? String(product.category_id) : '',
        type: product.type ?? '',
        description: product.description ?? '',
        price: product.price ?? '',
        discount_price: product.discount_price ?? '',
        stock_quantity: product.stock_quantity ?? '',
        sku: product.sku ?? '',
        status: product.status ?? 'active',
        is_featured: Boolean(product.is_featured),
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
        variants: normalizedVariants,
        variant_values: variantValuesFromAttributes(normalizedVariants[0]?.attributes ?? []),
    };
}

function cloneProductForm(product) {
    const normalizedVariants = normalizeVariants(product.variants);

    return {
        id: product.id ?? null,
        name: product.name ?? '',
        category_id: product.category_id ?? '',
        type: product.type ?? '',
        description: product.description ?? '',
        price: product.price ?? '',
        discount_price: product.discount_price ?? '',
        stock_quantity: product.stock_quantity ?? '',
        sku: product.sku ?? '',
        status: product.status ?? 'active',
        is_featured: Boolean(product.is_featured),
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
        variants: normalizedVariants,
        variant_values: {
            ...emptyVariantValues(),
            ...(product.variant_values ?? variantValuesFromAttributes(normalizedVariants[0]?.attributes ?? [])),
        },
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

function buildProductPayload(form, categories = []) {
    const selectedCategory = categories.find((item) => String(item.id) === String(form.category_id));

    return {
        name: String(form.name ?? '').trim(),
        category: selectedCategory?.slug ?? '',
        description: String(form.description ?? '').trim(),
        price: Number.parseFloat(String(form.price ?? '')),
        stock: Number.parseInt(String(form.stock_quantity ?? ''), 10),
        status: String(form.status ?? '').trim() || 'active',
        images: Array.isArray(form.images) ? [...form.images] : [],
        variants: Array.isArray(form.variants) ? form.variants : [],
    };
}

function validateProductPayload(payload) {
    const errors = {};

    if (!payload.name) {
        errors.name = ['Product name is required.'];
    }

    if (!payload.category) {
        errors.category_id = ['Category is required.'];
    }

    if (!payload.description) {
        errors.description = ['Description is required.'];
    }

    if (!Number.isFinite(payload.price)) {
        errors.price = ['Price is required.'];
    } else if (payload.price <= 0) {
        errors.price = ['Price must be greater than 0.'];
    }

    if (!Number.isFinite(payload.stock)) {
        errors.stock_quantity = ['Stock is required.'];
    } else if (payload.stock < 0) {
        errors.stock_quantity = ['Stock cannot be negative.'];
    }

    if (!payload.status) {
        errors.status = ['Status is required.'];
    }

    if (!Array.isArray(payload.variants) || payload.variants.length === 0) {
        errors.variants = ['At least one product variant is required.'];
        return errors;
    }

    payload.variants.forEach((variant, index) => {
        if (!String(variant.label ?? '').trim()) {
            errors[`variants.${index}.label`] = ['Variant label is required.'];
        }

        if (!Array.isArray(variant.attributes) || variant.attributes.length === 0) {
            errors[`variants.${index}.attributes`] = ['Variant attributes are required.'];
        }

        const price = Number.parseFloat(String(variant.price ?? ''));
        if (!Number.isFinite(price) || price < 0) {
            errors[`variants.${index}.price`] = ['Variant price must be 0 or greater.'];
        }

        const stock = Number.parseInt(String(variant.stock ?? ''), 10);
        if (!Number.isInteger(stock) || stock < 0) {
            errors[`variants.${index}.stock`] = ['Variant stock must be 0 or greater.'];
        }
    });

    return errors;
}

function buildSubmissionVariants(payload) {
    return (payload.variants ?? []).map((variant) => ({
        label: String(variant.label ?? '').trim(),
        attributes: Array.isArray(variant.attributes)
            ? variant.attributes
                .map((attribute) => ({
                    name: String(attribute.name ?? '').trim(),
                    value: String(attribute.value ?? '').trim(),
                }))
                .filter((attribute) => attribute.name !== '' && attribute.value !== '')
            : [],
        variant_sku: String(variant.variant_sku ?? '').trim(),
        price: String(variant.price ?? ''),
        stock: String(variant.stock ?? ''),
        image: variant.image ?? null,
        existing_image_path: variant.existing_image_path ?? '',
        remove_existing_image: Boolean(variant.remove_existing_image),
    }));
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
            subheadline: 'Manage products, categories, stock, and approval status.',
        },
        'featured-products': {
            page_title: 'Featured Products',
            kicker: 'Storefront highlights',
            subheadline: 'Loading featured products...',
        },
        'add-product': {
            page_title: 'Add Product',
            kicker: 'Catalog creation',
            subheadline: 'Loading add product form...',
        },
        'merchant-pending-products': {
            page_title: 'Pending Products',
            kicker: 'Merchant catalog',
            subheadline: 'Loading pending products...',
        },
        'merchant-approved-products': {
            page_title: 'Approved Products',
            kicker: 'Merchant catalog',
            subheadline: 'Loading approved products...',
        },
        'merchant-rejected-products': {
            page_title: 'Rejected Products',
            kicker: 'Merchant catalog',
            subheadline: 'Loading rejected products...',
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
                logout: '/auth/logout',
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
        dashboard_workspace: {
            hero: {
                eyebrow: 'Analytics',
                title: 'Dashboard',
                description: 'Loading dashboard analytics...',
            },
            summary_cards: [],
            range_options: [],
            selected_range: '30days',
            datasets: {},
            store_health: [],
            recent_orders: [],
            low_stock_products: [],
            actions: {},
        },
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
