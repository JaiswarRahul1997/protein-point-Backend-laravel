@extends('admin::layouts.app')

@section('title', 'Customers')

@php
    $activeFilterCount = collect($filters)->filter(fn ($value) => filled($value))->count();
    $clearUrl = route('admin.customers.index');
@endphp

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Customers</h1>
            <p class="page-lead">View and manage storefront customers.</p>
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
        <form method="GET" action="{{ route('admin.customers.index') }}">
            <div class="grid-filter-fields">
                <div class="grid-filter-field">
                    <label>ID</label>
                    <div class="grid-filter-range">
                        <input class="form-input" type="number" name="id_from" placeholder="from" value="{{ $filters['id_from'] }}">
                        <input class="form-input" type="number" name="id_to" placeholder="to" value="{{ $filters['id_to'] }}">
                    </div>
                </div>
                <div class="grid-filter-field">
                    <label for="filter-customer-name">Name</label>
                    <input class="form-input" id="filter-customer-name" type="text" name="name" value="{{ $filters['name'] }}">
                </div>
                <div class="grid-filter-field">
                    <label for="filter-customer-email">Email</label>
                    <input class="form-input" id="filter-customer-email" type="text" name="email" value="{{ $filters['email'] }}">
                </div>
                <div class="grid-filter-field">
                    <label for="filter-customer-phone">Phone</label>
                    <input class="form-input" id="filter-customer-phone" type="text" name="phone" value="{{ $filters['phone'] }}">
                </div>
                <div class="grid-filter-field">
                    <label for="filter-customer-status">Status</label>
                    <select class="form-input" id="filter-customer-status" name="status">
                        <option value="">Any</option>
                        @foreach (\Admin\Models\Customer::STATUSES as $slug => $label)
                            <option value="{{ $slug }}" @selected($filters['status'] === $slug)>{{ $label }}</option>
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

    @if ($customers->isEmpty())
        <div class="empty-state">
            @if ($hasActiveFilters)
                <p>No customers match the selected filters.</p>
                <a class="btn btn-outline" href="{{ $clearUrl }}">Clear Filters</a>
            @else
                <p>No customers found yet. Customers are created automatically from checkout orders.</p>
            @endif
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table" id="customers-table" data-column-storage="admin.customers.columns">
                <thead>
                    <tr>
                        <th data-col="id">ID</th>
                        <th data-col="name">Name</th>
                        <th data-col="email">Email</th>
                        <th data-col="phone">Phone</th>
                        <th data-col="orders">Orders</th>
                        <th data-col="spent">Total Spent</th>
                        <th data-col="status">Status</th>
                        <th data-col="updated_at">Last Updated At</th>
                        <th data-col="action" data-col-locked="1">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td data-col="id">{{ $customer->id }}</td>
                            <td data-col="name">{{ $customer->name }}</td>
                            <td data-col="email">{{ $customer->email }}</td>
                            <td data-col="phone">{{ $customer->phone ?: '—' }}</td>
                            <td data-col="orders">{{ $customer->orders_count }}</td>
                            <td data-col="spent">₹{{ number_format((float) ($customer->orders_sum_total ?? 0), 2) }}</td>
                            <td data-col="status">{{ $customer->statusLabel() }}</td>
                            <td data-col="updated_at">{{ $customer->updated_at?->format('Y-m-d H:i') }}</td>
                            <td data-col="action">
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.customers.show', $customer) }}">View</a>
                                    @if ($customer->isEnabled())
                                        <form method="POST" action="{{ route('admin.customers.login-as', $customer) }}" target="_blank">
                                            @csrf
                                            <button class="btn btn-sm" type="submit">Login as Customer</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete customer {{ addslashes($customer->name) }}? Their orders will be kept but unlinked.')">
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
            @if ($customers->hasPages())
                <div class="actions">
                    @if ($customers->onFirstPage())
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Previous</span>
                    @else
                        <a class="btn btn-outline btn-sm" href="{{ $customers->previousPageUrl() }}">Previous</a>
                    @endif
                    <span style="font-size:0.85rem;align-self:center">Page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}</span>
                    @if ($customers->hasMorePages())
                        <a class="btn btn-outline btn-sm" href="{{ $customers->nextPageUrl() }}">Next</a>
                    @else
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @include('admin::partials.grid-toolbar-script')
@endsection
