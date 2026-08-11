@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Add Product' : 'Edit Product')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $mode === 'create' ? 'Add Product' : 'Edit Product' }}</h1>
            <p class="page-lead">{{ $product->typeLabel() }} product details.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.products.index', ['type' => $product->type]) }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.products.store') : route('admin.products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <h2 class="section-title">Product Details</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-input" id="name" name="name" type="text" value="{{ old('name', $product->name) }}" required>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="sku">SKU</label>
                    <input class="form-input" id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" required>
                    @error('sku')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="type">Type</label>
                    <select class="form-select" id="type" name="type" required>
                        @foreach (\Admin\Models\Product::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="attribute_set">Attribute Set</label>
                    <input class="form-input" id="attribute_set" name="attribute_set" type="text" value="{{ old('attribute_set', $product->attribute_set ?: 'Default') }}" required>
                    @error('attribute_set')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="stock_status">Stock Status</label>
                    <select class="form-select" id="stock_status" name="stock_status" required>
                        @foreach (\Admin\Models\Product::STOCK_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('stock_status', $product->stock_status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('stock_status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="price">Price</label>
                    <input class="form-input" id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price ?? 0) }}" required>
                    @error('price')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input class="form-input" id="quantity" name="quantity" type="number" min="0" value="{{ old('quantity', $product->quantity ?? 0) }}" required>
                    @error('quantity')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="visibility">Visibility</label>
                    <select class="form-select" id="visibility" name="visibility" required>
                        @foreach (\Admin\Models\Product::VISIBILITIES as $value => $label)
                            <option value="{{ $value }}" @selected(old('visibility', $product->visibility) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        @foreach (\Admin\Models\Product::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $product->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="url_key">URL Key</label>
                    <input class="form-input" id="url_key" name="url_key" type="text" value="{{ old('url_key', $product->url_key) }}" placeholder="Auto from name if empty">
                    @error('url_key')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="brand">Brand</label>
                    <input class="form-input" id="brand" name="brand" type="text" value="{{ old('brand', $product->brand) }}">
                    @error('brand')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="sizes">Sizes</label>
                    <input
                        class="form-input"
                        id="sizes"
                        name="sizes"
                        type="text"
                        value="{{ old('sizes', is_array($product->sizes) ? implode(', ', $product->sizes) : '') }}"
                        placeholder="500g, 1kg, 2kg"
                    >
                    <p class="form-hint">Comma-separated options for the storefront Size dropdown. Leave blank to use defaults.</p>
                    @error('sizes')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="flavors">Flavors</label>
                    <input
                        class="form-input"
                        id="flavors"
                        name="flavors"
                        type="text"
                        value="{{ old('flavors', is_array($product->flavors) ? implode(', ', $product->flavors) : '') }}"
                        placeholder="Chocolate, Vanilla, Strawberry"
                    >
                    <p class="form-hint">Comma-separated options for the storefront Flavor dropdown. Leave blank to use defaults.</p>
                    @error('flavors')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label">Categories</label>
                    @if ($categories->isEmpty())
                        <p class="form-hint">No categories yet. <a href="{{ route('admin.categories.create') }}">Create one</a>.</p>
                    @else
                        <div class="checkbox-list">
                            @foreach ($categories as $category)
                                <label class="checkbox-item">
                                    <input
                                        type="checkbox"
                                        name="categories[]"
                                        value="{{ $category['id'] }}"
                                        @checked(in_array($category['id'], old('categories', $selectedCategoryIds), true))
                                    >
                                    {{ $category['label'] }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('categories')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-textarea" id="description" name="description">{{ old('description', $product->description) }}</textarea>
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="form-panel">
            <h2 class="section-title">Images and Videos</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="thumbnail_file">Thumbnail</label>
                    @if ($product->thumbnailUrl())
                        <div class="media-preview" style="margin-bottom:0.75rem">
                            <div class="media-item">
                                <img src="{{ $product->thumbnailUrl() }}" alt="Thumbnail" onerror="this.style.display='none'">
                                <label class="checkbox-item" style="margin-top:0.5rem">
                                    <input type="checkbox" name="remove_thumbnail" value="1">
                                    Remove current thumbnail
                                </label>
                            </div>
                        </div>
                    @endif
                    <input class="form-input" id="thumbnail_file" name="thumbnail_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                    <p class="form-hint">Upload from your computer (JPG, PNG, WEBP, GIF · max 5MB).</p>
                    @error('thumbnail_file')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="image_files">Upload Images</label>
                    <input class="form-input" id="image_files" name="image_files[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                    <p class="form-hint">Select one or more images from your computer (max 5MB each).</p>
                    @error('image_files')<p class="form-error">{{ $message }}</p>@enderror
                    @error('image_files.*')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    @if (! empty($product->images))
                        <p class="form-label">Current Images</p>
                        <div class="media-preview">
                            @foreach ($product->images as $image)
                                @if ($imageUrl = \Admin\Models\Product::mediaUrl($image))
                                <label class="media-item">
                                    <img src="{{ $imageUrl }}" alt="Product image" onerror="this.style.display='none'">
                                    <span class="checkbox-item">
                                        <input type="checkbox" name="existing_images[]" value="{{ $image }}" checked>
                                        Keep
                                    </span>
                                </label>
                                @endif
                            @endforeach
                        </div>
                        <p class="form-hint">Uncheck “Keep” to remove an image on save.</p>
                    @endif
                </div>

                <div class="form-field">
                    <label class="form-label" for="video_files">Upload Videos</label>
                    <input class="form-input" id="video_files" name="video_files[]" type="file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska" multiple>
                    <p class="form-hint">Select one or more videos from your computer (MP4, WEBM, MOV · max 50MB each).</p>
                    @error('video_files')<p class="form-error">{{ $message }}</p>@enderror
                    @error('video_files.*')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    @if (! empty($product->videos))
                        <p class="form-label">Current Videos</p>
                        <div class="media-preview">
                            @foreach ($product->videos as $video)
                                @if ($videoUrl = \Admin\Models\Product::mediaUrl($video))
                                <label class="media-item media-item-video">
                                    <video src="{{ $videoUrl }}" controls preload="metadata"></video>
                                    <span class="checkbox-item">
                                        <input type="checkbox" name="existing_videos[]" value="{{ $video }}" checked>
                                        Keep
                                    </span>
                                </label>
                                @endif
                            @endforeach
                        </div>
                        <p class="form-hint">Uncheck “Keep” to remove a video on save.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="form-panel">
            <h2 class="section-title">Search Engine Optimization</h2>
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="meta_title">Meta Title</label>
                    <input class="form-input" id="meta_title" name="meta_title" type="text" value="{{ old('meta_title', $product->meta_title) }}">
                    @error('meta_title')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="meta_keywords">Meta Keywords</label>
                    <input class="form-input" id="meta_keywords" name="meta_keywords" type="text" value="{{ old('meta_keywords', $product->meta_keywords) }}">
                    @error('meta_keywords')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field full">
                    <label class="form-label" for="meta_description">Meta Description</label>
                    <textarea class="form-textarea" id="meta_description" name="meta_description">{{ old('meta_description', $product->meta_description) }}</textarea>
                    @error('meta_description')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create Product' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.products.index', ['type' => $product->type]) }}">Cancel</a>
        </div>
    </form>
@endsection
