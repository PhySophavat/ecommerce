import './bootstrap';
import { createApp } from 'vue';

import UserManagementApp from './backend/UserManagementApp.vue';
import MerchantRegistrationApp from './backend/merchant/MerchantRegistrationApp.vue';
import MerchantManagerPage from './backend/merchant/MerchantManagerPage.vue';
import ProductManagerPage from './backend/products/ProductManagerPage.vue';
import SlideManagerPage from './backend/slides/SlideManagerPage.vue';
import StorefrontApp from './frontend/StorefrontApp.vue';

const appMap = {
    'backend-products': ProductManagerPage,
    'backend-slides': SlideManagerPage,
    'backend-merchants': MerchantManagerPage,
    'backend-merchant-register': MerchantRegistrationApp,
    'backend-users': UserManagementApp,
    frontend: StorefrontApp,
};

const appName = window.__APP_CONTEXT__?.app ?? 'frontend';
const mountTarget = document.getElementById('app');

try {
    if (!mountTarget) {
        throw new Error('Missing #app mount target.');
    }

    const rootComponent = appMap[appName];

    if (!rootComponent) {
        throw new Error(`Unknown app context: ${appName}`);
    }

    createApp(rootComponent).mount(mountTarget);
} catch (error) {
    console.error('Vue mount failed:', error);

    if (mountTarget) {
        mountTarget.innerHTML = `
            <div style="padding:24px;font-family:sans-serif;color:#b91c1c;background:#fff1f2;border:1px solid #fecdd3;border-radius:16px;margin:24px;">
                <strong>Frontend error:</strong> ${String(error?.message ?? error)}
            </div>
        `;
    }
}
