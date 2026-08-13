@extends('admin::layouts.app')

@section('title', 'All Products')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">All Products</h1>
            <p class="page-lead">Choose a product type to view and manage its products. {{ $totalProducts }} products in catalog.</p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.products.csv-template') }}">Download CSV Template</a>
            <a class="btn btn-outline" href="{{ route('admin.products.export-csv') }}">Download CSV</a>
            <form method="POST" action="{{ route('admin.products.seed-dummy') }}" onsubmit="return confirm('Add 10 dummy products for each product type (50 total)?')">
                @csrf
                <button class="btn btn-outline" type="submit">Seed Dummy Products</button>
            </form>
            <a class="btn" href="{{ route('admin.products.create') }}">Add Product</a>
        </div>
    </header>

    <section class="form-panel" style="margin-bottom:1.5rem">
        <h2 class="section-title" style="margin-bottom:0.75rem">Upload Products CSV</h2>
        <form method="POST" action="{{ route('admin.products.import-csv') }}" enctype="multipart/form-data" class="actions" style="flex-wrap:wrap">
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
            <a class="btn btn-outline" href="{{ route('admin.products.csv-template') }}">Download template</a>
            <a class="btn btn-outline" href="{{ route('admin.products.export-csv') }}">Download current CSV</a>
        </form>
        <p class="form-hint" style="margin-top:0.75rem">
            Columns: name, sku, type, attribute_set, stock_status, price, quantity, visibility, status, url_key, brand, sizes, flavors, description, categories.
            Matching is by SKU.
        </p>
        @error('csv_file')
            <p class="form-error" style="margin-top:0.75rem">{{ $message }}</p>
        @enderror
    </section>

    <div class="type-grid">
        <a class="type-card type-card-all" href="{{ route('admin.products.index', ['type' => 'all']) }}">
            <div class="type-card-body">
                <h2>All Products</h2>
                <p>{{ $totalProducts }} {{ \Illuminate\Support\Str::plural('product', $totalProducts) }}</p>
            </div>
            <span class="type-card-action">View all products →</span>
        </a>

        @foreach ($types as $typeItem)
            <a class="type-card" href="{{ route('admin.products.index', ['type' => $typeItem['slug']]) }}">
                <div class="type-card-body">
                    <h2>{{ $typeItem['label'] }}</h2>
                    <p>{{ $typeItem['count'] }} {{ \Illuminate\Support\Str::plural('product', $typeItem['count']) }}</p>
                </div>
                <span class="type-card-action">View products →</span>
            </a>
        @endforeach
    </div>

    <style>
        .type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
        }

        .type-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1rem;
            min-height: 140px;
            padding: 1.25rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--white);
            color: inherit;
            text-decoration: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .type-card:hover {
            border-color: var(--black);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .type-card h2 {
            margin: 0 0 0.35rem;
            font-size: 1.15rem;
        }

        .type-card p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .type-card-all {
            border-color: var(--black);
            background: var(--surface);
        }

        .type-card-action {
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
@endsection
