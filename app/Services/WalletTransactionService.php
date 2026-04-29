<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\WalletTransaction;

class WalletTransactionService
{
    public function record(
        Merchant $merchant,
        string $type,
        float $amount,
        string $direction,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
    ): WalletTransaction {
        return WalletTransaction::query()->create([
            'merchant_id' => $merchant->getKey(),
            'type' => $type,
            'amount' => round($amount, 2),
            'direction' => $direction,
            'balance_after' => round((float) $merchant->balance_total, 2),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);
    }
}
