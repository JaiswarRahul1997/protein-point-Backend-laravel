<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBSCRIBED = 'subscribed';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'phone',
        'email_status',
        'whatsapp_status',
        'email_subscribed_at',
        'whatsapp_subscribed_at',
        'email_unsubscribed_at',
        'whatsapp_unsubscribed_at',
        'email_confirm_token',
        'email_confirm_token_expires_at',
        'unsubscribe_token',
        'whatsapp_opt_out_token',
    ];

    protected function casts(): array
    {
        return [
            'email_subscribed_at' => 'datetime',
            'whatsapp_subscribed_at' => 'datetime',
            'email_unsubscribed_at' => 'datetime',
            'whatsapp_unsubscribed_at' => 'datetime',
            'email_confirm_token_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            if (! $subscriber->unsubscribe_token) {
                $subscriber->unsubscribe_token = Str::random(48);
            }
            if (! $subscriber->whatsapp_opt_out_token) {
                $subscriber->whatsapp_opt_out_token = Str::random(48);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class, 'subscriber_id');
    }

    public function isEmailSubscribed(): bool
    {
        return $this->email_status === self::STATUS_SUBSCRIBED;
    }

    public function isWhatsAppSubscribed(): bool
    {
        return $this->whatsapp_status === self::STATUS_SUBSCRIBED;
    }

    public function normalizedPhone(): ?string
    {
        $phone = preg_replace('/\D+/', '', (string) $this->phone);

        return $phone !== '' ? $phone : null;
    }
}
