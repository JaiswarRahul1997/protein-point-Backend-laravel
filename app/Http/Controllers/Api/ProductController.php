<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $section = trim((string) $request->query('section', ''));
        $limit = min((int) $request->query('limit', 24), 100);
        $search = trim((string) $request->query('q', ''));

        if ($type !== null && $type !== '' && ! array_key_exists($type, Product::TYPES)) {
            return response()->json(['message' => 'Invalid product type.'], 422);
        }

        $products = Product::query()
            ->with('categories:id,name,url_key')
            ->where('status', 'enabled')
            ->where('visibility', '!=', 'not_visible')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('brand', 'ilike', "%{$search}%")
                        ->orWhere('sku', 'ilike', "%{$search}%");
                });
            })
            ->when($section === 'trending', function ($q) {
                $q->orderByDesc('quantity')->orderByDesc('updated_at');
            })
            ->when($section === 'discount', function ($q) {
                $q->where('price', '>', 0)->orderBy('price')->orderByDesc('updated_at');
            })
            ->when(! in_array($section, ['trending', 'discount'], true), function ($q) {
                $q->latest('updated_at');
            })
            ->paginate($limit);

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product) => $this->transform($product))->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'section' => $section !== '' ? $section : null,
            ],
        ]);
    }

    public function show(string $idOrSlug): JsonResponse
    {
        $product = Product::query()
            ->with('categories:id,name,url_key')
            ->where('status', 'enabled')
            ->where('visibility', '!=', 'not_visible')
            ->when(
                ctype_digit($idOrSlug),
                fn ($q) => $q->where('id', (int) $idOrSlug),
                fn ($q) => $q->where('url_key', $idOrSlug)
            )
            ->firstOrFail();

        return response()->json([
            'data' => $this->transform($product),
        ]);
    }

    private function transform(Product $product): array
    {
        $price = (float) $product->price;
        $mrp = round($price * 1.28, 2);
        $offPercent = $mrp > 0 ? (int) round((($mrp - $price) / $mrp) * 100) : 0;

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
            'quantity' => $product->quantity,
            'stock_status' => $product->stock_status,
            'stock_status_label' => $product->stockStatusLabel(),
            'url_key' => $product->url_key,
            'description' => $product->description,
            'sizes' => $product->sizeOptions(),
            'flavors' => $product->flavorOptions(),
            'thumbnail' => Product::publicMediaUrl($product->thumbnail),
            'images' => collect($product->images ?? [])
                ->map(fn ($path) => Product::publicMediaUrl($path))
                ->filter()
                ->values()
                ->all(),
            'categories' => $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url_key' => $category->url_key,
            ])->values(),
            'tag' => $this->tagFor($product),
        ];
    }

    private function tagFor(Product $product): string
    {
        if ($product->created_at && $product->created_at->gt(now()->subDays(14))) {
            return 'New';
        }

        if ($product->quantity > 50) {
            return 'Best Seller';
        }

        return match ($product->type) {
            'bundle' => 'Bundle',
            'configurable' => 'Options',
            'virtual' => 'Digital',
            default => 'Trending',
        };
    }
}
