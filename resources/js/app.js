import './bootstrap';
import { createApp } from 'vue';

import ProductManagerPage from './backend/products/ProductManagerPage.vue';
import SlideManagerPage from './backend/slides/SlideManagerPage.vue';
import StorefrontApp from './frontend/StorefrontApp.vue';

const appMap = {
    'backend-products': ProductManagerPage,
    'backend-slides': SlideManagerPage,
    frontend: StorefrontApp,
};

const rootComponent = appMap[window.__APP_CONTEXT__?.app] ?? UserDirectoryApp;
const mountTarget = document.getElementById('app');

try {
    if (!mountTarget) {
        throw new Error('Missing #app mount target.');
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
