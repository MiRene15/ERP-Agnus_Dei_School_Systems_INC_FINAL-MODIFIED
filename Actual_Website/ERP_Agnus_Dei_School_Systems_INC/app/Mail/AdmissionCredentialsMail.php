<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdmissionCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Admission Approved - Enrollment Confirmation',
        );
    }

    public function build(): void
    {
        $this->text('emails.admission-credentials-mail');
    }

    public function attachments(): array
    {
        return [];
    }
}
