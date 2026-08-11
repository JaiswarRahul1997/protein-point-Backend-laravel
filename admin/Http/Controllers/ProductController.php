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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function csvTemplate(): StreamedResponse
    {
        $filename = 'products-template.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->csvColumns());
            fputcsv($handle, [
                'Whey Protein Isolate',
                'WHEY-ISO-001',
                'simple',
                'Default',
                'in_stock',
                '2499',
                '100',
                'catalog_search',
                'enabled',
                'whey-protein-isolate',
                'Protein Point',
                '500g,1kg,2kg',
                'Chocolate,Vanilla,Strawberry',
                'High-quality whey isolate for lean muscle.',
                '',
            ]);
            fclose($handle);
        }, $filename, $headers);
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $products = Product::query()
            ->with('categories:id,url_key')
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $this->csvColumns());

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku,
                    $product->type,
                    $product->attribute_set,
                    $product->stock_status,
                    $product->price,
                    $product->quantity,
                    $product->visibility,
                    $product->status,
                    $product->url_key,
                    $product->brand ?? '',
                    implode(',', $product->sizes ?? []),
                    implode(',', $product->flavors ?? []),
                    $product->description ?? '',
                    $product->categories->pluck('url_key')->filter()->implode('|'),
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value instanceof UploadedFile) {
                        $fail('Please upload a valid CSV file.');

                        return;
                    }

                    $extension = strtolower((string) $value->getClientOriginalExtension());
                    $mime = strtolower((string) ($value->getMimeType() ?: ''));
                    $allowedMimes = [
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/octet-stream',
                    ];

                    if ($extension !== 'csv' && ! in_array($mime, $allowedMimes, true)) {
                        $fail('Please upload a .csv file.');
                    }
                },
            ],
        ]);

        $file = $request->file('csv_file');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded CSV file.']);
        }

        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Could not open the uploaded CSV file.']);
        }

        $headerRow = fgetcsv($handle);

        if (! is_array($headerRow) || $headerRow === []) {
            fclose($handle);

            return back()->withErrors(['csv_file' => 'The CSV file is empty or invalid.']);
        }

        $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerRow[0]);
        $headers = array_map(fn ($value) => Str::snake(trim((string) $value)), $headerRow);

        foreach (['name', 'sku'] as $column) {
            if (! in_array($column, $headers, true)) {
                fclose($handle);

                return back()->withErrors([
                    'csv_file' => 'CSV must include "name" and "sku" columns. Download the template for the full format.',
                ]);
            }
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($this->csvRowIsEmpty($data)) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? trim((string) $data[$index]) : '';
            }

            $name = $row['name'] ?? '';
            $sku = $row['sku'] ?? '';

            if ($name === '' || $sku === '') {
                $errors[] = "Line {$line}: name and sku are required.";
                continue;
            }

            $type = strtolower($row['type'] ?? 'simple');
            if (! array_key_exists($type, Product::TYPES)) {
                $type = 'simple';
            }

            $stockStatus = strtolower($row['stock_status'] ?? 'in_stock');
            if (! array_key_exists($stockStatus, Product::STOCK_STATUSES)) {
                $stockStatus = 'in_stock';
            }

            $visibility = strtolower($row['visibility'] ?? 'catalog_search');
            if (! array_key_exists($visibility, Product::VISIBILITIES)) {
                $visibility = 'catalog_search';
            }

            $status = strtolower($row['status'] ?? 'enabled');
            if (! array_key_exists($status, Product::STATUSES)) {
                $status = 'enabled';
            }

            $urlKey = $row['url_key'] !== '' ? Str::slug($row['url_key']) : Str::slug($name);
            $urlKey = $this->uniqueUrlKey($urlKey, $sku);

            $payload = [
                'name' => $name,
                'sku' => $sku,
                'type' => $type,
                'attribute_set' => $row['attribute_set'] !== '' ? $row['attribute_set'] : 'Default',
                'stock_status' => $stockStatus,
                'price' => is_numeric($row['price'] ?? null) ? (float) $row['price'] : 0,
                'quantity' => is_numeric($row['quantity'] ?? null) ? (int) $row['quantity'] : 0,
                'visibility' => $visibility,
                'status' => $status,
                'url_key' => $urlKey,
                'brand' => $row['brand'] !== '' ? $row['brand'] : null,
                'sizes' => Product::parseOptionList($row['sizes'] ?? '', []),
                'flavors' => Product::parseOptionList($row['flavors'] ?? '', []),
                'description' => $row['description'] !== '' ? $row['description'] : null,
            ];

            if ($payload['sizes'] === []) {
                $payload['sizes'] = null;
            }
            if ($payload['flavors'] === []) {
                $payload['flavors'] = null;
            }

            try {
                $product = Product::query()->where('sku', $sku)->first();

                if ($product) {
                    if (
                        $payload['url_key'] !== $product->url_key
                        && Product::query()->where('url_key', $payload['url_key'])->where('id', '!=', $product->id)->exists()
                    ) {
                        $payload['url_key'] = $product->url_key;
                    }

                    $product->update($payload);
                    $updated++;
                } else {
                    $product = Product::create($payload);
                    $created++;
                }

                $categoryKeys = preg_split('/\s*[|,;]\s*/', (string) ($row['categories'] ?? '')) ?: [];
                $categoryIds = Category::query()
                    ->whereIn('url_key', array_filter(array_map('trim', $categoryKeys)))
                    ->pluck('id')
                    ->all();

                if ($categoryIds !== []) {
                    $product->categories()->sync($categoryIds);
                }
            } catch (\Throwable $e) {
                $errors[] = "Line {$line}: ".$e->getMessage();
            }
        }

        fclose($handle);

        if ($created === 0 && $updated === 0 && $errors !== []) {
            return back()->withErrors(['csv_file' => implode(' ', array_slice($errors, 0, 5))]);
        }

        $message = "CSV import complete: {$created} created, {$updated} updated.";
        if ($errors !== []) {
            $message .= ' Some rows had issues: '.implode(' ', array_slice($errors, 0, 3));
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $message);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $urlKey = $request->input('url_key') ?: Str::slug($request->input('name', ''));

        $request->merge(['url_key' => $urlKey]);

        $data = $request->validate([
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
            'sizes' => ['nullable', 'string', 'max:1000'],
            'flavors' => ['nullable', 'string', 'max:1000'],
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

        $data['sizes'] = Product::parseOptionList($data['sizes'] ?? null, []);
        $data['flavors'] = Product::parseOptionList($data['flavors'] ?? null, []);

        if ($data['sizes'] === []) {
            $data['sizes'] = null;
        }
        if ($data['flavors'] === []) {
            $data['flavors'] = null;
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function csvColumns(): array
    {
        return [
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
            'description',
            'categories',
        ];
    }

    /**
     * @param  array<int, string|null>  $data
     */
    private function csvRowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function uniqueUrlKey(string $urlKey, string $sku): string
    {
        $base = $urlKey !== '' ? $urlKey : Str::slug($sku);
        $candidate = $base;
        $i = 1;

        while (
            Product::query()
                ->where('url_key', $candidate)
                ->where('sku', '!=', $sku)
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
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
