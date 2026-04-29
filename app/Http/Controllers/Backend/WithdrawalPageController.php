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
                'endpoint' => route('api.admin.wallet.show'),
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
                'endpoint' => route('api.admin.withdrawals.index'),
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
                'endpoint' => route('api.admin.deposits.index'),
            ],
        ]);
    }
}
