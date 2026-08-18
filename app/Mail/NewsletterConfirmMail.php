<?php

namespace App\Mail;

use Admin\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $confirmUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your Protein Point newsletter subscription',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('mail.newsletter-confirm', [
                'subscriber' => $this->subscriber,
                'confirmUrl' => $this->confirmUrl,
            ])->render(),
        );
    }
}
