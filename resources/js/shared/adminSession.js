import { clearAdminAuth, getAdminToken, isAdminRole, setAdminAuth } from './auth';

export async function hydrateAdminSession() {
    const currentUser = window.__APP_CONTEXT__?.currentUser ?? null;

    if (!isAdminRole(currentUser?.role)) {
        clearAdminAuth();
        return null;
    }

    if (getAdminToken()) {
        setAdminAuth(getAdminToken(), currentUser);
        return currentUser;
    }

    try {
        const response = await window.axios.get('/api/admin/session');
        const { token, user } = response.data ?? {};

        if (token && user) {
            setAdminAuth(token, user);
            return user;
        }
    } catch {
        clearAdminAuth();
    }

    return null;
}

export async function logoutAdminSession(redirectTo = '/login') {
    try {
        await window.axios.post('/api/admin/logout');
    } catch {
        // Ignore API token revoke failures and continue with web logout.
    }

    clearAdminAuth();

    await window.axios.post('/auth/logout').catch(() => {});
    window.location.href = redirectTo;
}
