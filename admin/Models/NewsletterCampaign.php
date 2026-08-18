<?php

namespace Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    public const AUDIENCES = [
        'email_subscribers' => 'Email subscribers only',
        'whatsapp_subscribers' => 'WhatsApp subscribers only',
        'both_subscribers' => 'Both channels (per channel opt-in)',
    ];

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SCHEDULED => 'Scheduled',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_FAILED => 'Failed',
    ];

    protected $fillable = [
        'name',
        'subject',
        'email_html',
        'email_text',
        'whatsapp_template_name',
        'whatsapp_template_params',
        'whatsapp_message',
        'channel_email',
        'channel_whatsapp',
        'audience',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'channel_email' => 'boolean',
            'channel_whatsapp' => 'boolean',
            'whatsapp_template_params' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class, 'campaign_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED], true);
    }

    public function usesEmail(): bool
    {
        return (bool) $this->channel_email;
    }

    public function usesWhatsApp(): bool
    {
        return (bool) $this->channel_whatsapp;
    }
}
