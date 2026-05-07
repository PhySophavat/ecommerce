<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'total_balance',
        'available_balance',
        'pending_balance',
        'total_in',
        'total_out',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'total_balance' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_in' => 'decimal:2',
            'total_out' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
