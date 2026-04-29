<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'bank_name',
        'account_name',
        'account_number',
        'phone_number',
        'amount',
        'payment_method',
        'khqr_code',
        'payment_proof',
        'status',
        'note',
        'admin_note',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
