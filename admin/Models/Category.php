<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'url_key',
        'description',
        'image',
        'brand_logo',
        'is_brand',
        'status',
        'position',
    ];

    public function imageUrl(): ?string
    {
        return $this->mediaPublicUrl($this->image);
    }

    public function adminImageUrl(): ?string
    {
        return $this->mediaAdminUrl($this->image);
    }

    public function brandLogoUrl(): ?string
    {
        return $this->mediaPublicUrl($this->brand_logo);
    }

    public function adminBrandLogoUrl(): ?string
    {
        return $this->mediaAdminUrl($this->brand_logo);
    }

    private function mediaPublicUrl(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';

        if ($path === '') {
            return null;
        }

        return Product::publicMediaUrl($path);
    }

    private function mediaAdminUrl(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';

        if ($path === '') {
            return null;
        }

        return Product::mediaUrl($path);
    }

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'is_brand' => 'boolean',
            'position' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeShop(Builder $query): Builder
    {
        return $query->where('is_brand', false);
    }

    public function scopeBrands(Builder $query): Builder
    {
        return $query->where('is_brand', true);
    }

    public function letter(): string
    {
        $first = strtoupper(substr(ltrim((string) $this->name), 0, 1));

        return ctype_alpha($first) ? $first : '#';
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function descendantIds(): Collection
    {
        $ids = collect();

        $this->loadMissing('children');

        foreach ($this->children as $child) {
            $ids->push($child->id);
            $ids = $ids->merge($child->descendantIds());
        }

        return $ids;
    }
}
