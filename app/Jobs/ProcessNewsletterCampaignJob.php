<?php

namespace App\Jobs;

use Admin\Models\NewsletterCampaign;
use Admin\Models\NewsletterCampaignRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessNewsletterCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = NewsletterCampaign::query()->find($this->campaignId);

        if (! $campaign || $campaign->status !== NewsletterCampaign::STATUS_PROCESSING) {
            return;
        }

        $recipients = $campaign->recipients()
            ->where('status', NewsletterCampaignRecipient::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        foreach ($recipients as $recipient) {
            SendNewsletterMessageJob::dispatch($recipient->id);
        }

        Log::info('Newsletter campaign queued recipients', [
            'campaign_id' => $campaign->id,
            'count' => $recipients->count(),
        ]);
    }
}
