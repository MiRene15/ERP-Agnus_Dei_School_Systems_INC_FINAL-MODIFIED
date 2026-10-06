<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, SkipsDuplicateSends;

    public $firstName;
    public $institutionalEmail;
    public $password;
    public $verifyUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $firstName, string $institutionalEmail, string $password, string $verifyUrl)
    {
        $this->firstName = $firstName;
        $this->institutionalEmail = $institutionalEmail;
        $this->password = $password;
        $this->verifyUrl = $verifyUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Agnus Dei - Your Institutional Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.inquiry_credentials',
            with: [
                'firstName' => $this->firstName,
                'email' => $this->institutionalEmail,
                'password' => $this->password,
                'verifyUrl' => $this->verifyUrl,
            ],
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
