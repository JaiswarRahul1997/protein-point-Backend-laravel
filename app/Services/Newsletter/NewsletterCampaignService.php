<?php

namespace App\Services\Newsletter;

use Admin\Models\NewsletterCampaign;
use Admin\Models\NewsletterCampaignRecipient;
use Admin\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NewsletterCampaignService
{
    public function buildRecipients(NewsletterCampaign $campaign): int
    {
        $subscribers = $this->audienceQuery($campaign)->get();
        $created = 0;

        foreach ($subscribers as $subscriber) {
            if ($campaign->usesEmail() && $subscriber->isEmailSubscribed() && filled($subscriber->email)) {
                $created += $this->createRecipient($campaign, $subscriber, NewsletterCampaignRecipient::CHANNEL_EMAIL) ? 1 : 0;
            }

            if ($campaign->usesWhatsApp() && $subscriber->isWhatsAppSubscribed() && filled($subscriber->phone)) {
                $created += $this->createRecipient($campaign, $subscriber, NewsletterCampaignRecipient::CHANNEL_WHATSAPP) ? 1 : 0;
            }
        }

        return $created;
    }

    public function dispatch(NewsletterCampaign $campaign): void
    {
        if (! in_array($campaign->status, [NewsletterCampaign::STATUS_DRAFT, NewsletterCampaign::STATUS_SCHEDULED], true)) {
            throw new \RuntimeException('Campaign cannot be dispatched in its current status.');
        }

        DB::transaction(function () use ($campaign) {
            $locked = NewsletterCampaign::query()->lockForUpdate()->find($campaign->id);

            if (! $locked || ! in_array($locked->status, [NewsletterCampaign::STATUS_DRAFT, NewsletterCampaign::STATUS_SCHEDULED], true)) {
                throw new \RuntimeException('Campaign already processing or completed.');
            }

            $locked->update([
                'status' => NewsletterCampaign::STATUS_PROCESSING,
                'started_at' => now(),
                'last_error' => null,
            ]);

            $recipientCount = $this->buildRecipients($locked);

            if ($recipientCount === 0) {
                $locked->update([
                    'status' => NewsletterCampaign::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                return;
            }

            \App\Jobs\ProcessNewsletterCampaignJob::dispatch($locked->id);
        });

        Log::info('Newsletter campaign dispatched', ['campaign_id' => $campaign->id]);
    }

    public function markCampaignCompletedIfDone(NewsletterCampaign $campaign): void
    {
        $pending = $campaign->recipients()
            ->whereIn('status', [
                NewsletterCampaignRecipient::STATUS_PENDING,
                NewsletterCampaignRecipient::STATUS_PROCESSING,
            ])
            ->exists();

        if (! $pending) {
            $campaign->update([
                'status' => NewsletterCampaign::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    }

    public function stats(NewsletterCampaign $campaign): array
    {
        $rows = $campaign->recipients()
            ->selectRaw('channel, status, COUNT(*) as total')
            ->groupBy('channel', 'status')
            ->get();

        $stats = [
            'total' => 0,
            'email' => 0,
            'whatsapp' => 0,
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'cancelled' => 0,
            'unsubscribed' => 0,
        ];

        foreach ($rows as $row) {
            $stats['total'] += (int) $row->total;
            if ($row->channel === NewsletterCampaignRecipient::CHANNEL_EMAIL) {
                $stats['email'] += (int) $row->total;
            }
            if ($row->channel === NewsletterCampaignRecipient::CHANNEL_WHATSAPP) {
                $stats['whatsapp'] += (int) $row->total;
            }
            if (isset($stats[$row->status])) {
                $stats[$row->status] += (int) $row->total;
            }
        }

        return $stats;
    }

    private function audienceQuery(NewsletterCampaign $campaign)
    {
        $query = NewsletterSubscriber::query();

        return match ($campaign->audience) {
            'email_subscribers' => $query->where('email_status', NewsletterSubscriber::STATUS_SUBSCRIBED),
            'whatsapp_subscribers' => $query->where('whatsapp_status', NewsletterSubscriber::STATUS_SUBSCRIBED),
            default => $query->where(function ($q) {
                $q->where('email_status', NewsletterSubscriber::STATUS_SUBSCRIBED)
                    ->orWhere('whatsapp_status', NewsletterSubscriber::STATUS_SUBSCRIBED);
            }),
        };
    }

    private function createRecipient(NewsletterCampaign $campaign, NewsletterSubscriber $subscriber, string $channel): bool
    {
        $key = NewsletterCampaignRecipient::makeIdempotencyKey($campaign->id, $subscriber->id, $channel);

        $recipient = NewsletterCampaignRecipient::query()->firstOrCreate(
            ['idempotency_key' => $key],
            [
                'campaign_id' => $campaign->id,
                'subscriber_id' => $subscriber->id,
                'channel' => $channel,
                'status' => NewsletterCampaignRecipient::STATUS_PENDING,
            ]
        );

        return $recipient->wasRecentlyCreated;
    }
}
