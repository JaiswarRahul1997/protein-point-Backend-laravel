<?php

namespace App\Console\Commands;

use Admin\Models\NewsletterCampaign;
use App\Services\Newsletter\NewsletterCampaignService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledNewsletterCampaigns extends Command
{
    protected $signature = 'newsletter:dispatch-scheduled';

    protected $description = 'Dispatch scheduled newsletter campaigns that are due';

    public function handle(NewsletterCampaignService $service): int
    {
        $campaigns = NewsletterCampaign::query()
            ->where('status', NewsletterCampaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->get();

        foreach ($campaigns as $campaign) {
            try {
                $service->dispatch($campaign);
                $this->info("Dispatched campaign #{$campaign->id}");
            } catch (\Throwable $e) {
                $campaign->update([
                    'status' => NewsletterCampaign::STATUS_FAILED,
                    'last_error' => $e->getMessage(),
                ]);

                Log::error('Scheduled newsletter campaign failed', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed campaign #{$campaign->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
