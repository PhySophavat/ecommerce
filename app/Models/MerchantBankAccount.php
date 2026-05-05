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
        'account_holder_name',
        'account_number',
        'phone_number',
        'currency',
        'account_type',
        'qr_image_path',
        'khqr_code',
        'is_default',
        'status',
        'reject_reason',
        'approved_at',
        'rejected_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disabled_at' => 'datetime',
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

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
