<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admission;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Registrar full approval (spec:
 * enrollment-payment-gating-payment-access.md, Option A).
 *
 * Paid Pending file + section assignment → enrollment created →
 * institution address provisioned → notice email. This is the relocated
 * enrollment-creation path the old single-step approve used: the same
 * gender / verification / holds / year-lock / capacity gates, plus the
 * downpayment-receipt gate that closes the approve-without-paying
 * bypass. Nothing before this step creates an enrollment.
 */
class FullApprovalService
{
    /**
     * @param array<int, int> $subjectIds
     * @return array{approved: bool, enrollment?: Enrollment, address?: ?string, mailSent?: bool, message?: string, error?: string}
     */
    public static function approve(Admission $admission, int $sectionId, array $subjectIds, string $registrarName, ?string $mailMarker): array
    {
        if ($admission->status === 'Approved By Registrar') {
            return ['approved' => false, 'error' => 'This admission has already been processed.'];
        }

        if ($admission->status === AdmissionReviewService::STATUS_AWAITING_DOWNPAYMENT) {
            return ['approved' => false, 'error' => 'Confirm the downpayment at Cashier first — this file has not been paid yet.'];
        }

        if ($admission->status !== 'Pending' || empty($admission->downpayment_receipt)) {
            if ($admission->status !== 'Pending') {
                return ['approved' => false, 'error' => 'This admission has already been processed.'];
            }

            return ['approved' => false, 'error' => 'Downpayment first — confirm it at Cashier (receipt ' . ($admission->downpayment_receipt ?? 'missing') . '), then grant full approval here.'];
        }

        // Gender gate (same rule review-forward enforces): correct it first.
        if (empty($admission->student?->gender)) {
            return ['approved' => false, 'error' => 'Gender is still missing for this applicant. Please set it above before approving.'];
        }

        $pendingDocs = $admission->requirements()->where('status', '!=', 'Verified')->pluck('document_type');
        if ($pendingDocs->isNotEmpty()) {
            $totalDocs = $admission->requirements()->count();
            $verifiedDocs = $totalDocs - $pendingDocs->count();
            $names = $pendingDocs->implode(', ');

            return ['approved' => false, 'error' => $names . ($pendingDocs->count() === 1 ? ' is' : ' are') . ' still Under Review — ' . $verifiedDocs . '/' . $totalDocs . ' verified.'];
        }

        // Holds block full approval until cleared (library / clinic / finance).
        $studentForHolds = $admission->student;
        if ($studentForHolds) {
            $holds = HoldService::forStudent($studentForHolds);
            if (! empty($holds)) {
                return ['approved' => false, 'error' => 'Cannot enroll — holds must be cleared first. ' . HoldService::blockingMessage($holds)];
            }
        }

        // Locked school years are frozen — no new enrollments into them.
        if (school_year_locked($admission->school_year)) {
            return ['approved' => false, 'error' => 'Cannot enroll — school year ' . $admission->school_year . ' is locked.'];
        }

        $section = Section::find($sectionId);
        if ($section === null) {
            return ['approved' => false, 'error' => 'Section not found. Pick a section and try again.'];
        }

        // Uniform-30 section cap (spec promotion-section-capacity §6): a Locked
        // section refuses new picks with guidance naming the open section.
        $sectionCount = Enrollment::where('section_id', $sectionId)
            ->where('school_year', $admission->school_year)
            ->where('status', 'Active')
            ->count();
        if ($sectionCount >= PromotionService::SECTION_CAP) {
            $open = Section::where('grade_level', $section->grade_level)
                ->where('is_active', true)
                ->where('id', '!=', $sectionId)
                ->orderBy('section_name')
                ->get()
                ->first(function ($s) use ($admission) {
                    return Enrollment::where('section_id', $s->id)
                        ->where('school_year', $admission->school_year)
                        ->where('status', 'Active')
                        ->count() < PromotionService::SECTION_CAP;
                });
            $guide = $open ? " Place in {$open->section_name} instead." : ' All sections for this grade are full — promotion will auto-create the next section.';
            return ['approved' => false, 'error' => "Section {$section->section_name} is full (Locked at " . PromotionService::SECTION_CAP . ")." . $guide];
        }

        foreach ($subjectIds as $classId) {
            $class = Classes::find($classId);
            if ($class && $class->capacity) {
                $enrolledCount = $class->enrollments()
                    ->where('status', 'Active')
                    ->count();
                if ($enrolledCount >= $class->capacity) {
                    return ['approved' => false, 'error' => 'Subject "' . ($class->subject->name ?? 'N/A') . '" has reached its capacity of ' . $class->capacity . ' students.'];
                }
            }
        }

        $student = $admission->student;
        $address = null;

        try {
            $enrollment = DB::transaction(function () use ($admission, $student, $sectionId, $subjectIds, $registrarName, &$address) {
                if ($admission->application_type !== 'Old') {
                    $student->student_number = Student::generateStudentNumber();
                }

                $student->status = 'enrolled';
                $student->save();

                $record = Enrollment::create([
                    'student_id' => $student->id,
                    'section_id' => $sectionId,
                    'school_year' => $admission->school_year,
                    'strand' => $admission->strand,
                    'status' => 'Active',
                ]);

                $record->subjects()->attach($subjectIds);

                $admission->status = 'Approved By Registrar';
                $admission->save();

                $provisioned = InstitutionAddressService::provision($student->refresh(), $registrarName);
                $address = $provisioned['address'];

                return $record;
            });

            log_activity($admission, 'Approved', 'Approved admission for ' . $student->first_name . ' ' . $student->last_name . ' (receipt ' . $admission->downpayment_receipt . ', section assigned).');

            // Notice email goes in its own guarded step AFTER the commit
            // (same guarantee as the old approve): a mail outage must
            // never report an enrolled student as "failed to approve".
            $mailSent = app(FamilyEmailService::class)
                ->sendAdmissionApproval($student->refresh(), $mailMarker);

            $enrolledMsg = 'Admission approved for ' . $student->first_name . ' ' . $student->last_name . '. Student has been enrolled with ' . count($subjectIds) . ' subject(s).'
                . ($address !== null ? ' Institution email: ' . $address . '.' : '');

            if ($mailSent) {
                return ['approved' => true, 'enrollment' => $enrollment, 'address' => $address, 'mailSent' => true, 'message' => $enrolledMsg . ' Confirmation email sent.'];
            }

            return ['approved' => true, 'enrollment' => $enrollment, 'address' => $address, 'mailSent' => false, 'message' => $enrolledMsg . ' BUT the confirmation email could not be sent. Open the admission record and use Resend.'];
        } catch (\Exception $e) {
            Log::error('Full approval failed: ' . $e->getMessage(), [
                'admission_id' => $admission->id,
                'student_id' => $student?->id,
            ]);

            return ['approved' => false, 'error' => 'Failed to approve admission. Please try again.'];
        }
    }
}
