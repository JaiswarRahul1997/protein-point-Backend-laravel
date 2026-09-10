<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Category;
use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        $brands = Category::query()
            ->brands()
            ->where('status', true)
            ->withCount([
                'products as products_count' => fn ($q) => $q
                    ->where('status', 'enabled')
                    ->where('visibility', '!=', 'not_visible'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $brand) => $this->transform($brand));

        $letters = $brands
            ->pluck('letter')
            ->unique()
            ->values();

        return response()->json([
            'data' => $brands->values(),
            'meta' => [
                'letters' => $letters,
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $brand = Category::query()
            ->brands()
            ->where('status', true)
            ->when(
                ctype_digit($slug),
                fn ($q) => $q->where('id', (int) $slug),
                fn ($q) => $q->where('url_key', $slug)
            )
            ->firstOrFail();

        $limit = min((int) $request->query('limit', 100), 100);
        $type = trim((string) $request->query('type', ''));
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $products = Product::query()
            ->with(['categories:id,name,url_key,is_brand', 'attributeOptions.attribute'])
            ->where('status', 'enabled')
            ->where('visibility', '!=', 'not_visible')
            ->where(function ($query) use ($brand) {
                $query->whereHas('categories', fn ($q) => $q->where('categories.id', $brand->id))
                    ->orWhere('brand', 'ilike', $brand->name);
            })
            ->when($type !== '' && array_key_exists($type, Product::TYPES), fn ($q) => $q->where('type', $type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%");
                });
            })
            ->when($category !== '', function ($q) use ($category) {
                $q->whereHas('categories', function ($inner) use ($category) {
                    $inner->shop()->where(function ($shop) use ($category) {
                        $shop->where('categories.id', $category)
                            ->orWhere('categories.url_key', $category);
                    });
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $categories = $products
            ->flatMap(fn (Product $product) => $product->categories->where('is_brand', false))
            ->unique('id')
            ->values()
            ->map(fn (Category $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'url_key' => $item->url_key,
            ]);

        return response()->json([
            'data' => array_merge($this->transform($brand), [
                'description' => $brand->description,
                'products' => $products->map(fn (Product $product) => $this->transformProduct($product))->values(),
                'filter_categories' => $categories,
            ]),
        ]);
    }

    private function transform(Category $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'url_key' => $brand->url_key,
            'letter' => $brand->letter(),
            'logo' => $brand->brandLogoUrl() ?: $brand->imageUrl(),
            'description' => $brand->description,
            'products_count' => (int) ($brand->products_count ?? $brand->products()->count()),
        ];
    }

    private function transformProduct(Product $product): array
    {
        $price = (float) $product->price;
        $mrp = round($price * 1.28, 2);
        $offPercent = $mrp > 0 ? (int) round((($mrp - $price) / $mrp) * 100) : 0;
        $attributes = $product->frontendAttributes();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => $product->brand ?: 'Protein Point',
            'type' => $product->type,
            'type_label' => $product->typeLabel(),
            'price' => $price,
            'price_formatted' => '₹'.number_format($price, 0),
            'mrp' => $mrp,
            'mrp_formatted' => '₹'.number_format($mrp, 0),
            'discount_percent' => $offPercent,
            'discount_label' => $offPercent > 0 ? $offPercent.'% OFF' : null,
            'url_key' => $product->url_key,
            'attributes' => $attributes,
            'thumbnail' => Product::publicMediaUrl($product->thumbnail),
            'categories' => $product->categories
                ->where('is_brand', false)
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'url_key' => $category->url_key,
                ])
                ->values(),
            'has_options' => $attributes !== [] || in_array($product->type, ['configurable', 'bundle', 'grouped'], true),
            'tag' => $product->type === 'configurable' ? 'Options' : ($product->type === 'bundle' ? 'Bundle' : 'Trending'),
        ];
    }
}
