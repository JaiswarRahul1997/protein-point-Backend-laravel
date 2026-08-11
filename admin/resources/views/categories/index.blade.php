@extends('admin::layouts.app')

@section('title', 'Categories')

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

    @if ($categories->isEmpty())
        <div class="empty-state">
            <p>No categories found.</p>
            <a class="btn" href="{{ route('admin.categories.create') }}">Create your first category</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table" id="categories-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Brand Logo</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>URL Key</th>
                        <th>Products</th>
                        <th>Show on Frontend</th>
                        <th>Position</th>
                        <th>Last Updated At</th>
                        <th>Action</th>
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
                            <td>
                                @if ($parentImage)
                                    <img src="{{ $parentImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($parentLogo)
                                    <img src="{{ $parentLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                @else
                                    —
                                @endif
                            </td>
                            <td>
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
                            <td>Category</td>
                            <td>{{ $category->url_key }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
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
                            <td>{{ $category->position }}</td>
                            <td>{{ $category->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
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
                                <td>
                                    @if ($childImage)
                                        <img src="{{ $childImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($childLogo)
                                        <img src="{{ $childLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="category-child-name">
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
                                <td>Sub-Category</td>
                                <td>{{ $child->url_key }}</td>
                                <td>{{ $child->products_count }}</td>
                                <td>
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
                                <td>{{ $child->position }}</td>
                                <td>{{ $child->updated_at?->format('Y-m-d H:i') }}</td>
                                <td>
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
                                    <td>
                                        @if ($grandChildImage)
                                            <img src="{{ $grandChildImage }}" alt="" style="width:40px;height:40px;object-fit:cover;border:1px solid #ddd">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($grandChildLogo)
                                            <img src="{{ $grandChildLogo }}" alt="" style="width:40px;height:40px;object-fit:contain;border:1px solid #ddd;background:#fff">
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="category-grandchild-name">↳ {{ $grandChild->name }}</td>
                                    <td>Sub-Category</td>
                                    <td>{{ $grandChild->url_key }}</td>
                                    <td>{{ $grandChild->products_count }}</td>
                                    <td>
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
                                    <td>{{ $grandChild->position }}</td>
                                    <td>{{ $grandChild->updated_at?->format('Y-m-d H:i') }}</td>
                                    <td>
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
@endsection
