<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PublicContactMessage extends Mailable
{
    /** @param array{name: string, email: string, subject: string, message: string} $enquiry */
    public function __construct(public array $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->enquiry['email'], $this->enquiry['name'])],
            subject: 'NFLV website enquiry',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.public-contact');
    }
}
