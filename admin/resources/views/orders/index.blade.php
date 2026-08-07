@extends('admin::layouts.app')

@section('title', 'Orders')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Orders</h1>
            <p class="page-lead">View and manage customer orders from the storefront.</p>
        </div>
    </header>

    <div class="actions" style="margin-bottom:1.25rem">
        <a class="btn {{ $status === null ? '' : 'btn-outline' }} btn-sm" href="{{ route('admin.orders.index') }}">All</a>
        @foreach (\Admin\Models\Order::STATUSES as $value => $label)
            <a class="btn {{ $status === $value ? '' : 'btn-outline' }} btn-sm" href="{{ route('admin.orders.index', ['status' => $value]) }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($orders->isEmpty())
        <div class="empty-state">
            <p>No orders found.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Placed At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->customer_email }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td>₹{{ number_format((float) $order->total, 2) }}</td>
                            <td>{{ $order->statusLabel() }}</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.orders.show', $order) }}">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            @if ($orders->hasPages())
                <div class="actions">
                    @if ($orders->onFirstPage())
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Previous</span>
                    @else
                        <a class="btn btn-outline btn-sm" href="{{ $orders->previousPageUrl() }}">Previous</a>
                    @endif
                    <span style="font-size:0.85rem;align-self:center">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
                    @if ($orders->hasMorePages())
                        <a class="btn btn-outline btn-sm" href="{{ $orders->nextPageUrl() }}">Next</a>
                    @else
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
@endsection
