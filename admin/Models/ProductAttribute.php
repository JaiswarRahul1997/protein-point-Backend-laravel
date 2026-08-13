<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    public const INPUT_TYPES = [
        'select' => 'Dropdown',
    ];

    public const STATUSES = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'code',
        'label',
        'frontend_input',
        'is_required',
        'is_visible_on_frontend',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible_on_frontend' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductAttributeOption::class, 'attribute_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', 'enabled');
    }

    public function scopeVisibleOnFrontend(Builder $query): Builder
    {
        return $query->where('is_visible_on_frontend', true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function inputTypeLabel(): string
    {
        return self::INPUT_TYPES[$this->frontend_input] ?? $this->frontend_input;
    }

    public static function makeCode(string $label, ?string $code = null): string
    {
        $base = Str::slug($code ?: $label, '_');

        return $base !== '' ? $base : 'attribute';
    }
}
