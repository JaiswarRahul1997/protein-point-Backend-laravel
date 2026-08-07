@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Add Menu Item' : 'Edit Menu Item')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $mode === 'create' ? 'Add Menu Item' : 'Edit Menu Item' }}</h1>
            <p class="page-lead">Set the label, link URL, location, and sort order for the storefront menu.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.menus.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.menus.store') : route('admin.menus.update', $menu) }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <section class="form-panel">
            <div class="form-grid">
                <div class="form-field">
                    <label class="form-label" for="label">Label</label>
                    <input class="form-input" id="label" name="label" type="text" value="{{ old('label', $menu->label) }}" required placeholder="Shop">
                    @error('label')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="url">URL</label>
                    <input class="form-input" id="url" name="url" type="text" value="{{ old('url', $menu->url) }}" required placeholder="/products or /#offers or https://…">
                    <p class="form-hint">Internal paths (`/products`, `/#home`) or full external URLs.</p>
                    @error('url')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="location">Location</label>
                    <select class="form-select" id="location" name="location" required>
                        @foreach (\Admin\Models\Menu::LOCATIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('location', $menu->location) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('location')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="status">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        @foreach (\Admin\Models\Menu::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $menu->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="sort_order">Sort Order</label>
                    <input class="form-input" id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $menu->sort_order ?? 0) }}" required>
                    <p class="form-hint">Lower numbers appear first.</p>
                    @error('sort_order')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-field">
                    <label class="form-label" for="open_in_new_tab">Open in new tab</label>
                    <label class="checkbox-item" style="margin-top:0.55rem">
                        <input type="checkbox" id="open_in_new_tab" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $menu->open_in_new_tab))>
                        Yes
                    </label>
                    @error('open_in_new_tab')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $mode === 'create' ? 'Create Menu Item' : 'Save Changes' }}</button>
            <a class="btn btn-outline" href="{{ route('admin.menus.index') }}">Back to list</a>
        </div>
    </form>
@endsection
