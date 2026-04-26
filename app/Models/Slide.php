<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'eyebrow',
        'title',
        'highlight',
        'description',
        'button_text',
        'button_url',
        'badge_text',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function resolvedImagePath(): ?string
    {
        $disk = Storage::disk('public');

        if ($this->image_path && $disk->exists($this->image_path)) {
            return $this->image_path;
        }

        if (!$this->id) {
            return null;
        }

        return collect($disk->files("slides/{$this->id}"))
            ->sort()
            ->first();
    }

    public function resolvedImageUrl(): string
    {
        $path = $this->resolvedImagePath();

        if (!$path) {
            return '';
        }

        return '/storage/'.ltrim(str_replace('\\', '/', $path), '/');
    }

    public function resolvedImageName(): string
    {
        $path = $this->resolvedImagePath();

        return $path ? basename($path) : '';
    }
}
