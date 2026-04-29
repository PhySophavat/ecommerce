<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'full_address',
        'province_city',
        'district',
        'commune',
        'google_map_link',
        'delivery_area',
    ];

    /**
     * Get the merchant that owns the location.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}