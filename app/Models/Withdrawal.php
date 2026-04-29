<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'bank_account_id',
        'amount',
        'fee_amount',
        'net_amount',
        'status',
        'note',
        'approved_at',
        'rejected_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(MerchantBankAccount::class, 'bank_account_id');
    }
}
