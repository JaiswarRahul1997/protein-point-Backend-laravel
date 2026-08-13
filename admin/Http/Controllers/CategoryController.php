<?php

namespace Admin\Http\Controllers;

use Admin\Models\Category;
use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'id_from' => $request->query('id_from'),
            'id_to' => $request->query('id_to'),
            'name' => $request->query('name'),
            'url_key' => $request->query('url_key'),
            'category_type' => $request->query('category_type'),
            'status' => $request->query('status'),
            'position_from' => $request->query('position_from'),
            'position_to' => $request->query('position_to'),
            'updated_from' => $request->query('updated_from'),
            'updated_to' => $request->query('updated_to'),
        ];

        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));

        if ($hasActiveFilters) {
            $categories = Category::query()
                ->withCount(['products', 'children'])
                ->with('parent:id,name')
                ->tap(fn ($q) => $this->applyCategoryFilters($q, $filters))
                ->orderBy('position')
                ->orderBy('name')
                ->get();

            return view('admin::categories.index', [
                'categories' => $categories,
                'filters' => $filters,
                'hasActiveFilters' => true,
                'filteredMode' => true,
            ]);
        }

        $categories = Category::query()
            ->roots()
            ->with([
                'children' => fn ($q) => $q
                    ->withCount(['products', 'children'])
                    ->with(['children' => fn ($q2) => $q2->withCount('products')->orderBy('position')->orderBy('name')])
                    ->orderBy('position')
                    ->orderBy('name'),
            ])
            ->withCount(['products', 'children'])
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin::categories.index', [
            'categories' => $categories,
            'filters' => $filters,
            'hasActiveFilters' => false,
            'filteredMode' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCategoryFilters($query, array $filters): void
    {
        if (filled($filters['id_from']) && is_numeric($filters['id_from'])) {
            $query->where('id', '>=', (int) $filters['id_from']);
        }

        if (filled($filters['id_to']) && is_numeric($filters['id_to'])) {
            $query->where('id', '<=', (int) $filters['id_to']);
        }

        if (filled($filters['name'])) {
            $query->where('name', 'ilike', '%'.$filters['name'].'%');
        }

        if (filled($filters['url_key'])) {
            $query->where('url_key', 'ilike', '%'.$filters['url_key'].'%');
        }

        if (($filters['category_type'] ?? '') === 'category') {
            $query->whereNull('parent_id');
        } elseif (($filters['category_type'] ?? '') === 'subcategory') {
            $query->whereNotNull('parent_id');
        }

        if ($filters['status'] === '1' || $filters['status'] === '0') {
            $query->where('status', (bool) (int) $filters['status']);
        }

        if (filled($filters['position_from']) && is_numeric($filters['position_from'])) {
            $query->where('position', '>=', (int) $filters['position_from']);
        }

        if (filled($filters['position_to']) && is_numeric($filters['position_to'])) {
            $query->where('position', '<=', (int) $filters['position_to']);
        }

        if (filled($filters['updated_from'])) {
            $query->whereDate('updated_at', '>=', $filters['updated_from']);
        }

        if (filled($filters['updated_to'])) {
            $query->whereDate('updated_at', '<=', $filters['updated_to']);
        }
    }

    public function create(Request $request): View
    {
        $parentId = $request->query('parent_id');

        return view('admin::categories.form', [
            'category' => new Category([
                'status' => true,
                'position' => 0,
                'parent_id' => $parentId,
            ]),
            'parentOptions' => $this->parentOptions(),
            'mode' => 'create',
            'isSubCategory' => filled($parentId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        unset(
            $data['image_file'],
            $data['remove_image'],
            $data['brand_logo_file'],
            $data['remove_brand_logo']
        );

        if ($request->hasFile('image_file')) {
            $stored = $this->storeUploadedFile(
                $request,
                'image_file',
                ['categories', 'products/categories', 'products/images']
            );

            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['image_file' => 'Could not store the uploaded image. Check file permissions and try again.']);
            }

            $data['image'] = $stored;
        }

        if ($request->hasFile('brand_logo_file')) {
            $stored = $this->storeUploadedFile(
                $request,
                'brand_logo_file',
                ['categories/logos', 'categories', 'products/images'],
                ['jpg', 'png', 'webp', 'gif', 'svg']
            );

            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['brand_logo_file' => 'Could not store the uploaded brand logo. Check file permissions and try again.']);
            }

            $data['brand_logo'] = $stored;
        }

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin::categories.form', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
            'mode' => 'edit',
            'isSubCategory' => ! $category->isRoot(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        unset(
            $data['image_file'],
            $data['remove_image'],
            $data['brand_logo_file'],
            $data['remove_brand_logo']
        );

        if ($request->hasFile('image_file')) {
            $stored = $this->storeUploadedFile(
                $request,
                'image_file',
                ['categories', 'products/categories', 'products/images']
            );

            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['image_file' => 'Could not store the uploaded image. Check file permissions and try again.']);
            }

            $this->deleteStoredFile($category->image);
            $data['image'] = $stored;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteStoredFile($category->image);
            $data['image'] = null;
        }

        if ($request->hasFile('brand_logo_file')) {
            $stored = $this->storeUploadedFile(
                $request,
                'brand_logo_file',
                ['categories/logos', 'categories', 'products/images'],
                ['jpg', 'png', 'webp', 'gif', 'svg']
            );

            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['brand_logo_file' => 'Could not store the uploaded brand logo. Check file permissions and try again.']);
            }

            $this->deleteStoredFile($category->brand_logo);
            $data['brand_logo'] = $stored;
        } elseif ($request->boolean('remove_brand_logo')) {
            $this->deleteStoredFile($category->brand_logo);
            $data['brand_logo'] = null;
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->update([
            'status' => ! $category->status,
        ]);

        $label = $category->status ? 'shown on' : 'hidden from';

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "{$category->name} is now {$label} the storefront.");
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'categories-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $categories = Category::query()
            ->with('parent:id,url_key')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($categories) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'name',
                'url_key',
                'parent_url_key',
                'description',
                'status',
                'position',
                'image',
                'brand_logo',
            ]);

            foreach ($categories as $category) {
                fputcsv($handle, [
                    $category->name,
                    $category->url_key,
                    $category->parent?->url_key ?? '',
                    $category->description ?? '',
                    $category->status ? '1' : '0',
                    $category->position ?? 0,
                    $category->imageUrl() ?? '',
                    $category->brandLogoUrl() ?? '',
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
                'max:5120',
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
        $required = ['name'];

        foreach ($required as $column) {
            if (! in_array($column, $headers, true)) {
                fclose($handle);

                return back()->withErrors([
                    'csv_file' => 'CSV must include a "name" column. Optional: url_key, parent_url_key, description, status, position, image, brand_logo.',
                ]);
            }
        }

        $rows = [];
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

            $rows[] = ['line' => $line, 'data' => $row];
        }

        fclose($handle);

        if ($rows === []) {
            return back()->withErrors(['csv_file' => 'No category rows found in the CSV file.']);
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        // Pass 1: upsert categories without assigning parents.
        foreach ($rows as $entry) {
            $row = $entry['data'];
            $line = $entry['line'];
            $name = $row['name'] ?? '';

            if ($name === '') {
                $errors[] = "Line {$line}: name is required.";
                continue;
            }

            $urlKey = ($row['url_key'] ?? '') !== ''
                ? Str::slug($row['url_key'])
                : Str::slug($name);

            if ($urlKey === '') {
                $errors[] = "Line {$line}: could not generate a url_key from name.";
                continue;
            }

            $payload = [
                'name' => $name,
                'url_key' => $urlKey,
                'description' => ($row['description'] ?? '') !== '' ? $row['description'] : null,
                'status' => $this->parseCsvStatus($row['status'] ?? '1'),
                'position' => is_numeric($row['position'] ?? null) ? (int) $row['position'] : 0,
            ];

            $imageValue = $row['image'] ?? $row['category_image'] ?? null;
            $logoValue = $row['brand_logo'] ?? $row['logo'] ?? null;

            if (is_string($imageValue) && trim($imageValue) !== '') {
                $storedImage = $this->resolveCsvMediaPath(
                    trim($imageValue),
                    ['categories', 'products/categories', 'products/images'],
                    $line,
                    'image',
                    $errors
                );

                if ($storedImage !== null) {
                    $payload['image'] = $storedImage;
                }
            }

            if (is_string($logoValue) && trim($logoValue) !== '') {
                $storedLogo = $this->resolveCsvMediaPath(
                    trim($logoValue),
                    ['categories/logos', 'categories', 'products/images'],
                    $line,
                    'brand_logo',
                    $errors
                );

                if ($storedLogo !== null) {
                    $payload['brand_logo'] = $storedLogo;
                }
            }

            $existing = Category::query()->where('url_key', $urlKey)->first();

            if ($existing) {
                if (isset($payload['image']) && $payload['image'] !== $existing->image) {
                    $this->deleteStoredFile($existing->image);
                }
                if (isset($payload['brand_logo']) && $payload['brand_logo'] !== $existing->brand_logo) {
                    $this->deleteStoredFile($existing->brand_logo);
                }

                $existing->update($payload);
                $updated++;
            } else {
                Category::create($payload);
                $created++;
            }
        }

        // Pass 2: assign parents by parent_url_key.
        $byUrlKey = Category::query()->get()->keyBy('url_key');

        foreach ($rows as $entry) {
            $row = $entry['data'];
            $line = $entry['line'];
            $name = $row['name'] ?? '';

            if ($name === '') {
                continue;
            }

            $urlKey = ($row['url_key'] ?? '') !== ''
                ? Str::slug($row['url_key'])
                : Str::slug($name);

            $category = $byUrlKey->get($urlKey);

            if (! $category) {
                continue;
            }

            $parentUrlKey = $row['parent_url_key'] ?? '';

            if ($parentUrlKey === '') {
                if ($category->parent_id !== null) {
                    $category->update(['parent_id' => null]);
                }
                continue;
            }

            $parentUrlKey = Str::slug($parentUrlKey);

            if ($parentUrlKey === $urlKey) {
                $errors[] = "Line {$line}: category cannot be its own parent.";
                continue;
            }

            $parent = $byUrlKey->get($parentUrlKey);

            if (! $parent) {
                $errors[] = "Line {$line}: parent_url_key \"{$row['parent_url_key']}\" not found.";
                continue;
            }

            if ((int) $category->parent_id !== (int) $parent->id) {
                $category->update(['parent_id' => $parent->id]);
            }
        }

        $message = "CSV import complete: {$created} created, {$updated} updated.";

        if ($errors !== []) {
            $message .= ' Some rows had issues: '.implode(' ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' (+'.(count($errors) - 5).' more)';
            }
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $message);
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->loadMissing('children.children');

        $this->deleteCategoryMedia($category);

        foreach ($category->children as $child) {
            $this->deleteCategoryMedia($child);

            foreach ($child->children as $grandChild) {
                $this->deleteCategoryMedia($grandChild);
            }
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function csvRowIsEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseCsvStatus(mixed $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return true;
        }

        return in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true);
    }

    /**
     * Resolve a CSV media cell to a stored relative path.
     * Accepts existing storage paths, /storage/... URLs, or remote image URLs.
     *
     * @param  array<int, string>  $directories
     * @param  array<int, string>  $errors
     */
    private function resolveCsvMediaPath(
        string $value,
        array $directories,
        int $line,
        string $field,
        array &$errors
    ): ?string {
        $relative = Product::relativeMediaPath($value);

        if ($relative && Storage::disk('public')->exists($relative)) {
            return $relative;
        }

        if ($relative && ! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $errors[] = "Line {$line}: {$field} path \"{$value}\" was not found in storage.";

            return null;
        }

        if (! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
            $errors[] = "Line {$line}: {$field} must be a storage path or image URL.";

            return null;
        }

        $contents = null;

        try {
            $response = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($value);

            if ($response->successful() && $response->body() !== '') {
                $contents = $response->body();
            }
        } catch (\Throwable $e) {
            Log::warning('Category CSV media download failed', [
                'field' => $field,
                'url' => $value,
                'message' => $e->getMessage(),
            ]);
        }

        if ($contents === null || $contents === '') {
            $errors[] = "Line {$line}: could not download {$field} from URL.";

            return null;
        }

        $pathInfo = pathinfo(parse_url($value, PHP_URL_PATH) ?: '');
        $extension = strtolower((string) ($pathInfo['extension'] ?? ''));

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $allowed = ['jpg', 'png', 'webp', 'gif', 'svg'];
        if (! in_array($extension, $allowed, true)) {
            $extension = 'jpg';
        }

        $filename = Str::random(40).'.'.$extension;

        foreach ($directories as $directory) {
            $absoluteDir = storage_path('app/public/'.$directory);

            if (! is_dir($absoluteDir) && ! @mkdir($absoluteDir, 0777, true) && ! is_dir($absoluteDir)) {
                continue;
            }

            @chmod($absoluteDir, 0777);

            if (! is_writable($absoluteDir)) {
                continue;
            }

            $relativePath = $directory.'/'.$filename;

            try {
                if (Storage::disk('public')->put($relativePath, $contents)) {
                    return $relativePath;
                }
            } catch (\Throwable $e) {
                Log::warning('Category CSV media store failed', [
                    'field' => $field,
                    'directory' => $directory,
                    'message' => $e->getMessage(),
                ]);
            }

            if (@file_put_contents($absoluteDir.'/'.$filename, $contents) !== false) {
                @chmod($absoluteDir.'/'.$filename, 0664);

                return $relativePath;
            }
        }

        $errors[] = "Line {$line}: could not store downloaded {$field}.";

        return null;
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $urlKey = $request->input('url_key') ?: Str::slug($request->input('name', ''));
        $request->merge([
            'url_key' => $urlKey,
            'parent_id' => $request->filled('parent_id') ? $request->input('parent_id') : null,
        ]);

        $excludedParentIds = $category
            ? $category->descendantIds()->push($category->id)->all()
            : [];

        return $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id'),
                Rule::notIn($excludedParentIds),
            ],
            'name' => ['required', 'string', 'max:255'],
            'url_key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'url_key')->ignore($category?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
            'position' => ['required', 'integer', 'min:0'],
            'image_file' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],
            'remove_image' => ['nullable', 'boolean'],
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

    private function storeUploadedFile(
        Request $request,
        string $field,
        array $directories,
        array $allowedExtensions = ['jpg', 'png', 'webp', 'gif']
    ): ?string {
        $file = $request->file($field);

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            Log::warning('Category upload missing or invalid', [
                'field' => $field,
                'error' => $file?->getErrorMessage(),
            ]);

            return null;
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if (! in_array($extension, $allowedExtensions, true)) {
            $extension = in_array('jpg', $allowedExtensions, true) ? 'jpg' : $allowedExtensions[0];
        }

        $filename = Str::random(40).'.'.$extension;

        foreach ($directories as $directory) {
            $absoluteDir = storage_path('app/public/'.$directory);

            if (! is_dir($absoluteDir) && ! @mkdir($absoluteDir, 0777, true) && ! is_dir($absoluteDir)) {
                continue;
            }

            @chmod($absoluteDir, 0777);

            if (! is_writable($absoluteDir)) {
                continue;
            }

            $relative = $directory.'/'.$filename;
            $absoluteFile = $absoluteDir.'/'.$filename;

            try {
                $stored = Storage::disk('public')->putFileAs($directory, $file, $filename);

                if (is_string($stored) && $stored !== '' && Storage::disk('public')->exists($stored)) {
                    return $stored;
                }
            } catch (\Throwable $e) {
                Log::warning('Category Storage::putFileAs failed', [
                    'field' => $field,
                    'directory' => $directory,
                    'message' => $e->getMessage(),
                ]);
            }

            $source = $file->getRealPath();

            if ($source && @copy($source, $absoluteFile)) {
                @chmod($absoluteFile, 0664);

                return $relative;
            }

            try {
                $file->move($absoluteDir, $filename);

                if (is_file($absoluteFile)) {
                    @chmod($absoluteFile, 0664);

                    return $relative;
                }
            } catch (\Throwable $e) {
                Log::warning('Category file move failed', [
                    'field' => $field,
                    'directory' => $directory,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::error('Category upload could not be stored in any directory', [
            'field' => $field,
            'tried' => $directories,
            'original' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
        ]);

        return null;
    }

    private function deleteCategoryMedia(Category $category): void
    {
        $this->deleteStoredFile($category->image);
        $this->deleteStoredFile($category->brand_logo);
    }

    private function deleteStoredFile(?string $path): void
    {
        $relative = Product::relativeMediaPath($path);

        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function parentOptions(?Category $exclude = null)
    {
        $excludedIds = $exclude
            ? $exclude->descendantIds()->push($exclude->id)->all()
            : [];

        return Category::query()
            ->roots()
            ->with(['children' => fn ($q) => $q->orderBy('position')->orderBy('name')])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->flatMap(function (Category $root) use ($excludedIds) {
                $options = collect();

                if (! in_array($root->id, $excludedIds, true)) {
                    $options->push([
                        'id' => $root->id,
                        'label' => $root->name,
                    ]);
                }

                foreach ($root->children as $child) {
                    if (! in_array($child->id, $excludedIds, true)) {
                        $options->push([
                            'id' => $child->id,
                            'label' => $root->name.' › '.$child->name,
                        ]);
                    }

                    $child->loadMissing('children');

                    foreach ($child->children as $grandChild) {
                        if (! in_array($grandChild->id, $excludedIds, true)) {
                            $options->push([
                                'id' => $grandChild->id,
                                'label' => $root->name.' › '.$child->name.' › '.$grandChild->name,
                            ]);
                        }
                    }
                }

                return $options;
            });
    }
}
