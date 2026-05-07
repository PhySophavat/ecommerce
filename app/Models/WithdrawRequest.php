<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_id',
        'merchant_id',
        'merchant_bank_account_id',
        'amount',
        'currency',
        'status',
        'note',
        'requested_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(MerchantBankAccount::class, 'merchant_bank_account_id');
    }
}
