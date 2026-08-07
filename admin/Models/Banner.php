<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public const SLOTS = [
        'left' => 'Main — Large (Left) · 900×510',
        'center' => 'Side — Top (Right) · 450×251',
        'right' => 'Side — Bottom (Right) · 450×251',
    ];

    public const DIMENSIONS = [
        'left' => ['width' => 900, 'height' => 510],
        'center' => ['width' => 450, 'height' => 251],
        'right' => ['width' => 450, 'height' => 251],
    ];

    public const STATUSES = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'title',
        'subtitle',
        'cta_text',
        'cta_link',
        'image',
        'slot',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', 'enabled');
    }

    public function slotLabel(): string
    {
        return self::SLOTS[$this->slot] ?? $this->slot;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function imageUrl(): ?string
    {
        $path = is_string($this->image) ? trim($this->image) : '';

        if ($path === '') {
            return null;
        }

        return Product::publicMediaUrl($path);
    }

    public function adminImageUrl(): ?string
    {
        $path = is_string($this->image) ? trim($this->image) : '';

        if ($path === '') {
            return null;
        }

        return Product::mediaUrl($path);
    }
}
