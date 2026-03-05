<?php

namespace App\Mail;

use App\Models\Convention;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Convention $convention,
        public string $invitationUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation to {$this->convention->name}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-invitation',
            with: [
                'userName' => $this->user->first_name,
                'conventionName' => $this->convention->name,
                'invitationUrl' => $this->invitationUrl,
                'expiresAt' => now()->addHours(24)->format('M d, Y g:i A'),
            ],
        );
    }
}
