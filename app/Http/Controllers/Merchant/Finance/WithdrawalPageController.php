<?php

namespace App\Http\Controllers\Merchant\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WithdrawalPageController extends Controller
{
    public function wallet(): View
    {
        return $this->page('wallet', 'Merchant | QR Codes');
    }

    public function overview(): View
    {
        return view('merchant.finance-overview.index', [
            'title' => 'Merchant | Finance Overview',
            'context' => [
                'app' => 'merchant-finance-overview',
                'screen' => 'finance-overview',
                'endpoint' => route('api.merchant.finance-overview'),
            ],
        ]);
    }

    public function deposit(): View
    {
        return $this->page('deposit', 'Merchant | Deposits');
    }

    public function bankAccounts(): View
    {
        return $this->page('bank-accounts', 'Merchant | Bank Accounts');
    }

    public function withdraw(): View
    {
        return $this->page('withdraw', 'Merchant | Withdrawals');
    }

    public function history(): View
    {
        return $this->page('transactions', 'Merchant | Wallet History');
    }

    private function page(string $screen, string $title): View
    {
        return view('merchant.finance.index', [
            'title' => $title,
            'context' => [
                'app' => 'merchant-withdrawals',
                'screen' => $screen,
            ],
        ]);
    }
}
