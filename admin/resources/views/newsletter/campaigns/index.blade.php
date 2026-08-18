@extends('admin::layouts.app')

@section('title', 'Newsletter Campaigns')

@section('content')
    <header class="page-header">
        <div>
            <h1 class="page-title">Newsletter Campaigns</h1>
            <p class="page-lead">Create and send email and WhatsApp marketing campaigns.</p>
        </div>
        <div class="actions">
            <a class="btn btn-outline" href="{{ route('admin.newsletter.subscribers.index') }}">Subscribers</a>
            <a class="btn" href="{{ route('admin.newsletter.campaigns.create') }}">Create Campaign</a>
        </div>
    </header>

    @if ($campaigns->isEmpty())
        <div class="empty-state">
            <p>No campaigns yet.</p>
            <a class="btn" href="{{ route('admin.newsletter.campaigns.create') }}">Create your first campaign</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Channels</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->id }}</td>
                            <td>{{ $campaign->name }}</td>
                            <td>
                                @if ($campaign->channel_email) Email @endif
                                @if ($campaign->channel_whatsapp) WhatsApp @endif
                            </td>
                            <td>{{ $campaign->statusLabel() }}</td>
                            <td>{{ $campaign->scheduled_at?->format('Y-m-d H:i') ?: '—' }}</td>
                            <td>{{ $campaign->updated_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <div class="actions">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.newsletter.campaigns.show', $campaign) }}">View</a>
                                    @if ($campaign->isEditable())
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.newsletter.campaigns.edit', $campaign) }}">Edit</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">
            @if ($campaigns->hasPages())
                <div class="actions">
                    @if ($campaigns->onFirstPage())
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Previous</span>
                    @else
                        <a class="btn btn-outline btn-sm" href="{{ $campaigns->previousPageUrl() }}">Previous</a>
                    @endif
                    <span style="font-size:0.85rem;align-self:center">Page {{ $campaigns->currentPage() }} of {{ $campaigns->lastPage() }}</span>
                    @if ($campaigns->hasMorePages())
                        <a class="btn btn-outline btn-sm" href="{{ $campaigns->nextPageUrl() }}">Next</a>
                    @else
                        <span class="btn btn-outline btn-sm" style="opacity:0.4;pointer-events:none">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
@endsection
