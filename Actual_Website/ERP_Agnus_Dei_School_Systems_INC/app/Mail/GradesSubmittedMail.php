<?php

namespace App\Mail;

use App\Models\Classes;
use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GradesSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $class;
    public $gradingPeriod;

    public function __construct(Classes $class, string $gradingPeriod)
    {
        $this->class = $class;
        $this->gradingPeriod = $gradingPeriod;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Grades Submitted - ' . $this->class->subject?->name . ' (' . $this->gradingPeriod . ')',
        );
    }

    public function build(): void
    {
        $this->text('emails.grades-submitted');
    }

    public function attachments(): array
    {
        return [];
    }
}
