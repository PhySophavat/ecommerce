<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const PAYMENT_METHODS = ['cash', 'aba_qr', 'acleda', 'wing', 'card'];

    public const PAYMENT_TYPES = ['cash', 'manual_transfer', 'gateway'];

    public const PAYMENT_STATUSES = ['unpaid', 'pending', 'submitted', 'approved', 'auto_failed', 'rejected', 'failed'];

    public const ORDER_STATUSES = ['pending', 'pending_payment', 'payment_submitted', 'processing', 'completed', 'cancelled'];

    public const LEGACY_ORDER_STATUSES = ['pending', 'paid', 'processing', 'completed', 'shipped', 'delivered', 'cancelled', 'payment_failed', 'failed', 'refunded'];

    protected $fillable = [
        'customer_id',
        'number',
        'order_code',
        'status',
        'order_status',
        'payment_method',
        'payment_type',
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

    public function payments(): HasMany
    {
        return $this->hasMany(GatewayPayment::class);
    }

    public function merchantPayments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function shouldApplyPlatformFeeForStage(string $stage): bool
    {
        return match ($stage) {
            'payment_success' => in_array($this->status, ['paid', 'processing', 'payment_success'], true)
                || in_array($this->payment_status, ['paid', 'approved'], true),
            'order_completed' => in_array($this->status, ['completed', 'delivered', 'order_completed'], true),
            default => false,
        };
    }
}
