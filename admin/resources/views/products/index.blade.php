@extends('admin::layouts.app')

@section('title', $typeLabel)

@php
    $activeFilterCount = collect($filters)->filter(fn ($value) => filled($value))->count();
    $clearUrl = route('admin.products.index', ['type' => $type]);
@endphp

@section('content')
    <header class="page-header">
        <div>
            <p class="form-hint" style="margin:0 0 0.35rem">
                <a href="{{ route('admin.products.index') }}">All Products</a> › {{ $typeLabel }}
            </p>
            <h1 class="page-title">{{ $typeLabel }}</h1>
            <p class="page-lead">
                @if ($type === 'all')
                    View and manage products across all types.
                @else
                    Manage {{ strtolower($typeLabel) }} products.
                @endif
            </p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.products.index') }}">Back to types</a>
            <a class="btn" href="{{ route('admin.products.create', $type === 'all' ? [] : ['type' => $type]) }}">
                {{ $type === 'all' ? 'Add Product' : 'Add '.$typeLabel.' Product' }}
            </a>
        </div>
    </header>

    <div class="grid-toolbar" data-grid-toolbar>
        <button type="button" class="grid-tool-btn {{ $hasActiveFilters ? 'is-active' : '' }}" data-filter-toggle>
            <span class="grid-tool-icon" aria-hidden="true">▾</span>
            Filters
            @if ($activeFilterCount > 0)
                <span class="grid-filter-badge">{{ $activeFilterCount }}</span>
            @endif
        </button>
        <button type="button" class="grid-tool-btn" data-columns-toggle>
            <span class="grid-tool-icon" aria-hidden="true">⚙</span>
            Columns
        </button>

        <div class="grid-columns-menu" data-columns-menu hidden>
            <p class="grid-columns-title" data-columns-count>Columns</p>
            <div class="grid-columns-list" data-columns-list></div>
            <div class="grid-columns-actions">
                <button type="button" data-columns-reset>Reset</button>
                <button type="button" data-columns-close>Cancel</button>
            </div>
        </div>
    </div>

    <div class="grid-filter-panel {{ $hasActiveFilters ? 'is-open' : '' }}" data-filter-panel @if(!$hasActiveFilters) hidden @endif>
        <form method="GET" action="{{ route('admin.products.index', ['type' => $type]) }}">
            <div class="grid-filter-fields">
                <div class="grid-filter-field">
                    <label>ID</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="number" name="id_from" placeholder="from" value="{{ $filters['id_from'] }}">
                        <input class="form-input" type="number" name="id_to" placeholder="to" value="{{ $filters['id_to'] }}">
                    </div>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-name">Name</label>
                    <input class="form-input" id="filter-name" type="text" name="name" value="{{ $filters['name'] }}">
                </div>

                <div class="grid-filter-field">
                    <label for="filter-type">Type</label>
                    @if ($type === 'all')
                        <select class="form-input" id="filter-type" name="product_type">
                            <option value="">All Types</option>
                            @foreach ($filterOptions['types'] as $slug => $label)
                                <option value="{{ $slug }}" @selected($filters['product_type'] === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input class="form-input" id="filter-type" type="text" value="{{ $typeLabel }}" disabled>
                    @endif
                </div>

                <div class="grid-filter-field">
                    <label for="filter-attribute-set">Attribute Set</label>
                    <select class="form-input" id="filter-attribute-set" name="attribute_set">
                        <option value="">Any</option>
                        @foreach ($filterOptions['attributeSets'] as $set)
                            <option value="{{ $set }}" @selected($filters['attribute_set'] === $set)>{{ $set }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-sku">SKU</label>
                    <input class="form-input" id="filter-sku" type="text" name="sku" value="{{ $filters['sku'] }}">
                </div>

                <div class="grid-filter-field">
                    <label>Price</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="number" step="0.01" name="price_from" placeholder="from" value="{{ $filters['price_from'] }}">
                        <input class="form-input" type="number" step="0.01" name="price_to" placeholder="to" value="{{ $filters['price_to'] }}">
                    </div>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-visibility">Visibility</label>
                    <select class="form-input" id="filter-visibility" name="visibility">
                        <option value="">Any</option>
                        @foreach ($filterOptions['visibilities'] as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['visibility'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-status">Status</label>
                    <select class="form-input" id="filter-status" name="status">
                        <option value="">Any</option>
                        @foreach ($filterOptions['statuses'] as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['status'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-stock-status">Stock Status</label>
                    <select class="form-input" id="filter-stock-status" name="stock_status">
                        <option value="">Any</option>
                        @foreach ($filterOptions['stockStatuses'] as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['stock_status'] === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-url-key">URL Key</label>
                    <input class="form-input" id="filter-url-key" type="text" name="url_key" value="{{ $filters['url_key'] }}">
                </div>

                <div class="grid-filter-field">
                    <label for="filter-brand">Brand</label>
                    <select class="form-input" id="filter-brand" name="brand">
                        <option value="">Any</option>
                        @foreach ($filterOptions['brands'] as $brand)
                            <option value="{{ $brand }}" @selected($filters['brand'] === $brand)>{{ $brand }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label>Last Updated At</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="date" name="updated_from" value="{{ $filters['updated_from'] }}">
                        <input class="form-input" type="date" name="updated_to" value="{{ $filters['updated_to'] }}">
                    </div>
                </div>
            </div>

            <div class="grid-filter-actions">
                @if ($hasActiveFilters)
                    <a class="grid-filter-cancel" href="{{ $clearUrl }}">Clear all</a>
                @endif
                <button class="grid-filter-cancel" type="button" data-filter-cancel>Cancel</button>
                <button class="btn" type="submit">Apply Filters</button>
            </div>
        </form>
    </div>

    @if ($products->isEmpty())
        <div class="empty-state">
            @if ($hasActiveFilters)
                <p>No products match the selected filters.</p>
                <a class="btn btn-outline" href="{{ $clearUrl }}">Clear Filters</a>
            @else
                <p>No products found{{ $type === 'all' ? '' : ' for '.strtolower($typeLabel) }}.</p>
                <a class="btn" href="{{ route('admin.products.create', $type === 'all' ? [] : ['type' => $type]) }}">
                    {{ $type === 'all' ? 'Create your first product' : 'Create your first '.strtolower($typeLabel).' product' }}
                </a>
            @endif
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table" id="products-table" data-column-storage="admin.products.columns.{{ $type }}">
                <thead>
                    <tr>
                        <th data-col="id">ID</th>
                        <th data-col="thumbnail">Thumbnail</th>
                        <th data-col="name">Name</th>
                        <th data-col="type">Type</th>
                        <th data-col="attribute_set">Attribute Set</th>
                        <th data-col="stock_status">Stock Status</th>
                        <th data-col="categories">Categories</th>
                        <th data-col="sku">SKU</th>
                        <th data-col="price">Price</th>
                        <th data-col="quantity">Quantity</th>
                        <th data-col="visibility">Visibility</th>
                        <th data-col="status">Status</th>
                        <th data-col="url_key">URL Key</th>
                        <th data-col="brand">Brand</th>
                        <th data-col="updated_at">Last Updated At</th>
                        <th data-col="action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td data-col="id">{{ $product->id }}</td>
                            <td data-col="thumbnail">
                                @if ($product->thumbnailUrl())
                                    <img
                                        class="thumb"
                                        src="{{ $product->thumbnailUrl() }}"
                                        alt=""
                                        onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'thumb-placeholder',textContent:'N/A'}))"
                                    >
                                @else
                                    <span class="thumb-placeholder">N/A</span>
                                @endif
                            </td>
                            <td data-col="name">{{ $product->name }}</td>
                            <td data-col="type">{{ $product->typeLabel() }}</td>
                            <td data-col="attribute_set">{{ $product->attribute_set }}</td>
                            <td data-col="stock_status">{{ $product->stockStatusLabel() }}</td>
                            <td data-col="categories">{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</td>
                            <td data-col="sku">{{ $product->sku }}</td>
                            <td data-col="price">{{ number_format((float) $product->price, 2) }}</td>
                            <td data-col="quantity">{{ $product->quantity }}</td>
                            <td data-col="visibility">{{ $product->visibilityLabel() }}</td>
                            <td data-col="status">{{ $product->statusLabel() }}</td>
                            <td data-col="url_key">{{ $product->url_key }}</td>
                            <td data-col="brand">{{ $product->brand ?: '—' }}</td>
                            <td data-col="updated_at">{{ $product->updated_at?->format('Y-m-d H:i') }}</td>
                            <td data-col="action">
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            @if ($products->hasPages())
                <div class="actions">
                    @if ($products->onFirstPage())
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Previous</span>
                    @else
                        <a class="btn btn-outline btn-sm" href="{{ $products->previousPageUrl() }}">Previous</a>
                    @endif
                    <span style="font-size:0.85rem;align-self:center">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
                    @if ($products->hasMorePages())
                        <a class="btn btn-outline btn-sm" href="{{ $products->nextPageUrl() }}">Next</a>
                    @else
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @include('admin::partials.grid-toolbar-script')
@endsection
