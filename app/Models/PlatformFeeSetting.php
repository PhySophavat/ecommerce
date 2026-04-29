<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformFeeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'fee_type',
        'fee_value',
        'apply_stage',
        'deduct_from',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'fee_value' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return self::query()->latest('id')->first() ?? self::make([
            'is_enabled' => true,
            'fee_type' => 'percentage',
            'fee_value' => 0,
            'apply_stage' => 'payment_success',
            'deduct_from' => 'merchant_balance',
        ]);
    }
}
