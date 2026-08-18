@extends('admin::layouts.app')

@section('title', $campaign->name)

@section('content')
    <header class="page-header">
        <div>
            <p class="form-hint" style="margin:0 0 0.35rem">
                <a href="{{ route('admin.newsletter.campaigns.index') }}">Campaigns</a> › {{ $campaign->name }}
            </p>
            <h1 class="page-title">{{ $campaign->name }}</h1>
            <p class="page-lead">{{ $campaign->statusLabel() }}</p>
        </div>
        <div class="actions">
            @if ($campaign->isEditable())
                <a class="btn btn-outline" href="{{ route('admin.newsletter.campaigns.edit', $campaign) }}">Edit</a>
            @endif
            @if (in_array($campaign->status, ['draft', 'scheduled']))
                <form method="POST" action="{{ route('admin.newsletter.campaigns.send', $campaign) }}">
                    @csrf
                    <button class="btn" type="submit" onclick="return confirm('Send this campaign now?')">Send now</button>
                </form>
            @endif
        </div>
    </header>

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <section class="form-panel">
        <h2 class="section-title">Reporting</h2>
        <div class="form-grid">
            <div class="form-field"><span class="form-label">Total recipients</span><p>{{ $stats['total'] }}</p></div>
            <div class="form-field"><span class="form-label">Email recipients</span><p>{{ $stats['email'] }}</p></div>
            <div class="form-field"><span class="form-label">WhatsApp recipients</span><p>{{ $stats['whatsapp'] }}</p></div>
            <div class="form-field"><span class="form-label">Pending</span><p>{{ $stats['pending'] }}</p></div>
            <div class="form-field"><span class="form-label">Sent</span><p>{{ $stats['sent'] }}</p></div>
            <div class="form-field"><span class="form-label">Delivered</span><p>{{ $stats['delivered'] }}</p></div>
            <div class="form-field"><span class="form-label">Failed</span><p>{{ $stats['failed'] }}</p></div>
            <div class="form-field"><span class="form-label">Unsubscribed</span><p>{{ $stats['unsubscribed'] }}</p></div>
        </div>
    </section>

    @if ($campaign->isEditable())
        <section class="form-panel">
            <h2 class="section-title">Schedule</h2>
            <form method="POST" action="{{ route('admin.newsletter.campaigns.schedule', $campaign) }}" class="actions">
                @csrf
                <input class="form-input" type="datetime-local" name="scheduled_at" required style="max-width:260px">
                <button class="btn btn-outline" type="submit">Schedule</button>
            </form>
            <form method="POST" action="{{ route('admin.newsletter.campaigns.cancel', $campaign) }}" style="margin-top:0.75rem">
                @csrf
                <button class="btn btn-outline btn-sm" type="submit">Cancel campaign</button>
            </form>
        </section>
    @endif

    <section class="form-panel">
        <h2 class="section-title">Content</h2>
        <div class="form-grid">
            <div class="form-field full"><span class="form-label">Subject</span><p>{{ $campaign->subject ?: '—' }}</p></div>
            <div class="form-field full"><span class="form-label">WhatsApp template</span><p>{{ $campaign->whatsapp_template_name ?: '—' }}</p></div>
        </div>
    </section>
@endsection
