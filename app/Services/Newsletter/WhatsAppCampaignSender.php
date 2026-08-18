<?php

namespace App\Services\Newsletter;

use Admin\Models\NewsletterCampaignRecipient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCampaignSender
{
    public function sendCampaign(NewsletterCampaignRecipient $recipient): array
    {
        $recipient->loadMissing(['subscriber', 'campaign']);
        $subscriber = $recipient->subscriber;
        $campaign = $recipient->campaign;

        if (! $subscriber || ! $subscriber->isWhatsAppSubscribed() || ! filled($subscriber->phone)) {
            throw new \RuntimeException('Subscriber is not opted in for WhatsApp.');
        }

        $token = config('newsletter.whatsapp.access_token');
        $phoneNumberId = config('newsletter.whatsapp.phone_number_id');
        $apiUrl = rtrim((string) config('newsletter.whatsapp.api_url'), '/');

        if (! $token || ! $phoneNumberId) {
            throw new \RuntimeException('WhatsApp API is not configured.');
        }

        if (! filled($campaign->whatsapp_template_name)) {
            throw new \RuntimeException('WhatsApp template name is required.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $subscriber->normalizedPhone(),
            'type' => 'template',
            'template' => [
                'name' => $campaign->whatsapp_template_name,
                'language' => ['code' => 'en'],
                'components' => $this->templateComponents($campaign, $subscriber),
            ],
        ];

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("{$apiUrl}/{$phoneNumberId}/messages", $payload);

        if (! $response->successful()) {
            Log::warning('WhatsApp send failed', [
                'recipient_id' => $recipient->id,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException('WhatsApp provider rejected the message.');
        }

        $body = $response->json();
        $messageId = data_get($body, 'messages.0.id');

        Log::info('WhatsApp campaign message sent', [
            'recipient_id' => $recipient->id,
            'message_id' => $messageId,
        ]);

        return [
            'message_id' => $messageId,
            'response' => $body,
        ];
    }

    private function templateComponents($campaign, $subscriber): array
    {
        $params = $campaign->whatsapp_template_params ?? [];
        $values = array_map(function ($value) use ($subscriber) {
            $text = str_replace(
                ['{{name}}', '{{phone}}'],
                [$subscriber->name ?: 'Customer', $subscriber->phone],
                (string) $value
            );

            return ['type' => 'text', 'text' => $text];
        }, $params);

        if ($values === []) {
            return [];
        }

        return [[
            'type' => 'body',
            'parameters' => $values,
        ]];
    }
}
