<?php

namespace Tests\Feature;

use Admin\Models\NewsletterCampaign;
use Admin\Models\NewsletterSubscriber;
use App\Jobs\ProcessNewsletterCampaignJob;
use App\Jobs\SendNewsletterMessageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'admin/database/migrations', '--force' => true]);
    }

    public function test_email_subscription_with_double_opt_in(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/newsletter/subscribe', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subscribe_email' => true,
            'subscribe_whatsapp' => false,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'jane@example.com',
            'email_status' => 'pending',
        ]);
    }

    public function test_whatsapp_subscription(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'phone' => '9876543210',
            'subscribe_email' => false,
            'subscribe_whatsapp' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('newsletter_subscribers', [
            'phone' => '9876543210',
            'whatsapp_status' => 'subscribed',
        ]);
    }

    public function test_both_channel_subscription(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/newsletter/subscribe', [
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '9123456789',
            'subscribe_email' => true,
            'subscribe_whatsapp' => true,
        ]);

        $response->assertCreated();
        $subscriber = NewsletterSubscriber::query()->where('email', 'john@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertSame('pending', $subscriber->email_status);
        $this->assertSame('subscribed', $subscriber->whatsapp_status);
    }

    public function test_duplicate_email_subscription_returns_friendly_message(): void
    {
        NewsletterSubscriber::create([
            'email' => 'dup@example.com',
            'email_status' => 'subscribed',
            'whatsapp_status' => 'unsubscribed',
            'email_subscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'dup@example.com',
            'subscribe_email' => true,
            'subscribe_whatsapp' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['message' => 'You are already subscribed to email updates.']);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'not-an-email',
            'subscribe_email' => true,
            'subscribe_whatsapp' => false,
        ]);

        $response->assertStatus(422);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'phone' => '123',
            'subscribe_email' => false,
            'subscribe_whatsapp' => true,
        ]);

        $response->assertStatus(422);
    }

    public function test_email_unsubscribe(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'leave@example.com',
            'email_status' => 'subscribed',
            'whatsapp_status' => 'subscribed',
            'email_subscribed_at' => now(),
            'whatsapp_subscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/newsletter/unsubscribe/email', [
            'token' => $subscriber->unsubscribe_token,
        ]);

        $response->assertOk();
        $this->assertSame('unsubscribed', $subscriber->fresh()->email_status);
        $this->assertSame('subscribed', $subscriber->fresh()->whatsapp_status);
    }

    public function test_whatsapp_unsubscribe(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'both@example.com',
            'phone' => '9988776655',
            'email_status' => 'subscribed',
            'whatsapp_status' => 'subscribed',
            'email_subscribed_at' => now(),
            'whatsapp_subscribed_at' => now(),
        ]);

        $response = $this->postJson('/api/newsletter/unsubscribe/whatsapp', [
            'token' => $subscriber->whatsapp_opt_out_token,
        ]);

        $response->assertOk();
        $this->assertSame('subscribed', $subscriber->fresh()->email_status);
        $this->assertSame('unsubscribed', $subscriber->fresh()->whatsapp_status);
    }

    public function test_campaign_dispatch_queues_jobs(): void
    {
        Bus::fake();

        NewsletterSubscriber::create([
            'email' => 'campaign@example.com',
            'email_status' => 'subscribed',
            'whatsapp_status' => 'unsubscribed',
            'email_subscribed_at' => now(),
        ]);

        $campaign = NewsletterCampaign::create([
            'name' => 'Launch',
            'subject' => 'Hello',
            'email_html' => '<p>Hi {{name}}</p>',
            'channel_email' => true,
            'channel_whatsapp' => false,
            'audience' => 'email_subscribers',
            'status' => 'draft',
        ]);

        app(\App\Services\Newsletter\NewsletterCampaignService::class)->dispatch($campaign);

        Bus::assertDispatched(ProcessNewsletterCampaignJob::class);
    }
}
