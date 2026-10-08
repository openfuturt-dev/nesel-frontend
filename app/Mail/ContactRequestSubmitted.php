<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ContactRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $city,
        public readonly ?string $details,
        public readonly ?string $offer = null,
        public readonly ?string $reference = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'Nesel'),
            replyTo: [new Address($this->email, $this->name)],
            subject: 'Nouvelle demande de domiciliation — '.$this->city,
        );
    }

    /**
     * Give every send of the same contact request the same Message-ID, so a
     * retried delivery can be recognised as a duplicate by the mail client.
     */
    public function headers(): Headers
    {
        if ($this->reference === null) {
            return new Headers;
        }

        $domain = Str::after((string) config('mail.from.address'), '@');

        return new Headers(messageId: "contact-request.{$this->reference}@{$domain}");
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.contact-request-submitted',
            text: 'mail.contact-request-submitted-text',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
