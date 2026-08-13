@extends('admin::layouts.app')

@section('title', 'Customer '.$customer->name)

@section('content')
    <header class="page-header">
        <div>
            <p class="form-hint" style="margin:0 0 0.35rem">
                <a href="{{ route('admin.customers.index') }}">All Customers</a> › {{ $customer->name }}
            </p>
            <h1 class="page-title">{{ $customer->name }}</h1>
            <p class="page-lead">{{ $customer->email }} · {{ $customer->statusLabel() }}</p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.customers.index') }}">Back</a>
            @if ($customer->isEnabled())
                <form method="POST" action="{{ route('admin.customers.login-as', $customer) }}" target="_blank">
                    @csrf
                    <button class="btn" type="submit">Login as Customer</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete customer {{ addslashes($customer->name) }}? Their orders will be kept but unlinked.')">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline" type="submit">Delete</button>
            </form>
        </div>
    </header>

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <section class="form-panel">
        <h2 class="section-title">Account Information</h2>
        <div class="form-grid">
            <div class="form-field">
                <span class="form-label">Customer ID</span>
                <p>{{ $customer->id }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Name</span>
                <p>{{ $customer->name }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Email</span>
                <p>{{ $customer->email }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Phone</span>
                <p>{{ $customer->phone ?: '—' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Status</span>
                <p>{{ $customer->statusLabel() }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Last Login</span>
                <p>{{ $customer->last_login_at?->format('Y-m-d H:i') ?: 'Never' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Created At</span>
                <p>{{ $customer->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Updated At</span>
                <p>{{ $customer->updated_at?->format('Y-m-d H:i') }}</p>
            </div>
        </div>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Default Shipping Address</h2>
        <div class="form-grid">
            <div class="form-field full">
                <span class="form-label">Address</span>
                <p>{{ $customer->shipping_address ?: '—' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">City</span>
                <p>{{ $customer->city ?: '—' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">State</span>
                <p>{{ $customer->state ?: '—' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Pincode</span>
                <p>{{ $customer->pincode ?: '—' }}</p>
            </div>
        </div>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Order Summary</h2>
        <div class="form-grid">
            <div class="form-field">
                <span class="form-label">Total Orders</span>
                <p>{{ $stats['orders_count'] }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Lifetime Spent</span>
                <p>₹{{ number_format($stats['total_spent'], 2) }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Last Order</span>
                <p>{{ $stats['last_order_at']?->format('Y-m-d H:i') ?: '—' }}</p>
            </div>
        </div>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Account Status</h2>
        <form method="POST" action="{{ route('admin.customers.status', $customer) }}" class="actions">
            @csrf
            @method('PUT')
            <select class="form-select" name="status" style="max-width:220px">
                @foreach (\Admin\Models\Customer::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($customer->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Save Status</button>
        </form>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Orders</h2>
        @if ($customer->orders->isEmpty())
            <p class="form-hint">No orders for this customer yet.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customer->orders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $order->statusLabel() }}</td>
                                <td>{{ $order->items_count }}</td>
                                <td>₹{{ number_format((float) $order->total, 2) }}</td>
                                <td>
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $order) }}">View Order</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
