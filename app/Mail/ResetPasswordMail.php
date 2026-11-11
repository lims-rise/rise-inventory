<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $url,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your RISE Asset DB password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reset-password');
    }
}
