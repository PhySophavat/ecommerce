<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class GatewayPayment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'submitted', 'approved', 'auto_failed', 'rejected', 'failed'];

    protected $table = 'payments';

    protected $fillable = [
        'order_id',
        'payment_method',
        'provider',
        'transaction_id',
        'gateway_reference',
        'transaction_ref',
        'transaction_reference',
        'screenshot',
        'screenshot_path',
        'admin_note',
        'reject_reason',
        'verified_by',
        'verified_at',
        'auto_check_status',
        'auto_check_score',
        'auto_check_result',
        'ocr_text',
        'auto_checked_at',
        'approved_at',
        'amount',
        'currency',
        'status',
        'paid_at',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
            'auto_check_score' => 'integer',
            'auto_check_result' => 'array',
            'auto_checked_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
