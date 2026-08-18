<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
        public string $textBody,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('mail.newsletter-campaign', [
                'htmlBody' => $this->htmlBody,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ])->render(),
            text: 'mail.newsletter-campaign-text',
            with: [
                'textBody' => $this->textBody,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }
}
