<?php

namespace Admin\Http\Controllers;

use Admin\Models\Category;
use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        $brands = Category::query()
            ->brands()
            ->withCount('products')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin::brands.index', [
            'brands' => $brands,
        ]);
    }

    public function create(): View
    {
        return view('admin::brands.form', [
            'brand' => new Category([
                'status' => true,
                'position' => 0,
                'is_brand' => true,
            ]),
            'products' => $this->productOptions(),
            'selectedProductIds' => [],
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $productIds = $data['products'] ?? [];
        unset($data['products'], $data['brand_logo_file'], $data['remove_brand_logo']);

        if ($request->hasFile('brand_logo_file')) {
            $stored = $this->storeUploadedFile($request);
            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['brand_logo_file' => 'Could not store the uploaded brand logo.']);
            }
            $data['brand_logo'] = $stored;
        }

        $data['is_brand'] = true;
        $data['parent_id'] = null;
        $brand = Category::create($data);
        $brand->products()->sync($productIds);
        $this->stampProductBrandNames($brand, $productIds);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand created successfully.');
    }

    public function edit(Category $brand): View
    {
        abort_unless($brand->is_brand, 404);

        return view('admin::brands.form', [
            'brand' => $brand,
            'products' => $this->productOptions(),
            'selectedProductIds' => $brand->products()->pluck('products.id')->all(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Category $brand): RedirectResponse
    {
        abort_unless($brand->is_brand, 404);

        $data = $this->validated($request, $brand);
        $productIds = $data['products'] ?? [];
        unset($data['products'], $data['brand_logo_file'], $data['remove_brand_logo']);

        if ($request->hasFile('brand_logo_file')) {
            $stored = $this->storeUploadedFile($request);
            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['brand_logo_file' => 'Could not store the uploaded brand logo.']);
            }
            $this->deleteStoredFile($brand->brand_logo);
            $data['brand_logo'] = $stored;
        } elseif ($request->boolean('remove_brand_logo')) {
            $this->deleteStoredFile($brand->brand_logo);
            $data['brand_logo'] = null;
        }

        $data['is_brand'] = true;
        $data['parent_id'] = null;
        $brand->update($data);
        $brand->products()->sync($productIds);
        $this->stampProductBrandNames($brand, $productIds);

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand updated successfully.');
    }

    public function toggleStatus(Category $brand): RedirectResponse
    {
        abort_unless($brand->is_brand, 404);

        $brand->update(['status' => ! $brand->status]);
        $label = $brand->status ? 'shown on' : 'hidden from';

        return redirect()
            ->route('admin.brands.index')
            ->with('success', "{$brand->name} is now {$label} the storefront.");
    }

    public function destroy(Category $brand): RedirectResponse
    {
        abort_unless($brand->is_brand, 404);

        $this->deleteStoredFile($brand->brand_logo);
        $brand->products()->detach();
        $brand->delete();

        return redirect()
            ->route('admin.brands.index')
            ->with('success', 'Brand deleted successfully.');
    }

    private function validated(Request $request, ?Category $brand = null): array
    {
        $urlKey = $request->input('url_key') ?: Str::slug($request->input('name', ''));
        $request->merge(['url_key' => $urlKey]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'url_key')->ignore($brand?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],
            'brand_logo_file' => [
                'nullable',
                'file',
                'image:allow_svg',
                'mimes:jpeg,jpg,png,webp,gif,svg',
                'max:5120',
            ],
            'remove_brand_logo' => ['nullable', 'boolean'],
        ]);
    }

    private function productOptions()
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'brand']);
    }

    /**
     * @param  array<int, int>  $productIds
     */
    private function stampProductBrandNames(Category $brand, array $productIds): void
    {
        if ($productIds === []) {
            return;
        }

        Product::query()
            ->whereIn('id', $productIds)
            ->where(function ($query) {
                $query->whereNull('brand')->orWhere('brand', '');
            })
            ->update(['brand' => $brand->name]);
    }

    private function storeUploadedFile(Request $request): ?string
    {
        $file = $request->file('brand_logo_file');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = Str::random(40).'.'.$extension;
        $directory = 'categories/logos';

        try {
            $stored = Storage::disk('public')->putFileAs($directory, $file, $filename);
            if (is_string($stored) && $stored !== '') {
                return $stored;
            }
        } catch (\Throwable $e) {
            Log::warning('Brand logo upload failed', ['message' => $e->getMessage()]);
        }

        return null;
    }

    private function deleteStoredFile(?string $path): void
    {
        $relative = Product::relativeMediaPath($path);
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
