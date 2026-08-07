<?php

namespace Admin\Http\Controllers;

use Admin\Models\Banner;
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

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()
            ->orderByRaw("CASE slot WHEN 'left' THEN 1 WHEN 'center' THEN 2 WHEN 'right' THEN 3 ELSE 4 END")
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin::banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin::banners.form', [
            'banner' => new Banner([
                'slot' => 'left',
                'status' => 'enabled',
                'sort_order' => 0,
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        unset($data['image_file'], $data['remove_image']);
        $data['image'] = $this->storeImage($request);

        if (! $data['image']) {
            return back()
                ->withInput()
                ->withErrors(['image_file' => 'Could not store the uploaded image. Check file permissions and try again.']);
        }

        Banner::create($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin::banners.form', [
            'banner' => $banner,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['image_file'], $data['remove_image']);

        if ($request->hasFile('image_file')) {
            $stored = $this->storeImage($request);

            if (! $stored) {
                return back()
                    ->withInput()
                    ->withErrors(['image_file' => 'Could not store the uploaded image. Check file permissions and try again.']);
            }

            $this->deleteStoredFile($banner->image);
            $data['image'] = $stored;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteStoredFile($banner->image);
            $data['image'] = null;
        }

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $this->deleteStoredFile($banner->image);
        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully.');
    }

    private function validated(Request $request, bool $requireImage = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_link' => ['nullable', 'string', 'max:500'],
            'slot' => ['required', Rule::in(array_keys(Banner::SLOTS))],
            'status' => ['required', Rule::in(array_keys(Banner::STATUSES))],
            'sort_order' => ['required', 'integer', 'min:0'],
            'image_file' => [
                $requireImage ? 'required' : 'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        $file = $request->file('image_file');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            Log::warning('Banner upload missing or invalid', [
                'error' => $file?->getErrorMessage(),
            ]);

            return null;
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if (! in_array($extension, ['jpg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $filename = Str::random(40).'.'.$extension;
        $directories = ['banners', 'products/banners', 'products/images'];

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
                Log::warning('Banner Storage::putFileAs failed', [
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
                Log::warning('Banner file move failed', [
                    'directory' => $directory,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::error('Banner upload could not be stored in any directory', [
            'tried' => $directories,
            'original' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
        ]);

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
