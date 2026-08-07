@extends('admin::layouts.app')

@section('title', 'Categories')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Categories</h1>
            <p class="page-lead">Add categories and nest sub-categories under them.</p>
        </div>
        <a class="btn" href="{{ route('admin.categories.create') }}">Add Category</a>
    </header>

    @if ($categories->isEmpty())
        <div class="empty-state">
            <p>No categories found.</p>
            <a class="btn" href="{{ route('admin.categories.create') }}">Create your first category</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>URL Key</th>
                        <th>Sub-Categories</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Position</th>
                        <th>Last Updated At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>Category</td>
                            <td>{{ $category->url_key }}</td>
                            <td>{{ $category->children_count }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>{{ $category->status ? 'Enabled' : 'Disabled' }}</td>
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
                            <tr>
                                <td>{{ $child->id }}</td>
                                <td style="padding-left: 2rem;">↳ {{ $child->name }}</td>
                                <td>Sub-Category</td>
                                <td>{{ $child->url_key }}</td>
                                <td>{{ $child->children_count }}</td>
                                <td>{{ $child->products_count }}</td>
                                <td>{{ $child->status ? 'Enabled' : 'Disabled' }}</td>
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
                                <tr>
                                    <td>{{ $grandChild->id }}</td>
                                    <td style="padding-left: 3.25rem;">↳ {{ $grandChild->name }}</td>
                                    <td>Sub-Category</td>
                                    <td>{{ $grandChild->url_key }}</td>
                                    <td>—</td>
                                    <td>{{ $grandChild->products_count }}</td>
                                    <td>{{ $grandChild->status ? 'Enabled' : 'Disabled' }}</td>
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
@endsection
