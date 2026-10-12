<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admission;
use Illuminate\Support\Facades\DB;

/**
 * Review-stage rules for admission applications
 * (spec: enrollment-payment-gating-review-forward.md).
 *
 * Two outcomes only at this stage:
 *   forward — complete file → status "Awaiting Downpayment" (Cashier picks it up)
 *   return  — incomplete file → status "Returned" with a note naming what's missing
 *
 * No enrollment is ever created here (that happens in child 2, after the
 * Cashier confirms the downpayment).
 */
class AdmissionReviewService
{
    public const STATUS_AWAITING_DOWNPAYMENT = 'Awaiting Downpayment';

    public const STATUS_RETURNED = 'Returned';

    /**
     * Document types every applicant must upload before submit.
     * Single source of truth: the submit gate, the registrar forward gate,
     * and the family upload checklist all read this list.
     *
     * @return array<int, string>
     */
    public static function requiredDocumentTypes(): array
    {
        return ['PSA Birth Certificate', 'Form 138 (Report Card)', 'Good Moral Certificate'];
    }

    /**
     * Required document types not yet uploaded for this admission.
     *
     * @return array<int, string>
     */
    public static function missingRequiredDocuments(Admission $admission): array
    {
        $uploaded = $admission->requirements()->pluck('document_type')->all();

        return array_values(array_diff(self::requiredDocumentTypes(), $uploaded));
    }

    /**
     * Forward a complete file to the Cashier. Blocked unless the school year
     * is unlocked, gender is filled, and every required document is uploaded
     * AND verified. The application then waits as Awaiting Downpayment —
     * no enrollment is created.
     *
     * @return array{forwarded: bool, admission?: Admission, error?: string, message?: string}
     */
    public static function forward(Admission $admission, string $reviewerName, ?string $note = null): array
    {
        if ($admission->status !== 'Pending') {
            return ['forwarded' => false, 'error' => 'This admission has already been processed.'];
        }

        // Paid files advance only via Grant Full Approval (spec:
        // payment-access.md Option A) — never back through Forward.
        if (! empty($admission->downpayment_receipt)) {
            return ['forwarded' => false, 'error' => 'Downpayment already confirmed (' . $admission->downpayment_receipt . '). Use Grant Full Approval instead of forwarding again.'];
        }

        // Locked school years are frozen — no forwarding into them.
        if (school_year_locked($admission->school_year)) {
            return ['forwarded' => false, 'error' => 'Cannot forward — school year ' . $admission->school_year . ' is locked. Verified work is kept.'];
        }

        // Gender gate (same rule the old Approve had): correct it first.
        if (empty($admission->student?->gender)) {
            return ['forwarded' => false, 'error' => 'Gender is still missing for this applicant. Set it above before forwarding.'];
        }

        // Spec cashier-forwarded-new-account-display.md §6 edge: a file
        // with no grade must never reach Cashier as N/A — block here and
        // name the missing grade.
        if (empty(trim((string) ($admission->grade_level ?? '')))) {
            return ['forwarded' => false, 'error' => 'Grade level is still missing for this application. Set the grade level above before forwarding.'];
        }

        $missing = self::missingRequiredDocuments($admission);
        if (! empty($missing)) {
            return ['forwarded' => false, 'error' => 'Required document(s) not uploaded: ' . implode(', ', $missing) . '. Ask the family to upload them first.'];
        }

        $pending = $admission->requirements()
            ->where('status', '!=', 'Verified')
            ->pluck('document_type')
            ->all();

        if (! empty($pending)) {
            $total = $admission->requirements()->count();
            $verified = $total - count($pending);
            $names = implode(', ', $pending);

            return ['forwarded' => false, 'error' => $names . (count($pending) === 1 ? ' is' : ' are') . ' still Under Review — ' . $verified . '/' . $total . ' verified.'];
        }

        // One transaction: the admission and the student flag commit together,
        // so the Cashier payments page (which reads students by status) can
        // never miss a forwarded file after a partial failure.
        DB::transaction(function () use ($admission, $note): void {
            $admission->status = self::STATUS_AWAITING_DOWNPAYMENT;
            $admission->review_note = $note !== null && trim($note) !== ''
                ? trim($note)
                : 'Forwarded to Cashier — file complete, awaiting downpayment.';
            $admission->save();

            $student = $admission->student;
            if ($student !== null && $student->status !== 'Awaiting Downpayment') {
                $student->status = 'Awaiting Downpayment';
                $student->save();
            }

            // Spec cashier-forwarded-new-account-display.md §4.2: the
            // student number travels with the file at forward time, so the
            // Cashier row is never blank. New/Transferee files get a number
            // here (same generator full approval uses); Old files keep
            // their existing number.
            if ($student !== null && empty($student->student_number) && ($admission->application_type ?? 'New') !== 'Old') {
                $student->student_number = \App\Models\Student::generateStudentNumber();
                $student->save();
            }
        });

        log_activity(
            $admission,
            'Forwarded to Cashier',
            $reviewerName . ' forwarded admission ' . ($admission->application_number ?? '#' . $admission->id)
                . ' to Cashier — awaiting downpayment.'
                . ($note !== null && trim($note) !== '' ? ' Note: ' . trim($note) : '')
        );

        return [
            'forwarded' => true,
            'admission' => $admission,
            'message' => 'Forwarded to Cashier. The family sees Awaiting Downpayment; no enrollment is created yet.',
        ];
    }

