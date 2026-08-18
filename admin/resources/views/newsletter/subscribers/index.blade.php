@extends('admin::layouts.app')

@section('title', 'Newsletter Subscribers')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Newsletter Subscribers</h1>
            <p class="page-lead">Email and WhatsApp marketing consent per subscriber.</p>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.newsletter.campaigns.index') }}">Campaigns</a>
    </header>

    <form method="GET" class="form-panel" style="margin-bottom:1rem">
        <div class="form-grid">
            <div class="form-field">
                <label class="form-label">Email</label>
                <input class="form-input" name="email" value="{{ $filters['email'] ?? '' }}">
            </div>
            <div class="form-field">
                <label class="form-label">Phone</label>
                <input class="form-input" name="phone" value="{{ $filters['phone'] ?? '' }}">
            </div>
            <div class="form-field">
                <label class="form-label">Email status</label>
                <select class="form-input" name="email_status">
                    <option value="">Any</option>
                    @foreach (['pending', 'subscribed', 'unsubscribed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['email_status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label class="form-label">WhatsApp status</label>
                <select class="form-input" name="whatsapp_status">
                    <option value="">Any</option>
                    @foreach (['pending', 'subscribed', 'unsubscribed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['whatsapp_status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="actions" style="margin-top:0.75rem">
            <button class="btn" type="submit">Apply filters</button>
        </div>
    </form>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Email status</th>
                    <th>WhatsApp status</th>
                    <th>Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscribers as $subscriber)
                    <tr>
                        <td>{{ $subscriber->id }}</td>
                        <td>{{ $subscriber->name ?: '—' }}</td>
                        <td>{{ $subscriber->email ?: '—' }}</td>
                        <td>{{ $subscriber->phone ?: '—' }}</td>
                        <td>{{ ucfirst($subscriber->email_status) }}</td>
                        <td>{{ ucfirst($subscriber->whatsapp_status) }}</td>
                        <td>{{ $subscriber->updated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No subscribers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
