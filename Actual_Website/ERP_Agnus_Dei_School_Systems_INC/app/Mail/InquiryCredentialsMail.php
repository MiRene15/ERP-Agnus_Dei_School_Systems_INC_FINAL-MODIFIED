<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $institutionalEmail;
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct($firstName, $institutionalEmail, $password)
    {
        $this->firstName = $firstName;
        $this->institutionalEmail = $institutionalEmail;
        $this->password = $password;
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
