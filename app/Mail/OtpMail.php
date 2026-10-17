<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre code de vérification Quick Pick',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-mail',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}