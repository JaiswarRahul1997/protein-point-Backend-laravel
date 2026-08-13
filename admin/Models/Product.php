<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    public const TYPES = [
        'simple' => 'Simple',
        'configurable' => 'Configurable',
        'bundle' => 'Bundle',
        'grouped' => 'Grouped',
        'virtual' => 'Virtual',
    ];

    public const STOCK_STATUSES = [
        'in_stock' => 'In Stock',
        'out_of_stock' => 'Out of Stock',
    ];

    public const VISIBILITIES = [
        'not_visible' => 'Not Visible Individually',
        'catalog' => 'Catalog',
        'search' => 'Search',
        'catalog_search' => 'Catalog, Search',
    ];

    public const STATUSES = [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
    ];

    protected $fillable = [
        'name',
        'sku',
        'type',
        'attribute_set',
        'stock_status',
        'price',
        'quantity',
        'visibility',
        'status',
        'url_key',
        'brand',
        'sizes',
        'flavors',
        'thumbnail',
        'images',
        'videos',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'images' => 'array',
            'videos' => 'array',
            'sizes' => 'array',
            'flavors' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeOption::class,
            'product_attribute_option',
            'product_id',
            'attribute_option_id'
        )->with(['attribute' => fn ($q) => $q->orderBy('sort_order')->orderBy('label')]);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function stockStatusLabel(): string
    {
        return self::STOCK_STATUSES[$this->stock_status] ?? $this->stock_status;
    }

    public function visibilityLabel(): string
    {
        return self::VISIBILITIES[$this->visibility] ?? $this->visibility;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Dynamic Magento-style attributes assigned to this product for storefront dropdowns.
     *
     * @return array<int, array{code: string, label: string, required: bool, options: array<int, array{value: string, label: string}>}>
     */
    public function frontendAttributes(): array
    {
        $options = $this->relationLoaded('attributeOptions')
            ? $this->attributeOptions
            : $this->attributeOptions()->with('attribute')->get();

        return $options
            ->filter(function (ProductAttributeOption $option) {
                $attribute = $option->attribute;

                return $attribute
                    && $attribute->status === 'enabled'
                    && $attribute->is_visible_on_frontend
                    && $option->status === 'enabled'
                    && $attribute->frontend_input === 'select';
            })
            ->groupBy(fn (ProductAttributeOption $option) => $option->attribute_id)
            ->map(function ($grouped) {
                /** @var ProductAttributeOption $first */
                $first = $grouped->first();
                $attribute = $first->attribute;

                $sorted = $grouped->sortBy([
                    ['sort_order', 'asc'],
                    ['label', 'asc'],
                ])->values();

                return [
                    'code' => $attribute->code,
                    'label' => $attribute->label,
                    'required' => (bool) $attribute->is_required,
                    'sort_order' => (int) $attribute->sort_order,
                    'options' => $sorted->map(fn (ProductAttributeOption $option) => [
                        'value' => $option->value,
                        'label' => $option->label,
                    ])->values()->all(),
                ];
            })
            ->filter(fn (array $attribute) => $attribute['options'] !== [])
            ->sortBy('sort_order')
            ->values()
            ->map(function (array $attribute) {
                unset($attribute['sort_order']);

                return $attribute;
            })
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function parseOptionList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/\s*[,|;]\s*/', $raw) ?: [];

        return collect($parts)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function thumbnailUrl(): ?string
    {
        return self::mediaUrl($this->thumbnail);
    }

    /**
     * @return array<int, string>
     */
    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => self::mediaUrl($path))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function videoUrls(): array
    {
        return collect($this->videos ?? [])
            ->map(fn ($path) => self::mediaUrl($path))
            ->filter()
            ->values()
            ->all();
    }

    public static function publicMediaUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $relative = self::relativeMediaPath($path);

            if ($relative !== null && $relative !== '') {
                return url('/storage/'.$relative);
            }

            return $path;
        }

        $relative = self::relativeMediaPath($path);

        if ($relative === null || $relative === '') {
            return null;
        }

        return url('/storage/'.$relative);
    }

    public static function mediaUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $relative = self::relativeMediaPath($path);

            if ($relative !== null && $relative !== '') {
                return self::adminMediaUrl($relative);
            }

            return $path;
        }

        $relative = self::relativeMediaPath($path);

        if ($relative === null || $relative === '') {
            return null;
        }

        return self::adminMediaUrl($relative);
    }

    public static function relativeMediaPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            foreach (['/admin/media/', '/storage/'] as $marker) {
                $position = strpos($path, $marker);

                if ($position !== false) {
                    $relative = ltrim(substr($path, $position + strlen($marker)), '/');

                    return $relative !== '' ? $relative : null;
                }
            }

            return null;
        }

        $normalized = ltrim($path, '/');

        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        if (str_starts_with($normalized, 'admin/media/')) {
            $normalized = substr($normalized, strlen('admin/media/'));
        }

        return $normalized !== '' ? $normalized : null;
    }

    private static function adminMediaUrl(string $relative): string
    {
        return url('admin/media/'.$relative);
    }
}
