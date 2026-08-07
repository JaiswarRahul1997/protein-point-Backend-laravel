@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Add Banner' : 'Edit Banner')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $mode === 'create' ? 'Add Banner' : 'Edit Banner' }}</h1>
            <p class="page-lead">Upload an image and assign it to a homepage banner slot.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.banners.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.banners.store') : route('admin.banners.update', $banner) }}" enctype="multipart/form-data" id="banner-form">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="title">Title</label>
                    <input class="form-input" id="title" name="title" type="text" value="{{ old('title', $banner->title) }}" required>
                    @error('title')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="slot">Slot</label>
                    <select class="form-select" id="slot" name="slot" required>
                        @foreach (\Admin\Models\Banner::SLOTS as $value => $label)
                            <option value="{{ $value }}" @selected(old('slot', $banner->slot) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('slot')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="subtitle">Subtitle</label>
                    <input class="form-input" id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $banner->subtitle) }}">
                    @error('subtitle')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="cta_text">CTA Text</label>
                    <input class="form-input" id="cta_text" name="cta_text" type="text" value="{{ old('cta_text', $banner->cta_text) }}" placeholder="Shop Now">
                    @error('cta_text')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="cta_link">CTA Link</label>
                    <input class="form-input" id="cta_link" name="cta_link" type="text" value="{{ old('cta_link', $banner->cta_link) }}" placeholder="#shop">
                    @error('cta_link')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        @foreach (\Admin\Models\Banner::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $banner->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-input" id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" required>
                    @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="image_file">Banner Image</label>

                    <input type="hidden" name="remove_image" id="remove_image" value="0">

                    <div class="media-preview" style="margin-bottom:0.75rem">
                        <div class="media-item media-item-banner" id="banner-preview-wrap" @if (! $banner->imageUrl()) style="display:none" @endif>
                            <img id="banner-preview-img" src="{{ $banner->imageUrl() }}" alt="Banner preview">
                            <button type="button" class="media-remove-btn" id="banner-preview-clear" aria-label="Remove image" title="Remove image">&times;</button>
                        </div>
                    </div>

                    <input
                        class="form-input"
                        id="image_file"
                        name="image_file"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                        {{ $mode === 'create' && ! $banner->imageUrl() ? 'required' : '' }}
                    >
                    <p class="form-hint">Layout: one large banner on the left, two stacked small banners on the right. Recommended: main ~1200×600, side ~600×300. JPG/JPEG/PNG/WEBP/GIF · Max 5MB. Click × on the preview to remove the image.</p>
                    @error('image_file')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create Banner' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.banners.index') }}">Back to list</a>
        </div>
    </form>

    <script>
        (function () {
            const fileInput = document.getElementById('image_file');
            const previewWrap = document.getElementById('banner-preview-wrap');
            const previewImg = document.getElementById('banner-preview-img');
            const clearBtn = document.getElementById('banner-preview-clear');
            const removeInput = document.getElementById('remove_image');
            const mode = @json($mode);
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

                if (mode === 'create') {
                    fileInput.required = true;
                }
            });

            clearBtn.addEventListener('click', function () {
                clearObjectUrl();
                fileInput.value = '';
                removeInput.value = '1';
                showPreview(null);

                if (mode === 'create') {
                    fileInput.required = true;
                } else {
                    fileInput.required = false;
                }
            });
        })();
    </script>
@endsection
