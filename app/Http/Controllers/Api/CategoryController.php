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
            ->roots()
            ->where('status', true)
            ->withCount([
                'products as products_count' => fn ($q) => $q
                    ->where('status', 'enabled')
                    ->where('visibility', '!=', 'not_visible'),
            ])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url_key' => $category->url_key,
                'products_count' => $category->products_count,
                'count_label' => $category->products_count.' '.Str::plural('item', $category->products_count),
            ]);

        return response()->json([
            'data' => $categories->values(),
        ]);
    }
}
