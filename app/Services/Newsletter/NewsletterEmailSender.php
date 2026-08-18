<?php

namespace App\Services\Newsletter;

use Admin\Models\NewsletterCampaignRecipient;
use Admin\Models\NewsletterSubscriber;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterConfirmMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterEmailSender
{
    public function sendConfirmation(NewsletterSubscriber $subscriber, string $plainToken): void
    {
        if (! filled($subscriber->email)) {
            return;
        }

        $confirmUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            .'/newsletter/confirm?token='.urlencode($plainToken);

        Mail::to($subscriber->email)->send(new NewsletterConfirmMail($subscriber, $confirmUrl));
    }

    public function sendCampaign(NewsletterCampaignRecipient $recipient): array
    {
        $recipient->loadMissing(['subscriber', 'campaign']);
        $subscriber = $recipient->subscriber;
        $campaign = $recipient->campaign;

        if (! $subscriber || ! $subscriber->isEmailSubscribed() || ! filled($subscriber->email)) {
            throw new \RuntimeException('Subscriber is not opted in for email.');
        }

        $unsubscribeUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            .'/newsletter/unsubscribe/email?token='.urlencode($subscriber->unsubscribe_token);

        $html = $this->personalize($campaign->email_html ?? '', $subscriber, $unsubscribeUrl);
        $text = $this->personalize($campaign->email_text ?? strip_tags($html), $subscriber, $unsubscribeUrl);

        Mail::to($subscriber->email)->send(new NewsletterCampaignMail(
            subjectLine: $campaign->subject ?? $campaign->name,
            htmlBody: $html,
            textBody: $text,
            unsubscribeUrl: $unsubscribeUrl,
        ));

        $messageId = 'mail-'.Str::uuid()->toString();

        Log::info('Newsletter email sent', [
            'recipient_id' => $recipient->id,
            'campaign_id' => $campaign->id,
            'message_id' => $messageId,
        ]);

        return ['message_id' => $messageId];
    }

    private function personalize(string $content, NewsletterSubscriber $subscriber, string $unsubscribeUrl): string
    {
        return str_replace(
            ['{{name}}', '{{email}}', '{{unsubscribe_url}}'],
            [$subscriber->name ?: 'Customer', $subscriber->email, $unsubscribeUrl],
            $content
        );
    }
}
