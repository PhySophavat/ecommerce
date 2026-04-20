import './bootstrap';
import { createApp } from 'vue';

import ProductManagerPage from './backend/products/ProductManagerPage.vue';
import UserDirectoryApp from './frontend/UserDirectoryApp.vue';

const appMap = {
    'backend-products': ProductManagerPage,
    frontend: UserDirectoryApp,
};

const rootComponent = appMap[window.__APP_CONTEXT__?.app] ?? UserDirectoryApp;

createApp(rootComponent).mount('#app');
