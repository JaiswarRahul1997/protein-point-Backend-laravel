@extends('admin::layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Order {{ $order->order_number }}</h1>
            <p class="page-lead">Placed {{ $order->created_at?->format('Y-m-d H:i') }} · {{ $order->statusLabel() }}</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.orders.index') }}">Back</a>
    </header>

    <section class="form-panel">
        <h2 class="section-title">Customer Details</h2>
        <div class="form-grid">
            <div class="form-field">
                <span class="form-label">Name</span>
                <p>{{ $order->customer_name }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Email</span>
                <p>{{ $order->customer_email }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Phone</span>
                <p>{{ $order->customer_phone ?: '—' }}</p>
            </div>
            <div class="form-field">
                <span class="form-label">Total</span>
                <p>₹{{ number_format((float) $order->total, 2) }}</p>
            </div>
            <div class="form-field full">
                <span class="form-label">Shipping Address</span>
                <p>{{ $order->shipping_address }}</p>
                <p>
                    {{ collect([$order->city, $order->state, $order->pincode])->filter()->join(', ') ?: '' }}
                </p>
            </div>
            @if ($order->notes)
                <div class="form-field full">
                    <span class="form-label">Notes</span>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
        </div>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Update Status</h2>
        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="actions">
            @csrf
            @method('PUT')
            <select class="form-select" name="status" style="max-width:220px">
                @foreach (\Admin\Models\Order::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Save Status</button>
        </form>
    </section>

    <section class="form-panel">
        <h2 class="section-title">Products</h2>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                @if ($item->thumbnailUrl())
                                    <img class="thumb" src="{{ $item->thumbnailUrl() }}" alt="">
                                @else
                                    <span class="thumb-placeholder">N/A</span>
                                @endif
                            </td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->product_sku ?: '—' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>₹{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
