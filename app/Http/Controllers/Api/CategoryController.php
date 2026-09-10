<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->shop()
            ->where('status', true)
            ->withCount([
                'products as products_count' => fn ($q) => $q
                    ->where('status', 'enabled')
                    ->where('visibility', '!=', 'not_visible'),
            ])
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'url_key' => $category->url_key,
                'image' => $category->imageUrl(),
                'brand_logo' => $category->brandLogoUrl(),
                'products_count' => $category->products_count,
                'count_label' => $category->products_count.' '.Str::plural('item', $category->products_count),
            ]);

        return response()->json([
            'data' => $categories->values(),
        ]);
    }
}
