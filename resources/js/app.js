import './bootstrap';
import { createApp } from 'vue';

import UserManagementApp from './backend/UserManagementApp.vue';
import UserDirectoryApp from './frontend/UserDirectoryApp.vue';

const rootComponent = window.__APP_CONTEXT__?.app === 'backend-users'
    ? UserManagementApp
    : UserDirectoryApp;

createApp(rootComponent).mount('#app');
