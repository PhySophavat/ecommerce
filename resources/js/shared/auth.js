const CUSTOMER_TOKEN_KEY = 'customer_token';
const CUSTOMER_USER_KEY = 'customer_user';
const ADMIN_TOKEN_KEY = 'admin_token';
const ADMIN_USER_KEY = 'admin_user';

function parseJson(key) {
    try {
        const raw = window.localStorage.getItem(key);
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function storeJson(key, value) {
    if (value === null || value === undefined) {
        window.localStorage.removeItem(key);
        return;
    }

    window.localStorage.setItem(key, JSON.stringify(value));
}

export function getCustomerToken() {
    return window.localStorage.getItem(CUSTOMER_TOKEN_KEY) || '';
}

export function getAdminToken() {
    return window.localStorage.getItem(ADMIN_TOKEN_KEY) || '';
}

export function getCustomerUser() {
    return parseJson(CUSTOMER_USER_KEY);
}

export function getAdminUser() {
    return parseJson(ADMIN_USER_KEY);
}

export function setCustomerAuth(token, user) {
    if (token) {
        window.localStorage.setItem(CUSTOMER_TOKEN_KEY, token);
    }

    storeJson(CUSTOMER_USER_KEY, user ?? null);
}

export function setAdminAuth(token, user) {
    if (token) {
        window.localStorage.setItem(ADMIN_TOKEN_KEY, token);
    }

    storeJson(ADMIN_USER_KEY, user ?? null);
}

export function clearCustomerAuth() {
    window.localStorage.removeItem(CUSTOMER_TOKEN_KEY);
    window.localStorage.removeItem(CUSTOMER_USER_KEY);
}

export function clearAdminAuth() {
    window.localStorage.removeItem(ADMIN_TOKEN_KEY);
    window.localStorage.removeItem(ADMIN_USER_KEY);
}

export function isAdminRole(role) {
    return ['admin', 'super_admin'].includes(String(role || '').toLowerCase());
}

export function isCustomerAuthenticated() {
    return Boolean(getCustomerToken());
}

export function isAdminAuthenticated() {
    return Boolean(getAdminToken()) && isAdminRole(getAdminUser()?.role);
}

export function authScopeForUrl(url = '') {
    if (url.startsWith('/api/admin')) {
        return 'admin';
    }

    if (url.startsWith('/api/frontend') || url.startsWith('/api/orders/') || url.startsWith('/api/payments/')) {
        return 'customer';
    }

    return null;
}

export function tokenForScope(scope) {
    return scope === 'admin'
        ? getAdminToken()
        : scope === 'customer'
            ? getCustomerToken()
            : '';
}
