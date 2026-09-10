<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'is_brand')) {
                $table->boolean('is_brand')->default(false)->after('brand_logo');
                $table->index('is_brand');
            }
        });

        $this->ensureBrandsMenuItem();
        $this->seedBrandsFromProducts();
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'is_brand')) {
                $table->dropIndex(['is_brand']);
                $table->dropColumn('is_brand');
            }
        });
    }

    private function ensureBrandsMenuItem(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        $exists = DB::table('menus')
            ->where(function ($query) {
                $query->where('url', '/brands')
                    ->orWhere('label', 'Brands')
                    ->orWhere('label', 'Brand');
            })
            ->exists();

        if ($exists) {
            DB::table('menus')
                ->where(function ($query) {
                    $query->where('label', 'Brands')->orWhere('label', 'Brand');
                })
                ->where('url', '!=', '/brands')
                ->update([
                    'url' => '/brands',
                    'updated_at' => now(),
                ]);

            return;
        }

        $maxSort = (int) DB::table('menus')->where('location', 'header')->max('sort_order');

        DB::table('menus')->insert([
            'label' => 'Brands',
            'url' => '/brands',
            'location' => 'header',
            'status' => 'enabled',
            'sort_order' => $maxSort + 10,
            'open_in_new_tab' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedBrandsFromProducts(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('categories')) {
            return;
        }

        $names = DB::table('products')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand');

        foreach ($names as $index => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $existing = DB::table('categories')
                ->where('is_brand', true)
                ->where(function ($query) use ($name) {
                    $query->where('name', $name)->orWhere('url_key', Str::slug($name));
                })
                ->first();

            $brandId = $existing?->id;

            if (! $brandId) {
                $urlKey = $this->uniqueUrlKey(Str::slug($name) ?: 'brand');
                $matchingCategory = DB::table('categories')
                    ->where('is_brand', false)
                    ->where('name', $name)
                    ->first();

                $brandId = DB::table('categories')->insertGetId([
                    'parent_id' => null,
                    'name' => $name,
                    'url_key' => $urlKey,
                    'description' => $name.' products from Protein Point.',
                    'image' => null,
                    'brand_logo' => $matchingCategory->brand_logo ?? null,
                    'is_brand' => true,
                    'status' => true,
                    'position' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $productIds = DB::table('products')->where('brand', $name)->pluck('id');
            foreach ($productIds as $productId) {
                $already = DB::table('category_product')
                    ->where('category_id', $brandId)
                    ->where('product_id', $productId)
                    ->exists();

                if (! $already) {
                    DB::table('category_product')->insert([
                        'category_id' => $brandId,
                        'product_id' => $productId,
                    ]);
                }
            }
        }
    }

    private function uniqueUrlKey(string $base): string
    {
        $candidate = $base;
        $i = 1;
        while (DB::table('categories')->where('url_key', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
};
