@extends('admin::layouts.app')

@section('title', 'Brands')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Brands</h1>
            <p class="page-lead">Each brand has a logo, description, and associated products for the storefront Brands directory.</p>
        </div>
        <a class="btn" href="{{ route('admin.brands.create') }}">Add Brand</a>
    </header>

    @if ($brands->isEmpty())
        <div class="empty-state">
            <p>No brands yet.</p>
            <a class="btn" href="{{ route('admin.brands.create') }}">Create your first brand</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Name</th>
                        <th>URL Key</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Position</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($brands as $brand)
                        @php $logo = $brand->adminBrandLogoUrl() ?: $brand->brandLogoUrl(); @endphp
                        <tr>
                            <td>{{ $brand->id }}</td>
                            <td>
                                @if ($logo)
                                    <img class="thumb" src="{{ $logo }}" alt="{{ $brand->name }}">
                                @else
                                    <span class="thumb-placeholder">N/A</span>
                                @endif
                            </td>
                            <td>{{ $brand->name }}</td>
                            <td><code>{{ $brand->url_key }}</code></td>
                            <td>{{ $brand->products_count }}</td>
                            <td>
                                <form class="status-switch-form" method="POST" action="{{ route('admin.brands.toggle-status', $brand) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="status-switch">
                                        <input type="checkbox" onchange="this.form.submit()" @checked($brand->status)>
                                        <span class="status-switch-track" aria-hidden="true"></span>
                                        <span class="status-switch-label">{{ $brand->status ? 'On' : 'Off' }}</span>
                                    </label>
                                </form>
                            </td>
                            <td>{{ $brand->position }}</td>
                            <td>{{ $brand->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.brands.edit', $brand) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand? Products will not be deleted.')">
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
    @endif
@endsection
