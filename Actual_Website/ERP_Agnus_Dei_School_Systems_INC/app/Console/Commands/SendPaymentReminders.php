<?php

namespace App\Console\Commands;

use App\Mail\PaymentReminderMail;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:payment';

    protected $description = 'Send gentle payment reminders to students with outstanding balances (3 days before the 15th)';

    public function handle()
    {
        $today = now();
        $reminderDay = 12;

        if ((int) $today->format('d') !== $reminderDay) {
            $this->info('Not the reminder day (12th). Skipping.');
            return 0;
        }

        $students = Student::where('status', 'enrolled')
            ->whereHas('ledger', function ($q) {
                $q->where('balance', '>', 0);
                $q->where('clearance_status', '!=', 'Cleared');
            })
            ->with('user', 'ledger', 'enrollments.section')
            ->get();

        $sentCount = 0;
        $cycle = $today->format('Y-m');
        $mailRoute = 'mail:'.PaymentReminderMail::class;

        foreach ($students as $student) {
            if (!$student->user?->email) {
                continue;
            }

            $enrollment = $student->enrollments()
                ->where('status', 'Active')
                ->latest()
                ->first();

            $schoolYear = $enrollment?->school_year ?? now()->format('Y') . '-' . (now()->format('Y') + 1);

            // Re-run safe: a reminder already queued for this student and cycle is skipped.
            $reminderMarker = \App\Models\IdempotencyKey::referenceFor('reminder', (string) $student->id, $cycle);
            if (\App\Models\IdempotencyKey::where('reference', $reminderMarker)->where('route', $mailRoute)->exists()) {
                continue;
            }

            try {
                $reminderMail = new PaymentReminderMail($student, $student->ledger->balance, $schoolYear);
                $reminderMail->idempotencyMarker = $reminderMarker;
                Mail::to($student->user->email)->send($reminderMail);
                $sentCount++;
                $this->info("Sent reminder to: {$student->user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send to {$student->user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent {$sentCount} reminder(s).");
        return 0;
    }
}
