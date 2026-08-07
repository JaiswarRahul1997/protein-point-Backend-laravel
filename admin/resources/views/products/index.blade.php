@extends('admin::layouts.app')

@section('title', $typeLabel)

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">{{ $typeLabel }}</h1>
            <p class="page-lead">Manage catalog products{{ $type ? ' of type '.$typeLabel : '' }}.</p>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('admin.products.seed-dummy') }}" onsubmit="return confirm('Add 10 dummy products for each product type (50 total)?')">
                @csrf
                <button class="btn btn-outline" type="submit">Seed Dummy Products</button>
            </form>
            <a class="btn" href="{{ route('admin.products.create', $type ? ['type' => $type] : []) }}">Add Product</a>
        </div>
    </header>

    @if ($products->isEmpty())
        <div class="empty-state">
            <p>No products found.</p>
            <a class="btn" href="{{ route('admin.products.create', $type ? ['type' => $type] : []) }}">Create your first product</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thumbnail</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Attribute Set</th>
                        <th>Stock Status</th>
                        <th>Categories</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Visibility</th>
                        <th>Status</th>
                        <th>URL Key</th>
                        <th>Brand</th>
                        <th>Last Updated At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
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
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->typeLabel() }}</td>
                            <td>{{ $product->attribute_set }}</td>
                            <td>{{ $product->stockStatusLabel() }}</td>
                            <td>{{ $product->categories->pluck('name')->join(', ') ?: '—' }}</td>
                            <td>{{ $product->sku }}</td>
                            <td>{{ number_format((float) $product->price, 2) }}</td>
                            <td>{{ $product->quantity }}</td>
                            <td>{{ $product->visibilityLabel() }}</td>
                            <td>{{ $product->statusLabel() }}</td>
                            <td>{{ $product->url_key }}</td>
                            <td>{{ $product->brand ?: '—' }}</td>
                            <td>{{ $product->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
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
@endsection
