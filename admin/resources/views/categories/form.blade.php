@extends('admin::layouts.app')

@section('title', $mode === 'create' ? ($isSubCategory ? 'Add Sub-Category' : 'Add Category') : ($isSubCategory ? 'Edit Sub-Category' : 'Edit Category'))

@section('content')
    @php
        $pageTitle = $mode === 'create'
            ? ($isSubCategory ? 'Add Sub-Category' : 'Add Category')
            : ($isSubCategory ? 'Edit Sub-Category' : 'Edit Category');
        $previewUrl = $category->adminImageUrl() ?: $category->imageUrl();
        $logoPreviewUrl = $category->adminBrandLogoUrl() ?: $category->brandLogoUrl();
    @endphp

    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $pageTitle }}</h1>
            <p class="page-lead">
                {{ $isSubCategory ? 'Nest this under a parent category.' : 'Create a top-level category or nest it under another.' }}
            </p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.categories.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}" enctype="multipart/form-data" id="category-form">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <div class="form-grid">
                <div class="form-field full">
                    <label class="form-label" for="parent_id">Parent Category</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">— None (top-level category) —</option>
                        @foreach ($parentOptions as $option)
                            <option value="{{ $option['id'] }}" @selected((string) old('parent_id', $category->parent_id) === (string) $option['id'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="form-hint">Choose a parent to make this a sub-category.</p>
                    @error('parent_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-input" id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="url_key">URL Key</label>
                    <input class="form-input" id="url_key" name="url_key" type="text" value="{{ old('url_key', $category->url_key) }}" placeholder="Auto from name if empty">
                    @error('url_key')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Show on Frontend</label>
                    <input type="hidden" name="status" value="0">
                    <label class="status-switch" for="status" style="margin-top:0.35rem">
                        <input
                            id="status"
                            type="checkbox"
                            name="status"
                            value="1"
                            @checked((string) old('status', $category->status ? '1' : '0') === '1')
                        >
                        <span class="status-switch-track" aria-hidden="true"></span>
                        <span class="status-switch-label" id="status-label">{{ (string) old('status', $category->status ? '1' : '0') === '1' ? 'On' : 'Off' }}</span>
                    </label>
                    <p class="form-hint">On = visible on the storefront. Off = hidden.</p>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="position">Position</label>
                    <input class="form-input" id="position" name="position" type="number" min="0" value="{{ old('position', $category->position ?? 0) }}" required>
                    @error('position')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-textarea" id="description" name="description">{{ old('description', $category->description) }}</textarea>
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="image_file">Category Image</label>

                    <input type="hidden" name="remove_image" id="remove_image" value="0">

                    <div class="media-preview" style="margin-bottom:0.75rem">
                        <div class="media-item" id="category-preview-wrap" @if (! $previewUrl) style="display:none" @endif>
                            <img id="category-preview-img" src="{{ $previewUrl }}" alt="Category preview">
                            <button type="button" class="media-remove-btn" id="category-preview-clear" aria-label="Remove image" title="Remove image">&times;</button>
                        </div>
                    </div>

                    <input
                        class="form-input"
                        id="image_file"
                        name="image_file"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                    >
                    <p class="form-hint">
                        Optional. Shown on the storefront category grid.
                        JPG/JPEG/PNG/WEBP/GIF · Max 5MB.
                    </p>
                    @error('image_file')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="brand_logo_file">Brand Logo</label>

                    <input type="hidden" name="remove_brand_logo" id="remove_brand_logo" value="0">

                    <div class="media-preview" style="margin-bottom:0.75rem">
                        <div class="media-item" id="brand-logo-preview-wrap" @if (! $logoPreviewUrl) style="display:none" @endif>
                            <img id="brand-logo-preview-img" src="{{ $logoPreviewUrl }}" alt="Brand logo preview">
                            <button type="button" class="media-remove-btn" id="brand-logo-preview-clear" aria-label="Remove brand logo" title="Remove brand logo">&times;</button>
                        </div>
                    </div>

                    <input
                        class="form-input"
                        id="brand_logo_file"
                        name="brand_logo_file"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
                    >
                    <p class="form-hint">
                        Optional. Brand logo for this category.
                        JPG/JPEG/PNG/WEBP/GIF/SVG · Max 5MB.
                    </p>
                    @error('brand_logo_file')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.categories.index') }}">Cancel</a>
        </div>
    </form>

    <script>
        (function () {
            const statusInput = document.getElementById('status');
            const statusLabel = document.getElementById('status-label');
            if (statusInput && statusLabel) {
                statusInput.addEventListener('change', function () {
                    statusLabel.textContent = statusInput.checked ? 'On' : 'Off';
                });
            }

            function bindMediaPreview(fileInputId, previewWrapId, previewImgId, clearBtnId, removeInputId) {
                const fileInput = document.getElementById(fileInputId);
                const previewWrap = document.getElementById(previewWrapId);
                const previewImg = document.getElementById(previewImgId);
                const clearBtn = document.getElementById(clearBtnId);
                const removeInput = document.getElementById(removeInputId);
                let objectUrl = null;

                if (!fileInput || !previewWrap || !previewImg || !clearBtn || !removeInput) {
                    return;
                }

                function showPreview(src) {
                    if (!src) {
                        previewWrap.style.display = 'none';
                        previewImg.removeAttribute('src');
                        return;
                    }
                    previewImg.src = src;
                    previewWrap.style.display = '';
                }

                function clearObjectUrl() {
                    if (objectUrl) {
                        URL.revokeObjectURL(objectUrl);
                        objectUrl = null;
                    }
                }

                fileInput.addEventListener('change', function () {
                    const file = fileInput.files && fileInput.files[0];
                    clearObjectUrl();

                    if (!file) {
                        if (removeInput.value === '1') {
                            showPreview(null);
                        }
                        return;
                    }

                    removeInput.value = '0';
                    objectUrl = URL.createObjectURL(file);
                    showPreview(objectUrl);
                });

                clearBtn.addEventListener('click', function () {
                    clearObjectUrl();
                    fileInput.value = '';
                    removeInput.value = '1';
                    showPreview(null);
                });
            }

            bindMediaPreview(
                'image_file',
                'category-preview-wrap',
                'category-preview-img',
                'category-preview-clear',
                'remove_image'
            );

            bindMediaPreview(
                'brand_logo_file',
                'brand-logo-preview-wrap',
                'brand-logo-preview-img',
                'brand-logo-preview-clear',
                'remove_brand_logo'
            );
        })();
    </script>
@endsection
