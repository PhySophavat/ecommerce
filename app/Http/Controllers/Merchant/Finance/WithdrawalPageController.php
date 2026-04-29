<?php

namespace App\Http\Controllers\Merchant\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class WithdrawalPageController extends Controller
{
    public function wallet(): View
    {
        return $this->page('wallet', 'Merchant | Wallet');
    }

    public function deposit(): View
    {
        return $this->page('deposit', 'Merchant | Deposit');
    }

    public function bankAccounts(): View
    {
        return $this->page('bank-accounts', 'Merchant | Bank Accounts');
    }

    public function withdraw(): View
    {
        return $this->page('withdraw', 'Merchant | Withdraw');
    }

    public function history(): View
    {
        return $this->page('transactions', 'Merchant | Transaction History');
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
