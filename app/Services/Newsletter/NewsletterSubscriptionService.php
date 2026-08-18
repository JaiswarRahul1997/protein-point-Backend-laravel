<?php

namespace App\Services\Newsletter;

use Admin\Models\Customer;
use Admin\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsletterSubscriptionService
{
    public function subscribe(array $data): array
    {
        $emailOptIn = (bool) ($data['subscribe_email'] ?? false);
        $whatsappOptIn = (bool) ($data['subscribe_whatsapp'] ?? false);

        if (! $emailOptIn && ! $whatsappOptIn) {
            throw ValidationException::withMessages([
                'subscribe' => ['Select at least one channel to subscribe.'],
            ]);
        }

        $email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : null;
        $phone = $this->normalizePhone($data['phone'] ?? null);
        $name = trim((string) ($data['name'] ?? ''));

        if ($emailOptIn && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => ['Enter a valid email address.']]);
        }

        if ($whatsappOptIn && ! $phone) {
            throw ValidationException::withMessages(['phone' => ['Enter a valid mobile number.']]);
        }

        $customer = null;
        if ($email) {
            $customer = Customer::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        return DB::transaction(function () use ($emailOptIn, $whatsappOptIn, $email, $phone, $name, $customer) {
            $subscriber = $this->findExistingSubscriber($email, $phone);

            if (! $subscriber) {
                $subscriber = new NewsletterSubscriber([
                    'name' => $name !== '' ? $name : ($customer?->name),
                    'email' => $email,
                    'phone' => $phone,
                    'customer_id' => $customer?->id,
                    'email_status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                    'whatsapp_status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                ]);
            } else {
                if ($name !== '') {
                    $subscriber->name = $name;
                }
                if ($email && ! $subscriber->email) {
                    $subscriber->email = $email;
                }
                if ($phone && ! $subscriber->phone) {
                    $subscriber->phone = $phone;
                }
                if ($customer && ! $subscriber->customer_id) {
                    $subscriber->customer_id = $customer->id;
                }
            }

            $messages = [];

            if ($emailOptIn) {
                if ($subscriber->isEmailSubscribed()) {
                    $messages[] = 'You are already subscribed to email updates.';
                } else {
                    $this->applyEmailOptIn($subscriber);
                    $messages[] = config('newsletter.double_opt_in_email')
                        ? 'Check your email to confirm your subscription.'
                        : 'You are subscribed to email updates.';
                }
            }

            if ($whatsappOptIn) {
                if ($subscriber->isWhatsAppSubscribed()) {
                    $messages[] = 'You are already subscribed to WhatsApp updates.';
                } else {
                    $this->applyWhatsAppOptIn($subscriber);
                    $messages[] = 'You are subscribed to WhatsApp updates.';
                }
            }

            $subscriber->save();

            Log::info('Newsletter subscription updated', [
                'subscriber_id' => $subscriber->id,
                'email_status' => $subscriber->email_status,
                'whatsapp_status' => $subscriber->whatsapp_status,
            ]);

            return [
                'subscriber' => $subscriber->fresh(),
                'message' => implode(' ', array_unique($messages)),
            ];
        });
    }

    public function confirmEmail(string $token): NewsletterSubscriber
    {
        $hashed = hash('sha256', $token);

        $subscriber = NewsletterSubscriber::query()
            ->where('email_confirm_token', $hashed)
            ->where('email_confirm_token_expires_at', '>', now())
            ->first();

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired confirmation link.'],
            ]);
        }

        $subscriber->update([
            'email_status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'email_subscribed_at' => now(),
            'email_unsubscribed_at' => null,
            'email_confirm_token' => null,
            'email_confirm_token_expires_at' => null,
        ]);

        return $subscriber->fresh();
    }

    public function unsubscribeEmail(string $token): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->first();

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'token' => ['Invalid unsubscribe link.'],
            ]);
        }

        $subscriber->update([
            'email_status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'email_unsubscribed_at' => now(),
        ]);

        return $subscriber->fresh();
    }

    public function unsubscribeWhatsApp(string $token): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('whatsapp_opt_out_token', $token)
            ->first();

        if (! $subscriber) {
            throw ValidationException::withMessages([
                'token' => ['Invalid WhatsApp opt-out link.'],
            ]);
        }

        $subscriber->update([
            'whatsapp_status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            'whatsapp_unsubscribed_at' => now(),
        ]);

        return $subscriber->fresh();
    }

    private function findExistingSubscriber(?string $email, ?string $phone): ?NewsletterSubscriber
    {
        if ($email) {
            $byEmail = NewsletterSubscriber::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        if ($phone) {
            return NewsletterSubscriber::query()->where('phone', $phone)->first();
        }

        return null;
    }

    private function applyEmailOptIn(NewsletterSubscriber $subscriber): void
    {
        if (config('newsletter.double_opt_in_email')) {
            $plain = Str::random(48);
            $subscriber->email_status = NewsletterSubscriber::STATUS_PENDING;
            $subscriber->email_confirm_token = hash('sha256', $plain);
            $subscriber->email_confirm_token_expires_at = now()->addHours(config('newsletter.confirm_token_ttl_hours'));
            app(NewsletterEmailSender::class)->sendConfirmation($subscriber, $plain);
        } else {
            $subscriber->email_status = NewsletterSubscriber::STATUS_SUBSCRIBED;
            $subscriber->email_subscribed_at = now();
            $subscriber->email_unsubscribed_at = null;
            $subscriber->email_confirm_token = null;
            $subscriber->email_confirm_token_expires_at = null;
        }
    }

    private function applyWhatsAppOptIn(NewsletterSubscriber $subscriber): void
    {
        $subscriber->whatsapp_status = NewsletterSubscriber::STATUS_SUBSCRIBED;
        $subscriber->whatsapp_subscribed_at = now();
        $subscriber->whatsapp_unsubscribed_at = null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
