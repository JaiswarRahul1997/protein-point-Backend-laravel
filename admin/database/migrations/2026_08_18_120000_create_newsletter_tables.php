<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email_status', 20)->default('unsubscribed');
            $table->string('whatsapp_status', 20)->default('unsubscribed');
            $table->timestamp('email_subscribed_at')->nullable();
            $table->timestamp('whatsapp_subscribed_at')->nullable();
            $table->timestamp('email_unsubscribed_at')->nullable();
            $table->timestamp('whatsapp_unsubscribed_at')->nullable();
            $table->string('email_confirm_token', 80)->nullable();
            $table->timestamp('email_confirm_token_expires_at')->nullable();
            $table->string('unsubscribe_token', 80)->unique();
            $table->string('whatsapp_opt_out_token', 80)->unique();
            $table->timestamps();

            $table->unique('email');
            $table->unique('phone');
            $table->index('email_status');
            $table->index('whatsapp_status');
        });

        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('email_html')->nullable();
            $table->longText('email_text')->nullable();
            $table->string('whatsapp_template_name')->nullable();
            $table->json('whatsapp_template_params')->nullable();
            $table->text('whatsapp_message')->nullable();
            $table->boolean('channel_email')->default(false);
            $table->boolean('channel_whatsapp')->default(false);
            $table->string('audience', 40)->default('both_subscribers');
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
        });

        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained('newsletter_subscribers')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('status', 20)->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamps();

            $table->index(['campaign_id', 'channel', 'status']);
            $table->index(['subscriber_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
        Schema::dropIfExists('newsletter_campaigns');
        Schema::dropIfExists('newsletter_subscribers');
    }
};
