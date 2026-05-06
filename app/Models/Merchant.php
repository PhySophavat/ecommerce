<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_name',
        'business_type',
        'business_description',
        'shop_logo',
        'cover_image',
        'id_card_document',
        'verification_status',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'balance_total',
        'available_balance',
        'pending_balance',
        'total_withdrawn',
        'total_deposited',
        'total_platform_fee_paid',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'balance_total' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
            'total_deposited' => 'decimal:2',
            'total_platform_fee_paid' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the merchant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location information for the merchant.
     */
    public function location(): HasOne
    {
        return $this->hasOne(MerchantLocation::class);
    }

    /**
     * Get the admin who approved this merchant.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(MerchantTransaction::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'merchant_id', 'user_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(MerchantBankAccount::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(MerchantDeposit::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if merchant is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    /**
     * Check if merchant is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if merchant is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'Rejected';
    }

    /**
     * Check if merchant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'Suspended';
    }
}
