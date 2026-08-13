@extends('admin::layouts.app')

@section('title', 'Categories')

@php
    $filters = $filters ?? [];
    $hasActiveFilters = $hasActiveFilters ?? false;
    $filteredMode = $filteredMode ?? false;
    $activeFilterCount = collect($filters)->filter(fn ($value) => filled($value))->count();
    $clearUrl = route('admin.categories.index');
@endphp

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Categories</h1>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.categories.export-csv') }}">Download CSV</a>
            <a class="btn" href="{{ route('admin.categories.create') }}">Add Category</a>
        </div>
    </header>

    <section class="form-panel" style="margin-bottom:1.5rem">
        <h2 class="section-title" style="margin-bottom:0.75rem">Upload Categories CSV</h2>
        <form method="POST" action="{{ route('admin.categories.import-csv') }}" enctype="multipart/form-data" class="actions" style="flex-wrap:wrap">
            @csrf
            <input
                class="form-input"
                type="file"
                name="csv_file"
                accept=".csv,text/csv"
                required
                style="max-width:320px"
            >
            <button class="btn" type="submit">Upload CSV</button>
            <a class="btn btn-outline" href="{{ route('admin.categories.export-csv') }}">Download current CSV</a>
        </form>
        @error('csv_file')
            <p class="form-error" style="margin-top:0.75rem">{{ $message }}</p>
        @enderror
    </section>

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
        <form method="GET" action="{{ route('admin.categories.index') }}">
            <div class="grid-filter-fields">
                <div class="grid-filter-field">
                    <label>ID</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="number" name="id_from" placeholder="from" value="{{ $filters['id_from'] ?? '' }}">
                        <input class="form-input" type="number" name="id_to" placeholder="to" value="{{ $filters['id_to'] ?? '' }}">
                    </div>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-cat-name">Name</label>
                    <input class="form-input" id="filter-cat-name" type="text" name="name" value="{{ $filters['name'] ?? '' }}">
                </div>

                <div class="grid-filter-field">
                    <label for="filter-cat-url-key">URL Key</label>
                    <input class="form-input" id="filter-cat-url-key" type="text" name="url_key" value="{{ $filters['url_key'] ?? '' }}">
                </div>

                <div class="grid-filter-field">
                    <label for="filter-cat-type">Type</label>
                    <select class="form-input" id="filter-cat-type" name="category_type">
                        <option value="">Any</option>
                        <option value="category" @selected(($filters['category_type'] ?? '') === 'category')>Category</option>
                        <option value="subcategory" @selected(($filters['category_type'] ?? '') === 'subcategory')>Sub-Category</option>
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label for="filter-cat-status">Show on Frontend</label>
                    <select class="form-input" id="filter-cat-status" name="status">
                        <option value="">Any</option>
                        <option value="1" @selected(($filters['status'] ?? '') === '1')>On</option>
                        <option value="0" @selected(($filters['status'] ?? '') === '0')>Off</option>
                    </select>
                </div>

                <div class="grid-filter-field">
                    <label>Position</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="number" name="position_from" placeholder="from" value="{{ $filters['position_from'] ?? '' }}">
                        <input class="form-input" type="number" name="position_to" placeholder="to" value="{{ $filters['position_to'] ?? '' }}">
                    </div>
                </div>

                <div class="grid-filter-field">
                    <label>Last Updated At</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="date" name="updated_from" value="{{ $filters['updated_from'] ?? '' }}">
                        <input class="form-input" type="date" name="updated_to" value="{{ $filters['updated_to'] ?? '' }}">
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

    @if ($categories->isEmpty())
        <div class="empty-state">
            @if ($hasActiveFilters)
                <p>No categories match the selected filters.</p>
                <a class="btn btn-outline" href="{{ $clearUrl }}">Clear Filters</a>
            @else
                <p>No categories found.</p>
                <a class="btn" href="{{ route('admin.categories.create') }}">Create your first category</a>
            @endif
        </div>
    @elseif ($filteredMode)
        <div class="table-wrap">
            <table class="data-table" id="categories-table" data-column-storage="admin.categories.columns">
                <thead>
                    <tr>
                        <th data-col="id">ID</th>
                        <th data-col="image">Image</th>
                        <th data-col="brand_logo">Brand Logo</th>
                        <th data-col="name">Name</th>
                        <th data-col="type">Type</th>
                        <th data-col="parent">Parent</th>
                        <th data-col="url_key">URL Key</th>
                        <th data-col="products">Products</th>
                        <th data-col="status">Show on Frontend</th>
                        <th data-col="position">Position</th>
                        <th data-col="updated_at">Last Updated At</th>
                        <th data-col="action" data-col-locked="1">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php
                            $image = $category->adminImageUrl() ?: $category->imageUrl();
                            $logo = $category->adminBrandLogoUrl() ?: $category->brandLogoUrl();
                        @endphp
                        <tr>
                            <td data-col="id">{{ $category->id }}</td>
                            <td data-col="image">
                                @if ($image)
                                    <img src="{{ $image }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                @else
                                    —
                                @endif
                            </td>
                            <td data-col="brand_logo">
                                @if ($logo)
                                    <img src="{{ $logo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                @else
                                    —
                                @endif
                            </td>
                            <td data-col="name"><strong>{{ $category->name }}</strong></td>
                            <td data-col="type">{{ $category->parent_id ? 'Sub-Category' : 'Category' }}</td>
                            <td data-col="parent">{{ $category->parent?->name ?: '—' }}</td>
                            <td data-col="url_key">{{ $category->url_key }}</td>
                            <td data-col="products">{{ $category->products_count }}</td>
                            <td data-col="status">
                                <form class="status-switch-form" method="POST" action="{{ route('admin.categories.toggle-status', $category) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="status-switch" title="{{ $category->status ? 'Visible on storefront' : 'Hidden from storefront' }}">
                                        <input type="checkbox" @checked($category->status) onchange="this.form.submit()">
                                        <span class="status-switch-track" aria-hidden="true"></span>
                                        <span class="status-switch-label">{{ $category->status ? 'On' : 'Off' }}</span>
                                    </label>
                                </form>
                            </td>
                            <td data-col="position">{{ $category->position }}</td>
                            <td data-col="updated_at">{{ $category->updated_at?->format('Y-m-d H:i') }}</td>
                            <td data-col="action">
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}">Add Sub-Category</a>
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
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
    @else
        <div class="table-wrap">
            <table class="data-table" id="categories-table" data-column-storage="admin.categories.columns">
                <thead>
                    <tr>
                        <th data-col="image">Image</th>
                        <th data-col="brand_logo">Brand Logo</th>
                        <th data-col="name">Name</th>
                        <th data-col="type">Type</th>
                        <th data-col="url_key">URL Key</th>
                        <th data-col="products">Products</th>
                        <th data-col="status">Show on Frontend</th>
                        <th data-col="position">Position</th>
                        <th data-col="updated_at">Last Updated At</th>
                        <th data-col="action" data-col-locked="1">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        @php
                            $hasChildren = $category->children_count > 0;
                            $parentImage = $category->adminImageUrl() ?: $category->imageUrl();
                            $parentLogo = $category->adminBrandLogoUrl() ?: $category->brandLogoUrl();
                        @endphp

                        <tr class="category-parent-row" data-category-id="{{ $category->id }}">
                            <td data-col="image">
                                @if ($parentImage)
                                    <img src="{{ $parentImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                @else
                                    —
                                @endif
                            </td>
                            <td data-col="brand_logo">
                                @if ($parentLogo)
                                    <img src="{{ $parentLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                @else
                                    —
                                @endif
                            </td>
                            <td data-col="name">
                                @if ($hasChildren)
                                    <button
                                        type="button"
                                        class="category-name-toggle"
                                        data-toggle-children="{{ $category->id }}"
                                        aria-expanded="false"
                                    >
                                        <span class="category-chevron" aria-hidden="true">▸</span>
                                        <strong>{{ $category->name }}</strong>
                                        <span class="category-child-count">({{ $category->children_count }})</span>
                                    </button>
                                @else
                                    <strong>{{ $category->name }}</strong>
                                    <span class="category-child-count">(0)</span>
                                @endif
                            </td>
                            <td data-col="type">Category</td>
                            <td data-col="url_key">{{ $category->url_key }}</td>
                            <td data-col="products">{{ $category->products_count }}</td>
                            <td data-col="status">
                                <form class="status-switch-form" method="POST" action="{{ route('admin.categories.toggle-status', $category) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="status-switch" title="{{ $category->status ? 'Visible on storefront' : 'Hidden from storefront' }}">
                                        <input type="checkbox" @checked($category->status) onchange="this.form.submit()">
                                        <span class="status-switch-track" aria-hidden="true"></span>
                                        <span class="status-switch-label">{{ $category->status ? 'On' : 'Off' }}</span>
                                    </label>
                                </form>
                            </td>
                            <td data-col="position">{{ $category->position }}</td>
                            <td data-col="updated_at">{{ $category->updated_at?->format('Y-m-d H:i') }}</td>
                            <td data-col="action">
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}">Add Sub-Category</a>
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and its sub-categories?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        @foreach ($category->children as $child)
                            @php
                                $childHasChildren = $child->children_count > 0;
                                $childImage = $child->adminImageUrl() ?: $child->imageUrl();
                                $childLogo = $child->adminBrandLogoUrl() ?: $child->brandLogoUrl();
                            @endphp

                            <tr
                                class="category-child-row"
                                data-parent-id="{{ $category->id }}"
                                data-category-id="{{ $child->id }}"
                                hidden
                            >
                                <td data-col="image">
                                    @if ($childImage)
                                        <img src="{{ $childImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-col="brand_logo">
                                    @if ($childLogo)
                                        <img src="{{ $childLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="category-child-name" data-col="name">
                                    @if ($childHasChildren)
                                        <button
                                            type="button"
                                            class="category-name-toggle"
                                            data-toggle-children="{{ $child->id }}"
                                            aria-expanded="false"
                                        >
                                            <span class="category-chevron" aria-hidden="true">▸</span>
                                            <span>↳ {{ $child->name }}</span>
                                            <span class="category-child-count">({{ $child->children_count }})</span>
                                        </button>
                                    @else
                                        <span>↳ {{ $child->name }}</span>
                                        @if ($child->children_count > 0)
                                            <span class="category-child-count">({{ $child->children_count }})</span>
                                        @endif
                                    @endif
                                </td>
                                <td data-col="type">Sub-Category</td>
                                <td data-col="url_key">{{ $child->url_key }}</td>
                                <td data-col="products">{{ $child->products_count }}</td>
                                <td data-col="status">
                                    <form class="status-switch-form" method="POST" action="{{ route('admin.categories.toggle-status', $child) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="status-switch" title="{{ $child->status ? 'Visible on storefront' : 'Hidden from storefront' }}">
                                            <input type="checkbox" @checked($child->status) onchange="this.form.submit()">
                                            <span class="status-switch-track" aria-hidden="true"></span>
                                            <span class="status-switch-label">{{ $child->status ? 'On' : 'Off' }}</span>
                                        </label>
                                    </form>
                                </td>
                                <td data-col="position">{{ $child->position }}</td>
                                <td data-col="updated_at">{{ $child->updated_at?->format('Y-m-d H:i') }}</td>
                                <td data-col="action">
                                    <div class="actions">
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.create', ['parent_id' => $child->id]) }}">Add Sub-Category</a>
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.edit', $child) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $child) }}" onsubmit="return confirm('Delete this sub-category?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            @foreach ($child->children as $grandChild)
                                @php
                                    $grandChildImage = $grandChild->adminImageUrl() ?: $grandChild->imageUrl();
                                    $grandChildLogo = $grandChild->adminBrandLogoUrl() ?: $grandChild->brandLogoUrl();
                                @endphp

                                <tr
                                    class="category-child-row category-grandchild-row"
                                    data-parent-id="{{ $child->id }}"
                                    data-root-parent-id="{{ $category->id }}"
                                    hidden
                                >
                                    <td data-col="image">
                                        @if ($grandChildImage)
                                            <img src="{{ $grandChildImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-col="brand_logo">
                                        @if ($grandChildLogo)
                                            <img src="{{ $grandChildLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="category-grandchild-name" data-col="name">↳ {{ $grandChild->name }}</td>
                                    <td data-col="type">Sub-Category</td>
                                    <td data-col="url_key">{{ $grandChild->url_key }}</td>
                                    <td data-col="products">{{ $grandChild->products_count }}</td>
                                    <td data-col="status">
                                        <form class="status-switch-form" method="POST" action="{{ route('admin.categories.toggle-status', $grandChild) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="status-switch" title="{{ $grandChild->status ? 'Visible on storefront' : 'Hidden from storefront' }}">
                                                <input type="checkbox" @checked($grandChild->status) onchange="this.form.submit()">
                                                <span class="status-switch-track" aria-hidden="true"></span>
                                                <span class="status-switch-label">{{ $grandChild->status ? 'On' : 'Off' }}</span>
                                            </label>
                                        </form>
                                    </td>
                                    <td data-col="position">{{ $grandChild->position }}</td>
                                    <td data-col="updated_at">{{ $grandChild->updated_at?->format('Y-m-d H:i') }}</td>
                                    <td data-col="action">
                                        <div class="actions">
                                            <a class="btn btn-outline btn-sm" href="{{ route('admin.categories.edit', $grandChild) }}">Edit</a>
                                            <form method="POST" action="{{ route('admin.categories.destroy', $grandChild) }}" onsubmit="return confirm('Delete this sub-category?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <style>
        .category-name-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            cursor: pointer;
            text-align: left;
        }

        .category-name-toggle:hover strong,
        .category-name-toggle:hover span:not(.category-child-count):not(.category-chevron) {
            text-decoration: underline;
        }

        .category-chevron {
            display: inline-block;
            width: 0.85rem;
            color: var(--muted);
            transition: transform 0.15s ease;
        }

        .category-name-toggle[aria-expanded="true"] .category-chevron {
            transform: rotate(90deg);
        }

        .category-child-count {
            color: var(--muted);
            font-weight: 500;
            font-size: 0.9em;
        }

        .category-child-name {
            padding-left: 1.5rem !important;
        }

        .category-grandchild-name {
            padding-left: 2.75rem !important;
        }

        .category-child-row {
            background: #fafafa;
        }

        .category-grandchild-row {
            background: #f5f5f5;
        }
    </style>

    @unless ($filteredMode)
    <script>
        (function () {
            const storageKey = 'admin.categories.openIds';
            const table = document.getElementById('categories-table');
            if (!table) return;

            function readOpenIds() {
                try {
                    const raw = sessionStorage.getItem(storageKey);
                    const parsed = raw ? JSON.parse(raw) : [];
                    return Array.isArray(parsed) ? parsed.map(String) : [];
                } catch (e) {
                    return [];
                }
            }

            function writeOpenIds(ids) {
                sessionStorage.setItem(storageKey, JSON.stringify(ids));
            }

            function setExpanded(parentId, expanded) {
                const id = String(parentId);
                const toggle = table.querySelector('[data-toggle-children="' + id + '"]');
                const childRows = table.querySelectorAll('.category-child-row[data-parent-id="' + id + '"]');

                if (toggle) {
                    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                }

                childRows.forEach(function (row) {
                    row.hidden = !expanded;

                    if (!expanded) {
                        const nestedId = row.getAttribute('data-category-id');
                        if (nestedId) {
                            setExpanded(nestedId, false);
                        }
                    }
                });

                const openIds = new Set(readOpenIds());
                if (expanded) {
                    openIds.add(id);
                } else {
                    openIds.delete(id);
                    childRows.forEach(function (row) {
                        const nestedId = row.getAttribute('data-category-id');
                        if (nestedId) openIds.delete(String(nestedId));
                    });
                }
                writeOpenIds(Array.from(openIds));
            }

            table.querySelectorAll('[data-toggle-children]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const parentId = button.getAttribute('data-toggle-children');
                    const isOpen = button.getAttribute('aria-expanded') === 'true';
                    setExpanded(parentId, !isOpen);
                });
            });

            readOpenIds().forEach(function (id) {
                setExpanded(id, true);
            });
        })();
    </script>
    @endunless

    @include('admin::partials.grid-toolbar-script')
@endsection
