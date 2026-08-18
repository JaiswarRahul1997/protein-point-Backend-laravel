@extends('admin::layouts.app')

@section('title', $mode === 'create' ? 'Create Campaign' : 'Edit Campaign')

@section('content')
    <header class="page-header">
        <div>
            <p class="form-hint" style="margin:0 0 0.35rem">
                <a href="{{ route('admin.newsletter.campaigns.index') }}">Campaigns</a> › {{ $mode === 'create' ? 'Create' : $campaign->name }}
            </p>
            <h1 class="page-title">{{ $mode === 'create' ? 'Create Campaign' : 'Edit Campaign' }}</h1>
        </div>
        <a class="btn btn-outline" href="{{ route('admin.newsletter.campaigns.index') }}">Back</a>
    </header>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.newsletter.campaigns.store') : route('admin.newsletter.campaigns.update', $campaign) }}" class="form-panel">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="form-grid">
            <div class="form-field full">
                <label class="form-label" for="name">Campaign name</label>
                <input class="form-input" id="name" name="name" value="{{ old('name', $campaign->name) }}" required>
            </div>

            <div class="form-field">
                <label class="form-label">Channels</label>
                <label><input type="checkbox" name="channel_email" value="1" @checked(old('channel_email', $campaign->channel_email))> Email</label>
                <label style="margin-left:1rem"><input type="checkbox" name="channel_whatsapp" value="1" @checked(old('channel_whatsapp', $campaign->channel_whatsapp))> WhatsApp</label>
            </div>

            <div class="form-field">
                <label class="form-label" for="audience">Audience</label>
                <select class="form-input" id="audience" name="audience">
                    @foreach (\Admin\Models\NewsletterCampaign::AUDIENCES as $value => $label)
                        <option value="{{ $value }}" @selected(old('audience', $campaign->audience) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field full">
                <label class="form-label" for="subject">Email subject</label>
                <input class="form-input" id="subject" name="subject" value="{{ old('subject', $campaign->subject) }}">
            </div>

            <div class="form-field full">
                <label class="form-label" for="email_html">Email HTML content</label>
                <textarea class="form-input" id="email_html" name="email_html" rows="8">{{ old('email_html', $campaign->email_html) }}</textarea>
                <p class="form-hint">Use @{{name}}, @{{email}}, and @{{unsubscribe_url}} for personalization.</p>
            </div>

            <div class="form-field full">
                <label class="form-label" for="email_text">Email plain text (optional)</label>
                <textarea class="form-input" id="email_text" name="email_text" rows="4">{{ old('email_text', $campaign->email_text) }}</textarea>
            </div>

            <div class="form-field full">
                <label class="form-label" for="whatsapp_template_name">WhatsApp template name</label>
                <input class="form-input" id="whatsapp_template_name" name="whatsapp_template_name" value="{{ old('whatsapp_template_name', $campaign->whatsapp_template_name) }}">
            </div>

            <div class="form-field full">
                <label class="form-label" for="whatsapp_message">WhatsApp message preview / notes</label>
                <textarea class="form-input" id="whatsapp_message" name="whatsapp_message" rows="3">{{ old('whatsapp_message', $campaign->whatsapp_message) }}</textarea>
            </div>
        </div>

        <div class="actions" style="margin-top:1rem">
            <button class="btn" type="submit">Save draft</button>
        </div>
    </form>
@endsection
