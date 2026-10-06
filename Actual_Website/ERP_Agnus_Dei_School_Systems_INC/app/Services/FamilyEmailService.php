<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AdmissionCredentialsMail;
use App\Models\Student;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// Applicant email reliability (spec: applicant-email-reliability.md).
// One family-email rule for every applicant send: the personal inbox first,
// the institutional address as fallback only. Sending is always guarded so a
// mail-provider outage can never fake a success or a failure — callers get
// a plain bool and message accordingly.
class FamilyEmailService
{
    public function familyEmail(Student $student): ?string
    {
        $personal = trim((string) ($student->personal_email ?? ''));
        if ($personal !== '') {
            return $personal;
        }

        $institutional = trim((string) ($student->user?->email ?? ''));

        return $institutional !== '' ? $institutional : null;
    }

    public function sendAdmissionApproval(Student $student, ?string $marker): bool
    {
        $email = $this->familyEmail($student);

        if ($email === null) {
            Log::warning('Admission approval email skipped: no address on file.', [
                'student_id' => $student->id,
            ]);

            return false;
        }

        try {
            $mail = new AdmissionCredentialsMail($student);
            $mail->idempotencyMarker = $marker;
            Mail::to($email)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Admission approval email failed: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'email' => $email,
            ]);

            return false;
        }
    }
}
