<?php

namespace App\Http\Controllers\Api;

use Admin\Models\NewsletterCampaignRecipient;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): JsonResponse|string
    {
        $mode = (string) $request->query('hub_mode');
        $token = (string) $request->query('hub_verify_token');
        $challenge = (string) $request->query('hub_challenge');

        if ($mode === 'subscribe' && hash_equals((string) config('newsletter.whatsapp.webhook_verify_token'), $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        if (! $this->validSignature($request)) {
            Log::warning('WhatsApp webhook signature invalid');

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payload = $request->all();

        foreach (data_get($payload, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.statuses', []) as $status) {
                    $this->updateRecipientFromStatus($status);
                }
            }
        }

        return response()->json(['message' => 'ok']);
    }

    private function updateRecipientFromStatus(array $status): void
    {
        $messageId = data_get($status, 'id');
        $state = data_get($status, 'status');

        if (! $messageId || ! $state) {
            return;
        }

        $recipient = NewsletterCampaignRecipient::query()
            ->where('provider_message_id', $messageId)
            ->where('channel', NewsletterCampaignRecipient::CHANNEL_WHATSAPP)
            ->first();

        if (! $recipient) {
            return;
        }

        $mapped = match ($state) {
            'sent' => NewsletterCampaignRecipient::STATUS_SENT,
            'delivered' => NewsletterCampaignRecipient::STATUS_DELIVERED,
            'read' => NewsletterCampaignRecipient::STATUS_DELIVERED,
            'failed' => NewsletterCampaignRecipient::STATUS_FAILED,
            default => $recipient->status,
        };

        $updates = [
            'status' => $mapped,
            'provider_response' => array_merge($recipient->provider_response ?? [], ['webhook' => $status]),
        ];

        if ($mapped === NewsletterCampaignRecipient::STATUS_DELIVERED) {
            $updates['delivered_at'] = now();
            if ($state === 'read') {
                $updates['read_at'] = now();
            }
        }

        if ($mapped === NewsletterCampaignRecipient::STATUS_FAILED) {
            $updates['failed_at'] = now();
            $updates['error_message'] = data_get($status, 'errors.0.title', 'Delivery failed');
        }

        $recipient->update($updates);
    }

    private function validSignature(Request $request): bool
    {
        $secret = config('newsletter.whatsapp.app_secret');

        if (! $secret) {
            return app()->environment('local', 'testing');
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
