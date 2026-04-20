import { reactive, ref } from 'vue';

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
    const isSavingProduct = ref(false);
    const notice = ref(null);
    const openMenus = ref({});
    const formResetToken = ref(0);
    const productForm = reactive(initialProductForm());
    const productErrors = reactive({});

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
        Object.assign(productForm, initialProductForm());
        applyFormDefaults();
        formResetToken.value += 1;
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

        productForm.images.forEach((file) => {
            formData.append('images[]', file);
        });

        productForm.variants
            .filter(variantHasData)
            .forEach((variant, index) => {
                formData.append(`variants[${index}][size]`, variant.size);
                formData.append(`variants[${index}][color]`, variant.color);
                formData.append(`variants[${index}][price]`, variant.price);
                formData.append(`variants[${index}][stock]`, variant.stock);
            });

        try {
            const response = await window.axios.post('/api/admin/products', formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            resetProductForm();
            await loadDashboard({ preserveNotice: true });

            notice.value = {
                type: 'success',
                text: response.data?.message ?? 'Product was created successfully.',
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

    return {
        dashboard,
        editorActions,
        formResetToken,
        isLoading,
        isMenuOpen,
        isSavingProduct,
        loadDashboard,
        notice,
        openMenus,
        productErrors,
        productForm,
        screen: currentScreen,
        resetProductForm,
        submitProduct,
        toggleMenu,
    };
}

function normalizeScreen(screen) {
    return ['dashboard', 'products', 'add-product'].includes(screen) ? screen : 'products';
}

function initialProductForm() {
    return {
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
        variants: [emptyVariant()],
    };
}

function emptyVariant() {
    return {
        size: '',
        color: '',
        price: '',
        stock: '',
    };
}

function variantHasData(variant) {
    return [variant.size, variant.color, variant.price, variant.stock].some((value) => String(value).trim() !== '');
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
