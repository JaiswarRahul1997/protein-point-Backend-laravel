<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductAttributeOption extends Model
{
    public const STATUSES = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'attribute_id',
        'label',
        'value',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_attribute_option',
            'attribute_option_id',
            'product_id'
        );
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', 'enabled');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public static function makeValue(string $label, ?string $value = null): string
    {
        $base = Str::slug($value ?: $label, '_');

        return $base !== '' ? $base : 'option';
    }
}
