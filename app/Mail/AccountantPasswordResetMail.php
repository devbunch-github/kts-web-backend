<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Accountant;

class AccountantPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $accountant;

    public function __construct(Accountant $accountant)
    {
        $this->accountant = $accountant;
    }

    public function build()
    {
        return $this->subject('Your Password Has Been Reset')
            ->view('emails.accountant_password_reset')
            ->with([
                'accountant' => $this->accountant,
                'newPassword' => $this->newPassword,
            ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Accountant Password Reset Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.accountant_password_reset',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}