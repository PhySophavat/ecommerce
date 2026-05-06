<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const PAYMENT_METHODS = ['cash', 'aba_qr', 'wing', 'card'];

    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'failed', 'refunded'];

    public const ORDER_STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    protected $fillable = [
        'customer_id',
        'number',
        'status',
        'payment_method',
        'payment_status',
        'payment_reference',
        'customer_name',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'notes',
        'payment_notes',
        'subtotal_amount',
        'shipping_amount',
        'total_amount',
        'placed_at',
        'paid_at',
        'platform_fee_processed_at',
        'platform_fee_processed_stage',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'platform_fee_processed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function merchantTransactions(): HasMany
    {
        return $this->hasMany(MerchantTransaction::class);
    }

    public function shouldApplyPlatformFeeForStage(string $stage): bool
    {
        return match ($stage) {
            'payment_success' => in_array($this->status, ['paid', 'payment_success'], true),
            'order_completed' => in_array($this->status, ['completed', 'order_completed'], true),
            default => false,
        };
    }
}
