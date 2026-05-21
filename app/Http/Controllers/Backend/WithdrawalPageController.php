<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WithdrawalPageController extends Controller
{
    public function wallet(): View
    {
        return view('backend.wallet.index', [
            'title' => 'Admin | Wallet',
            'context' => [
                'app' => 'backend-wallet',
                'screen' => 'wallet',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.wallet.show'),
            ],
        ]);
    }

    public function qrCodes(): View
    {
        return view('backend.qr-codes.index', [
            'title' => 'Admin | QR Codes',
            'context' => [
                'app' => 'backend-qr-codes',
                'screen' => 'qr-codes',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.qr-codes.index'),
            ],
        ]);
    }

    public function financeOverview(): View
    {
        return view('backend.finance-overview.index', [
            'title' => 'Admin | Finance Overview',
            'context' => [
                'app' => 'backend-finance-overview',
                'screen' => 'finance-overview',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.finance-overview'),
            ],
        ]);
    }

    public function paymentRecords(): View
    {
        return view('backend.payment-records.index', [
            'title' => 'Admin | Payment Records',
            'context' => [
                'app' => 'backend-payment-records',
                'screen' => 'payment-records',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.payments.index'),
            ],
        ]);
    }

    public function paymentMethods(): View
    {
        return view('backend.payment-methods.index', [
            'title' => 'Admin | Payment Methods',
            'context' => [
                'app' => 'backend-payment-methods',
                'screen' => 'payment-methods',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.payment-methods.index'),
            ],
        ]);
    }

    public function paymentFees(): View
    {
        return view('backend.payment-fees.index', [
            'title' => 'Admin | Platform Fee',
            'context' => [
                'app' => 'backend-payment-fees',
                'screen' => 'payment-fees',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.payment-fees.index'),
            ],
        ]);
    }

    public function merchantBalance(): View
    {
        return view('backend.merchant-balance.index', [
            'title' => 'Admin | Merchant Balance',
            'context' => [
                'app' => 'backend-merchant-balance',
                'screen' => 'merchant-balance',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.merchant-balance.index'),
            ],
        ]);
    }

    public function withdrawals(): View
    {
        return view('backend.withdrawals.index', [
            'title' => 'Admin | Withdrawals',
            'context' => [
                'app' => 'backend-withdrawals',
                'screen' => 'withdrawals',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.withdrawals.index'),
            ],
        ]);
    }

    public function bankAccounts(): View
    {
        return view('backend.bank-accounts.index', [
            'title' => 'Admin | Bank Accounts',
            'context' => [
                'app' => 'backend-bank-accounts',
                'screen' => 'bank-accounts',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.bank-accounts.index'),
            ],
        ]);
    }

    public function deposits(): View
    {
        return view('backend.deposits.index', [
            'title' => 'Admin | Deposits',
            'context' => [
                'app' => 'backend-deposits',
                'screen' => 'deposits',
                'role_scope' => 'admin',
                'endpoint' => route('api.admin.deposits.index'),
            ],
        ]);
    }
}
