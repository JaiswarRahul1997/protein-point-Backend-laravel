<?php

namespace Admin\Http\Controllers;

use Admin\Models\NewsletterCampaign;
use Admin\Models\NewsletterSubscriber;
use App\Http\Controllers\Controller;
use App\Services\Newsletter\NewsletterCampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterCampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = NewsletterCampaign::query()->latest('updated_at')->paginate(20);

        return view('admin::newsletter.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('admin::newsletter.campaigns.form', [
            'campaign' => new NewsletterCampaign([
                'status' => NewsletterCampaign::STATUS_DRAFT,
                'audience' => 'both_subscribers',
            ]),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = session('admin_name');
        $data['status'] = NewsletterCampaign::STATUS_DRAFT;

        NewsletterCampaign::create($data);

        return redirect()->route('admin.newsletter.campaigns.index')
            ->with('success', 'Campaign saved as draft.');
    }

    public function show(NewsletterCampaign $campaign, NewsletterCampaignService $service): View
    {
        $stats = $service->stats($campaign);

        return view('admin::newsletter.campaigns.show', compact('campaign', 'stats'));
    }

    public function edit(NewsletterCampaign $campaign): View|RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return redirect()->route('admin.newsletter.campaigns.show', $campaign)
                ->withErrors(['campaign' => 'This campaign can no longer be edited.']);
        }

        return view('admin::newsletter.campaigns.form', [
            'campaign' => $campaign,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, NewsletterCampaign $campaign): RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return back()->withErrors(['campaign' => 'This campaign can no longer be edited.']);
        }

        $campaign->update($this->validated($request));

        return redirect()->route('admin.newsletter.campaigns.show', $campaign)
            ->with('success', 'Campaign updated.');
    }

    public function destroy(NewsletterCampaign $campaign): RedirectResponse
    {
        if ($campaign->status === NewsletterCampaign::STATUS_PROCESSING) {
            return back()->withErrors(['campaign' => 'Cannot delete a campaign while it is processing.']);
        }

        $campaign->delete();

        return redirect()->route('admin.newsletter.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function schedule(Request $request, NewsletterCampaign $campaign): RedirectResponse
    {
        if (! $campaign->isEditable()) {
            return back()->withErrors(['campaign' => 'Campaign cannot be scheduled in its current status.']);
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $campaign->update([
            'scheduled_at' => $data['scheduled_at'],
            'status' => NewsletterCampaign::STATUS_SCHEDULED,
        ]);

        return back()->with('success', 'Campaign scheduled.');
    }

    public function sendNow(NewsletterCampaign $campaign, NewsletterCampaignService $service): RedirectResponse
    {
        try {
            $service->dispatch($campaign->fresh());
        } catch (\Throwable $e) {
            return back()->withErrors(['campaign' => $e->getMessage()]);
        }

        return redirect()->route('admin.newsletter.campaigns.show', $campaign)
            ->with('success', 'Campaign dispatch started.');
    }

    public function cancel(NewsletterCampaign $campaign): RedirectResponse
    {
        if (! in_array($campaign->status, [NewsletterCampaign::STATUS_DRAFT, NewsletterCampaign::STATUS_SCHEDULED], true)) {
            return back()->withErrors(['campaign' => 'Only draft or scheduled campaigns can be cancelled.']);
        }

        $campaign->update(['status' => NewsletterCampaign::STATUS_CANCELLED]);

        return back()->with('success', 'Campaign cancelled.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'email_html' => ['nullable', 'string'],
            'email_text' => ['nullable', 'string'],
            'whatsapp_template_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_template_params' => ['nullable', 'array'],
            'whatsapp_template_params.*' => ['nullable', 'string', 'max:255'],
            'whatsapp_message' => ['nullable', 'string'],
            'channel_email' => ['nullable', 'boolean'],
            'channel_whatsapp' => ['nullable', 'boolean'],
            'audience' => ['required', 'in:'.implode(',', array_keys(NewsletterCampaign::AUDIENCES))],
        ]);

        $data['channel_email'] = $request->boolean('channel_email');
        $data['channel_whatsapp'] = $request->boolean('channel_whatsapp');

        if (! $data['channel_email'] && ! $data['channel_whatsapp']) {
            abort(422, 'Select at least one channel.');
        }

        if ($data['channel_email'] && blank($data['subject'] ?? null)) {
            abort(422, 'Email subject is required when Email channel is selected.');
        }

        if ($data['channel_whatsapp'] && blank($data['whatsapp_template_name'] ?? null)) {
            abort(422, 'WhatsApp template name is required when WhatsApp channel is selected.');
        }

        return $data;
    }
}
