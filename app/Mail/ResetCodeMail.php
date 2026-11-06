<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetCodeMail extends Mailable
{
    public function __construct(
        public string $name,
        public string $code,
        public int $minutes = 10,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode reset password School Class Progress');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reset-code');
    }
}
