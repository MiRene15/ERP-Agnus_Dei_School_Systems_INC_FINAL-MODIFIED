<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admission;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Submit-side rules for the admission application
 * (spec: enrollment-payment-gating-review-forward.md §4.1, §6).
 *
 * Submit is blocked until every required document is uploaded, and duplicate
 * applications for one child in one school year consolidate to a single
 * survivor with uploads merged.
 */
class AdmissionSubmissionService
{
    /**
     * Submit the application: update the student record, then move the draft
     * to Pending — or consolidate/reuse the existing Pending record when no
     * draft is left. Returns a result array (never throws for a gate block)
     * so the controller can flash the plain-language reason.
     *
     * @param array<string, mixed> $data
     * @return array{submitted: bool, admission?: Admission, error?: string}
     */
    public static function submit(Student $student, array $data): array
    {
        $data['contact_number'] = self::normalizePhone($data['contact_number'] ?? null);
        $data['guardian_contact'] = self::normalizePhone($data['guardian_contact'] ?? null);
        $data['emergency_contact_number'] = self::normalizePhone($data['emergency_contact_number'] ?? null);

        // Free-text detail only applies to Non-binary / Prefer not to say.
        $genderDetail = in_array($data['gender'] ?? null, ['Non-binary', 'Prefer not to say'], true)
            ? ($data['gender_detail'] ?? null)
            : null;

        return DB::transaction(function () use ($student, $data, $genderDetail) {
            // Gate: required uploads must be present before submit (§4.1, §6).
            $existing = $student->admissions()
                ->whereIn('status', ['Draft', 'Pending'])
                ->latest()
                ->first();

            $missing = $existing !== null
                ? AdmissionReviewService::missingRequiredDocuments($existing)
                : AdmissionReviewService::requiredDocumentTypes();

            if (! empty($missing)) {
                return [
                    'submitted' => false,
                    'error' => 'Missing required document(s): ' . implode(', ', $missing) . '. Upload them, then submit.',
                ];
            }

            $student->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'gender_detail' => $genderDetail,
                'personal_email' => $data['student_email'] ?? $student->personal_email,
                'date_of_birth' => $data['date_of_birth'],
                'place_of_birth' => $data['place_of_birth'] ?? null,
                'citizenship' => $data['citizenship'] ?? null,
                'religion' => $data['religion'] ?? null,
                'legacy_lrn' => $data['legacy_lrn'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'current_address' => ($data['same_as_permanent'] ?? false) ? ($data['permanent_address'] ?? null) : ($data['current_address'] ?? null),
                'father_name' => $data['father_name'] ?? null,
                'father_occupation' => $data['father_occupation'] ?? null,
                'mother_name' => $data['mother_name'] ?? null,
                'mother_occupation' => $data['mother_occupation'] ?? null,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_contact' => $data['guardian_contact'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_number' => $data['emergency_contact_number'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'previous_school' => $data['previous_school'] ?? null,
                'previous_school_address' => $data['previous_school_address'] ?? null,
            ]);

            $draft = $student->admissions()->where('status', 'Draft')->latest()->first();

            if ($draft !== null) {
                $draft->update([
                    'application_type' => $data['application_type'],
                    'grade_level' => $data['grade_level'],
                    'strand' => $data['strand'] ?? null,
                    'school_year' => $data['school_year'],
                    'status' => 'Pending',
                    'draft_data' => null,
                ]);
                $record = $draft;
            } else {
                $record = self::consolidateDuplicates($student, $data['school_year'])
                    ?? Admission::create([
                        'student_id' => $student->id,
                        'application_type' => $data['application_type'],
                        'grade_level' => $data['grade_level'],
                        'strand' => $data['strand'] ?? null,
                        'school_year' => $data['school_year'],
                        'status' => 'Pending',
                    ]);
            }

            // A re-submitted file (previously Returned, family supplied what
            // was missing) starts review with a clean note.
            if ($record->review_note !== null) {
                $record->review_note = null;
                $record->save();
            }

            log_activity($record, 'Admission Submitted', $student->first_name . ' ' . $student->last_name . ' submitted admission application ' . ($record->application_number ?? '#' . $record->id) . ' (' . $data['application_type'] . ', ' . $data['grade_level'] . ', SY ' . $data['school_year'] . ').');

            return ['submitted' => true, 'admission' => $record];
        });
    }

    /**
     * A Returned file whose family has now supplied every missing required
     * document goes back to Pending so review resumes. Verified files are
     * never touched; the return note is cleared.
     */
    public static function resumeIfComplete(Admission $admission): void
    {
        if ($admission->status !== 'Returned') {
            return;
        }

        if (! empty(AdmissionReviewService::missingRequiredDocuments($admission))) {
            return;
        }

        $admission->status = 'Pending';
        $admission->review_note = null;
        $admission->save();

        log_activity($admission, 'Returned File Back Under Review', 'Family uploaded the missing document(s); admission ' . ($admission->application_number ?? '#' . $admission->id) . ' is Pending again.');
    }

    /**
     * One live application per child per school year: the earliest Pending
     * record survives, its uploads win on a document-type conflict, the other
     * records' uploads transfer over, and the losers are removed.
     */
    private static function consolidateDuplicates(Student $student, string $schoolYear): ?Admission
    {
        $pending = $student->admissions()
            ->where('school_year', $schoolYear)
            ->where('status', 'Pending')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($pending->isEmpty()) {
            return null;
        }

        $survivor = $pending->first();

        foreach ($pending->skip(1) as $loser) {
            foreach ($loser->requirements as $requirement) {
                $survivorHas = $survivor->requirements()
                    ->where('document_type', $requirement->document_type)
                    ->exists();

                if ($survivorHas) {
                    $requirement->delete();
                } else {
                    $requirement->admission_id = $survivor->id;
                    $requirement->save();
                }
            }

            log_activity($survivor, 'Duplicate Consolidated', 'Duplicate admission #' . $loser->id . ' merged into ' . ($survivor->application_number ?? '#' . $survivor->id) . ' for SY ' . $schoolYear . ' — uploads consolidated, duplicate removed.');

            $loser->delete();
        }

        return $survivor;
    }

    private static function normalizePhone(?string $raw): ?string
    {
        if (!$raw || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+63' . substr($digits, 1);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            return '+63' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '63')) {
            return '+' . $digits;
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '63')) {
            return '+' . $digits;
        }

        return $raw;
    }
}
