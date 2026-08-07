@extends('admin::layouts.app')

@section('title', 'Menus')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Menu Management</h1>
            <p class="page-lead">Create and manage storefront navigation links (header and footer).</p>
        </div>
        <a class="btn" href="{{ route('admin.menus.create') }}">Add Menu Item</a>
    </header>

    <div class="actions" style="margin-bottom:1rem">
        <a class="btn btn-sm {{ $location === null ? '' : 'btn-outline' }}" href="{{ route('admin.menus.index') }}">All</a>
        @foreach (\Admin\Models\Menu::LOCATIONS as $value => $label)
            <a class="btn btn-sm {{ $location === $value ? '' : 'btn-outline' }}" href="{{ route('admin.menus.index', ['location' => $value]) }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($menus->isEmpty())
        <div class="empty-state">
            <p>No menu items yet.</p>
            <a class="btn" href="{{ route('admin.menus.create') }}">Create your first menu item</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>URL</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($menus as $menu)
                        <tr>
                            <td>{{ $menu->id }}</td>
                            <td>{{ $menu->label }}</td>
                            <td><code>{{ $menu->url }}</code></td>
                            <td>{{ $menu->locationLabel() }}</td>
                            <td>{{ $menu->statusLabel() }}</td>
                            <td>{{ $menu->sort_order }}</td>
                            <td>{{ $menu->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.menus.edit', $menu) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('Delete this menu item?')">
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
