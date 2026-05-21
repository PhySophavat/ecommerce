import axios from 'axios';
import { authScopeForUrl, clearAdminAuth, tokenForScope } from './shared/auth';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.axios.interceptors.request.use((config) => {
    const url = String(config.url || '');
    const scope = authScopeForUrl(url);
    const authToken = tokenForScope(scope);

    config.headers = config.headers ?? {};

    if (authToken) {
        config.headers.Authorization = `Bearer ${authToken}`;
    } else if (config.headers.Authorization) {
        delete config.headers.Authorization;
    }

    return config;
});

window.axios.interceptors.response.use((response) => {
    const url = String(response.config?.url || '');

    if (url === '/auth/logout') {
        clearAdminAuth();
    }

    return response;
});
