<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'type',
        'name',
        'slug',
        'sku',
        'tagline',
        'description',
        'price',
        'compare_at_price',
        'inventory',
        'status',
        'admin_note',
        'is_featured',
        'theme',
        'rating',
        'reviews_count',
        'merchant_id',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'rating' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the merchant who created this product
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get the admin who approved this product
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if product is approved and visible in frontend
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if product is pending review
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if product is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Scope to filter only approved products (for frontend)
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter products by merchant
     */
    public function scopeForMerchant($query, int $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    /**
     * Scope to filter pending products
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