    /**
     * Return an incomplete file to the family with a note naming each missing
     * document. Already-uploaded files are kept (verified stay Verified).
     * A comment-only return is allowed for non-document issues such as
     * "student email needs fix".
     *
     * @return array{returned: bool, admission?: Admission, note?: string, error?: string, message?: string}
     */
    public static function returnWithNote(Admission $admission, string $reviewerName, ?string $comment = null): array
    {
        if ($admission->status !== 'Pending') {
            return ['returned' => false, 'error' => 'This admission has already been processed.'];
        }

        $missing = self::missingRequiredDocuments($admission);
        $unverified = $admission->requirements()
            ->where('status', '!=', 'Verified')
            ->pluck('document_type')
            ->all();

        $named = array_values(array_unique(array_merge($missing, $unverified)));
        $comment = $comment !== null ? trim($comment) : '';

        if (empty($named) && $comment === '') {
            return ['returned' => false, 'error' => 'All documents are verified — use Forward to Cashier instead.'];
        }

        $note = $named !== []
            ? 'Missing: ' . implode(', ', $named) . ($comment !== '' ? ' — ' . $comment : '')
            : $comment;

        $admission->status = self::STATUS_RETURNED;
        $admission->review_note = $note;
        $admission->save();

        log_activity(
            $admission,
            'Returned to Family',
            $reviewerName . ' returned admission ' . ($admission->application_number ?? '#' . $admission->id)
                . ' — ' . $note . ' Uploaded files kept.'
        );

        return [
            'returned' => true,
            'admission' => $admission,
            'note' => $note,
            'message' => 'Returned — the family sees exactly what to upload. Their other files are kept.',
        ];
    }

    /**
     * Bulk forward with one note. Every file goes through the same gates as
     * a single forward; incomplete files stay Pending and are reported per
     * file so the registrar knows what is still waiting.
     *
     * @param array<int, int> $admissionIds
     * @return array{forwarded: array<int, string>, blocked: array<int, string>, forwarded_count: int, blocked_count: int}
     */
    public static function bulkForward(array $admissionIds, string $reviewerName, ?string $note = null): array
    {
        $forwarded = [];
        $blocked = [];

        $admissions = Admission::whereIn('id', array_filter($admissionIds))->get();

        foreach ($admissions as $admission) {
            $result = self::forward($admission, $reviewerName, $note);

            if ($result['forwarded']) {
                $forwarded[] = $admission->application_number ?? '#' . $admission->id;
            } else {
                $blocked[$admission->id] = $result['error'];
            }
        }

        return [
            'forwarded' => $forwarded,
            'blocked' => $blocked,
            'forwarded_count' => count($forwarded),
            'blocked_count' => count($blocked),
        ];
    }
}
