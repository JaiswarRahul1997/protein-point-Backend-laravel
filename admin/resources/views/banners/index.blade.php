@extends('admin::layouts.app')

@section('title', 'Banners')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Homepage Banners</h1>
            <p class="page-lead">Manage homepage banners: large left + two stacked right banners.</p>
        </div>
        <a class="btn" href="{{ route('admin.banners.create') }}">Add Banner</a>
    </header>

    @if ($banners->isEmpty())
        <div class="empty-state">
            <p>No banners yet.</p>
            <a class="btn" href="{{ route('admin.banners.create') }}">Upload your first banner</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Slot</th>
                        <th>Status</th>
                        <th>Sort</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($banners as $banner)
                        <tr>
                            <td>{{ $banner->id }}</td>
                            <td>
                                @if ($banner->imageUrl())
                                    <img class="thumb" src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}">
                                @else
                                    <span class="thumb-placeholder">N/A</span>
                                @endif
                            </td>
                            <td>{{ $banner->title }}</td>
                            <td>{{ $banner->slotLabel() }}</td>
                            <td>{{ $banner->statusLabel() }}</td>
                            <td>{{ $banner->sort_order }}</td>
                            <td>{{ $banner->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.banners.edit', $banner) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
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
