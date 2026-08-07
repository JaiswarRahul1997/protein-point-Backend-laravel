<?php

namespace Admin\Http\Controllers;

use Admin\Models\Category;
use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request, ?string $type = null): View
    {
        if ($type !== null && ! array_key_exists($type, Product::TYPES)) {
            abort(404);
        }

        $products = Product::query()
            ->with('categories')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin::products.index', [
            'products' => $products,
            'type' => $type,
            'typeLabel' => $type ? Product::TYPES[$type] : 'All Products',
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->query('type', 'simple');

        if (! array_key_exists($type, Product::TYPES)) {
            $type = 'simple';
        }

        return view('admin::products.form', [
            'product' => new Product([
                'type' => $type,
                'status' => 'enabled',
                'stock_status' => 'in_stock',
                'visibility' => 'catalog_search',
                'attribute_set' => 'Default',
            ]),
            'categories' => $this->categoryOptions(),
            'selectedCategoryIds' => [],
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->storeMedia($request, $data);

        $product = Product::create($data);
        $product->categories()->sync($request->input('categories', []));

        return redirect()
            ->route('admin.products.index', ['type' => $product->type])
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        return view('admin::products.form', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
            'selectedCategoryIds' => $product->categories()->pluck('categories.id')->all(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $data = $this->storeMedia($request, $data, $product);

        $product->update($data);
        $product->categories()->sync($request->input('categories', []));

        return redirect()
            ->route('admin.products.index', ['type' => $product->type])
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $type = $product->type;
        $this->deleteStoredFiles(array_filter([
            $product->thumbnail,
            ...($product->images ?? []),
            ...($product->videos ?? []),
        ]));
        $product->delete();

        return redirect()
            ->route('admin.products.index', ['type' => $type])
            ->with('success', 'Product deleted successfully.');
    }

    public function seedDummy(): RedirectResponse
    {
        (new \Database\Seeders\AdminProductSeeder)->run();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Added 10 dummy products for each product type (50 total).');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $urlKey = $request->input('url_key') ?: Str::slug($request->input('name', ''));

        $request->merge(['url_key' => $urlKey]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product?->id),
            ],
            'type' => ['required', Rule::in(array_keys(Product::TYPES))],
            'attribute_set' => ['required', 'string', 'max:255'],
            'stock_status' => ['required', Rule::in(array_keys(Product::STOCK_STATUSES))],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'visibility' => ['required', Rule::in(array_keys(Product::VISIBILITIES))],
            'status' => ['required', Rule::in(array_keys(Product::STATUSES))],
            'url_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'url_key')->ignore($product?->id),
            ],
            'brand' => ['nullable', 'string', 'max:255'],
            'thumbnail_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'image_files' => ['nullable', 'array'],
            'image_files.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'video_files' => ['nullable', 'array'],
            'video_files.*' => ['file', 'mimes:mp4,webm,mov,avi,mkv', 'max:51200'],
            'existing_images' => ['nullable', 'array'],
            'existing_images.*' => ['string'],
            'existing_videos' => ['nullable', 'array'],
            'existing_videos.*' => ['string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ]);
    }

    private function storeMedia(Request $request, array $data, ?Product $product = null): array
    {
        unset(
            $data['thumbnail_file'],
            $data['image_files'],
            $data['video_files'],
            $data['existing_images'],
            $data['existing_videos']
        );

        $keptImages = array_values(array_filter($request->input('existing_images', [])));
        $keptVideos = array_values(array_filter($request->input('existing_videos', [])));

        if ($product) {
            $removedImages = array_diff($product->images ?? [], $keptImages);
            $removedVideos = array_diff($product->videos ?? [], $keptVideos);
            $this->deleteStoredFiles([...$removedImages, ...$removedVideos]);
        }

        $uploadedImages = $this->storeUploadedFiles(
            $request->file('image_files', []),
            'products/images'
        );
        $uploadedVideos = $this->storeUploadedFiles(
            $request->file('video_files', []),
            'products/videos'
        );

        $data['images'] = array_values(array_unique([...$keptImages, ...$uploadedImages]));
        $data['videos'] = array_values(array_unique([...$keptVideos, ...$uploadedVideos]));

        if ($request->hasFile('thumbnail_file')) {
            if ($product?->thumbnail) {
                $this->deleteStoredFiles([$product->thumbnail]);
            }

            $data['thumbnail'] = $this->storeUploadedFile(
                $request->file('thumbnail_file'),
                'products/thumbnails'
            );
        } elseif ($product) {
            $data['thumbnail'] = $product->thumbnail;

            if ($request->boolean('remove_thumbnail')) {
                $this->deleteStoredFiles([$product->thumbnail]);
                $data['thumbnail'] = $data['images'][0] ?? null;
            }
        } else {
            $data['thumbnail'] = $data['images'][0] ?? null;
        }

        if (empty($data['thumbnail']) && ! empty($data['images'])) {
            $data['thumbnail'] = $data['images'][0];
        }

        return $data;
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     * @return array<int, string>
     */
    private function storeUploadedFiles(array $files, string $directory): array
    {
        $paths = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->storeUploadedFile($file, $directory);
            }
        }

        return $paths;
    }

    private function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function deleteStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }

            $relative = Product::relativeMediaPath($path);

            if ($relative && Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            }
        }
    }

    private function categoryOptions()
    {
        return Category::query()
            ->roots()
            ->with(['children' => fn ($q) => $q->orderBy('position')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->flatMap(function (Category $root) {
                $options = collect([
                    [
                        'id' => $root->id,
                        'label' => $root->name,
                    ],
                ]);

                foreach ($root->children as $child) {
                    $options->push([
                        'id' => $child->id,
                        'label' => $root->name.' › '.$child->name,
                    ]);

                    foreach ($child->children as $grandChild) {
                        $options->push([
                            'id' => $grandChild->id,
                            'label' => $root->name.' › '.$child->name.' › '.$grandChild->name,
                        ]);
                    }
                }

                return $options;
            });
    }
}
