@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Add Brand' : 'Edit Brand')

@section('content')
    @php
        $logoPreviewUrl = $brand->adminBrandLogoUrl() ?: $brand->brandLogoUrl();
    @endphp

    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $mode === 'create' ? 'Add Brand' : 'Edit Brand' }}</h1>
            <p class="page-lead">Add a logo, description, and the products that belong to this brand.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.brands.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.brands.store') : route('admin.brands.update', $brand) }}" enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-input" id="name" name="name" type="text" value="{{ old('name', $brand->name) }}" required>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="url_key">URL Key</label>
                    <input class="form-input" id="url_key" name="url_key" type="text" value="{{ old('url_key', $brand->url_key) }}" placeholder="Auto from name if empty">
                    <p class="form-hint">Storefront path: /brands/{{ old('url_key', $brand->url_key) ?: 'url-key' }}</p>
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
                            @checked((string) old('status', $brand->status ? '1' : '0') === '1')
                        >
                        <span class="status-switch-track" aria-hidden="true"></span>
                        <span class="status-switch-label" id="status-label">{{ (string) old('status', $brand->status ? '1' : '0') === '1' ? 'On' : 'Off' }}</span>
                    </label>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="position">Position</label>
                    <input class="form-input" id="position" name="position" type="number" min="0" value="{{ old('position', $brand->position ?? 0) }}" required>
                    @error('position')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-textarea" id="description" name="description" rows="5">{{ old('description', $brand->description) }}</textarea>
                    <p class="form-hint">Shown on the brand product listing page next to the logo.</p>
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="brand_logo_file">Brand Logo / Image</label>
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
                    <p class="form-hint">JPG/JPEG/PNG/WEBP/GIF/SVG · Max 5MB. Used on the A–Z brands directory and brand page.</p>
                    @error('brand_logo_file')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label">Associated Products</label>
                    @if ($products->isEmpty())
                        <p class="form-hint">No products yet. <a href="{{ route('admin.products.create') }}">Create a product</a> first.</p>
                    @else
                        <div class="checkbox-list" style="max-height:22rem;overflow:auto;padding:0.5rem 0.65rem;border:1px solid var(--line, #e5e5e5)">
                            @foreach ($products as $product)
                                <label class="checkbox-item">
                                    <input
                                        type="checkbox"
                                        name="products[]"
                                        value="{{ $product->id }}"
                                        @checked(in_array($product->id, old('products', $selectedProductIds), true))
                                    >
                                    {{ $product->name }}
                                    <span style="opacity:0.65">· {{ $product->sku }}{{ $product->brand ? ' · '.$product->brand : '' }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <p class="form-hint">Selected products appear on this brand’s storefront page.</p>
                    @error('products')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create Brand' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.brands.index') }}">Cancel</a>
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

            const fileInput = document.getElementById('brand_logo_file');
            const previewWrap = document.getElementById('brand-logo-preview-wrap');
            const previewImg = document.getElementById('brand-logo-preview-img');
            const clearBtn = document.getElementById('brand-logo-preview-clear');
            const removeInput = document.getElementById('remove_brand_logo');
            let objectUrl = null;

            function showPreview(src) {
                if (!src) {
                    previewWrap.style.display = 'none';
                    previewImg.removeAttribute('src');
                    return;
                }
                previewImg.src = src;
                previewWrap.style.display = '';
            }

            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0];
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                if (!file) return;
                removeInput.value = '0';
                objectUrl = URL.createObjectURL(file);
                showPreview(objectUrl);
            });

            clearBtn.addEventListener('click', function () {
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                fileInput.value = '';
                removeInput.value = '1';
                showPreview(null);
            });
        })();
    </script>
@endsection
