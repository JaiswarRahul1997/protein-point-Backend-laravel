@extends('admin::layouts.app')

@section('title', $mode === 'create' ? ($isSubCategory ? 'Add Sub-Category' : 'Add Category') : ($isSubCategory ? 'Edit Sub-Category' : 'Edit Category'))

@section('content')
    @php
        $pageTitle = $mode === 'create'
            ? ($isSubCategory ? 'Add Sub-Category' : 'Add Category')
            : ($isSubCategory ? 'Edit Sub-Category' : 'Edit Category');
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

    <form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}">
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
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="1" @selected((string) old('status', $category->status ? '1' : '0') === '1')>Enabled</option>
                        <option value="0" @selected((string) old('status', $category->status ? '1' : '0') === '0')>Disabled</option>
                    </select>
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
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.categories.index') }}">Cancel</a>
        </div>
    </form>
@endsection
