<?php

namespace App\Jobs;

use Admin\Models\NewsletterCampaign;
use Admin\Models\NewsletterCampaignRecipient;
use App\Services\Newsletter\NewsletterCampaignService;
use App\Services\Newsletter\NewsletterEmailSender;
use App\Services\Newsletter\WhatsAppCampaignSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewsletterMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(public int $recipientId)
    {
        $this->tries = (int) config('newsletter.max_send_attempts', 3);
    }

    public function handle(
        NewsletterEmailSender $emailSender,
        WhatsAppCampaignSender $whatsAppSender,
        NewsletterCampaignService $campaignService,
    ): void {
        $recipient = NewsletterCampaignRecipient::query()->with(['subscriber', 'campaign'])->find($this->recipientId);

        if (! $recipient || $recipient->status !== NewsletterCampaignRecipient::STATUS_PENDING) {
            return;
        }

        $recipient->update([
            'status' => NewsletterCampaignRecipient::STATUS_PROCESSING,
            'attempts' => $recipient->attempts + 1,
        ]);

        try {
            $result = match ($recipient->channel) {
                NewsletterCampaignRecipient::CHANNEL_EMAIL => $emailSender->sendCampaign($recipient),
                NewsletterCampaignRecipient::CHANNEL_WHATSAPP => $whatsAppSender->sendCampaign($recipient),
                default => throw new \RuntimeException('Unknown channel.'),
            };

            $recipient->update([
                'status' => NewsletterCampaignRecipient::STATUS_SENT,
                'provider_message_id' => $result['message_id'] ?? null,
                'provider_response' => $result['response'] ?? null,
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $failed = $recipient->attempts >= $this->tries;

            $recipient->update([
                'status' => $failed ? NewsletterCampaignRecipient::STATUS_FAILED : NewsletterCampaignRecipient::STATUS_PENDING,
                'failed_at' => $failed ? now() : null,
                'error_message' => $e->getMessage(),
            ]);

            Log::warning('Newsletter message send failed', [
                'recipient_id' => $recipient->id,
                'channel' => $recipient->channel,
                'attempt' => $recipient->attempts,
            ]);

            if (! $failed) {
                throw $e;
            }
        } finally {
            if ($recipient->campaign) {
                $campaignService->markCampaignCompletedIfDone($recipient->campaign);
            }
        }
    }
}
