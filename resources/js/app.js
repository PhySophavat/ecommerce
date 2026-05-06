import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';

import UserManagementApp from './backend/UserManagementApp.vue';
import MerchantRegistrationApp from './backend/merchant/MerchantRegistrationApp.vue';
import MerchantManagerPage from './backend/merchant/MerchantManagerPage.vue';
import MerchantStatusApp from './backend/merchant/MerchantStatusApp.vue';
import AdminDeposits from './backend/deposits/AdminDeposits.vue';
import BankAccountsPage from './backend/bank-accounts/BankAccountsPage.vue';
import MerchantBalancePage from './backend/merchant-balance/MerchantBalancePage.vue';
import AdminPaymentRecords from './backend/payments/AdminPaymentRecords.vue';
import AdminPaymentMethods from './backend/payments/AdminPaymentMethods.vue';
import AdminWallet from './backend/wallet/AdminWallet.vue';
import AdminWithdrawals from './backend/withdrawals/AdminWithdrawals.vue';
import MerchantFinanceApp from './backend/merchant/MerchantFinanceApp.vue';
import OrdersDashboard from './backend/orders/OrdersDashboard.vue';
import PlatformFeeSettingsPage from './backend/settings/PlatformFeeSettings.vue';
import ProductManagerPage from './backend/products/ProductManagerPage.vue';
import SlideManagerPage from './backend/slides/SlideManagerPage.vue';
import StorefrontApp from './frontend/StorefrontApp.vue';
import storefrontRouter from './frontend/router';

const appMap = {
    'backend-products': ProductManagerPage,
    'backend-slides': SlideManagerPage,
    'backend-merchants': MerchantManagerPage,
    'merchant-status': MerchantStatusApp,
    'backend-deposits': AdminDeposits,
    'backend-bank-accounts': BankAccountsPage,
    'backend-merchant-balance': MerchantBalancePage,
    'backend-payment-records': AdminPaymentRecords,
    'backend-payment-methods': AdminPaymentMethods,
    'backend-wallet': AdminWallet,
    'backend-withdrawals': AdminWithdrawals,
    'backend-merchant-register': MerchantRegistrationApp,
    'backend-platform-fee-settings': PlatformFeeSettingsPage,
    'backend-users': UserManagementApp,
    'merchant-withdrawals': MerchantFinanceApp,
    'backend-orders': OrdersDashboard,
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

    const app = createApp(rootComponent);

    if (appName === 'frontend') {
        app.use(createPinia());
        app.use(storefrontRouter);
    }

    app.mount(mountTarget);
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
