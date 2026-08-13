@extends('admin::layouts.app')

@section('title', 'Product Attributes')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Product Attributes</h1>
            <p class="page-lead">Create Magento-style attributes (e.g. Size, Flavor). Assign options on each product to show dropdowns on the storefront.</p>
        </div>
        <a class="btn" href="{{ route('admin.attributes.create') }}">Add Attribute</a>
    </header>

    @if ($attributes->isEmpty())
        <div class="empty-state">
            <p>No attributes yet.</p>
            <a class="btn" href="{{ route('admin.attributes.create') }}">Create Size or Flavor</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>Code</th>
                        <th>Input</th>
                        <th>Options</th>
                        <th>Required</th>
                        <th>Frontend</th>
                        <th>Sort</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attributes as $attribute)
                        <tr>
                            <td>{{ $attribute->id }}</td>
                            <td>{{ $attribute->label }}</td>
                            <td><code>{{ $attribute->code }}</code></td>
                            <td>{{ $attribute->inputTypeLabel() }}</td>
                            <td>{{ $attribute->options_count }}</td>
                            <td>{{ $attribute->is_required ? 'Yes' : 'No' }}</td>
                            <td>{{ $attribute->is_visible_on_frontend ? 'Visible' : 'Hidden' }}</td>
                            <td>{{ $attribute->sort_order }}</td>
                            <td>{{ $attribute->statusLabel() }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.attributes.edit', $attribute) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.attributes.destroy', $attribute) }}" onsubmit="return confirm('Delete this attribute and its options?')">
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
