<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'bank_name',
        'account_name',
        'account_number',
        'account_type',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'bank_account_id');
    }

    public function maskedAccountNumber(): string
    {
        $visible = substr($this->account_number, -4);

        return '****'.$visible;
    }
}
