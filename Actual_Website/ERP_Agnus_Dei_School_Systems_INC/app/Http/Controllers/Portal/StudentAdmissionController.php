<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreAdmissionRequest;
use App\Models\Student;
use App\Models\Admission;
use App\Models\Requirement;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentAdmissionController extends Controller
{
    protected function normalizePhone(?string $raw): ?string
    {
        if (!$raw || trim($raw) === '') return null;
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

    public function create(): View|RedirectResponse
    {
        if (Setting::getValue('enrollment_open', '1') === '0') {
            return view('portal.student.admission-closed');
        }

        $student = auth()->user()->student;

        if ($student->student_number) {
            return redirect()->route('student.dashboard')
                ->with('error', 'You already have a student number and cannot submit a new student application.');
        }

        $pendingAdmission = $student->admissions()->where('status', 'Pending')->latest()->first();
        $draftAdmission = $pendingAdmission ? null : $student->admissions()->where('status', 'Draft')->latest()->first();

        $draftData = null;
        $draftStep = 1;
        if ($draftAdmission) {
            $draftData = $draftAdmission->draft_data;
            $draftStep = (int) ($draftAdmission->draft_data['_step'] ?? 1);
        }

        // A failed submit flashes every typed answer (store() uses
        // withInput on all failure paths). Prefer it over the saved draft
        // so Step 6 values typed just before Submit are never lost.
        $flashed = session()->getOldInput();
        if (is_array($flashed) && $flashed !== []) {
            $known = [
                'application_type', 'grade_level', 'strand', 'school_year',
                'first_name', 'middle_name', 'last_name', 'gender', 'gender_detail',
                'date_of_birth', 'place_of_birth', 'citizenship', 'religion',
                'legacy_lrn', 'contact_number',
                'permanent_address', 'same_as_permanent', 'current_address',
                'father_name', 'father_occupation', 'mother_name', 'mother_occupation',
                'guardian_name', 'guardian_contact',
                'emergency_contact_name', 'emergency_contact_number', 'emergency_contact_relationship',
                'previous_school', 'previous_school_address',
            ];
            $draftData = is_array($draftData) ? $draftData : [];
            foreach ($known as $key) {
                if (array_key_exists($key, $flashed) && $flashed[$key] !== null) {
                    $draftData[$key] = $flashed[$key];
                }
            }
            // An unchecked "same as permanent" box posts no key at all, so
            // its absence in flashed input means unchecked — not "keep draft".
            if (! array_key_exists('same_as_permanent', $flashed)) {
                $draftData['same_as_permanent'] = false;
            }
        }

        // Reopen on the step holding the first validation problem so the
        // family sees the plain message instead of an empty-looking form.
        if (view()->shared('errors') !== null && count($errors = view()->shared('errors')) > 0) {
            $stepForField = [
                'application_type' => 1, 'grade_level' => 1, 'strand' => 1, 'school_year' => 1,
                'first_name' => 2, 'middle_name' => 2, 'last_name' => 2, 'gender' => 2,
                'gender_detail' => 2, 'date_of_birth' => 2, 'place_of_birth' => 2,
                'citizenship' => 2, 'religion' => 2, 'legacy_lrn' => 2, 'contact_number' => 2,
                'permanent_address' => 3, 'same_as_permanent' => 3, 'current_address' => 3,
                'father_name' => 4, 'father_occupation' => 4, 'mother_name' => 4,
                'mother_occupation' => 4, 'guardian_name' => 4, 'guardian_contact' => 4,
                'emergency_contact_name' => 5, 'emergency_contact_number' => 5,
                'emergency_contact_relationship' => 5,
                'previous_school' => 6, 'previous_school_address' => 6,
            ];
            foreach ($errors->keys() as $failed) {
                $base = explode('.', (string) $failed)[0];
                if (isset($stepForField[$base])) {
                    $draftStep = $stepForField[$base];
                    break;
                }
            }
        }

        $draftStep = min(6, max(1, $draftStep));

        return view('portal.student.admission-apply', compact('student', 'pendingAdmission', 'draftAdmission', 'draftData', 'draftStep'));
    }

    public function saveDraft(Request $request)
    {
        $student = auth()->user()->student;

        if ($student->student_number) {
            return response()->json(['error' => 'Already admitted'], 422);
        }

        $data = $request->validate([
            '_step' => 'required|integer|min:1|max:6',
            'application_type' => 'nullable|in:New,Transferee',
            'grade_level' => 'nullable|string|max:20',
            'strand' => 'nullable|in:Arts, Social Sciences, and Humanities,Business and Entrepreneurship',
            'school_year' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'gender' => 'nullable|in:Male,Female,Non-binary,Prefer not to say',
            'gender_detail' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|after_or_equal:1950-01-01|before_or_equal:today',
            'place_of_birth' => 'nullable|string|max:255',
            'citizenship' => 'nullable|string|max:100',
            'religion' => 'nullable|string|max:100',
            'legacy_lrn' => 'nullable|digits:12',
            'contact_number' => 'nullable|string|max:15',
            'permanent_address' => 'nullable|string|max:500',
            'same_as_permanent' => 'nullable|boolean',
            'current_address' => 'nullable|string|max:500',
            'father_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_occupation' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_contact' => 'nullable|string|max:15',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:15',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'previous_school_address' => 'nullable|string|max:500',
        ]);

        $data['contact_number'] = $this->normalizePhone($data['contact_number'] ?? null);
        $data['guardian_contact'] = $this->normalizePhone($data['guardian_contact'] ?? null);
        $data['emergency_contact_number'] = $this->normalizePhone($data['emergency_contact_number'] ?? null);

        $admission = $student->admissions()->where('status', 'Draft')->latest()->first();

        if ($admission) {
            $admission->update([
                'application_type' => $data['application_type'] ?? $admission->application_type,
                'grade_level' => $data['grade_level'] ?? $admission->grade_level,
                'strand' => $data['strand'] ?? $admission->strand,
                'school_year' => $data['school_year'] ?? $admission->school_year,
                'draft_data' => $data,
            ]);
        } else {
            $admission = Admission::create([
                'student_id' => $student->id,
                'application_type' => $data['application_type'] ?? 'New',
                'grade_level' => $data['grade_level'] ?? '',
                'strand' => $data['strand'] ?? null,
                'school_year' => $data['school_year'] ?? active_school_year(),
                'status' => 'Draft',
                'draft_data' => $data,
            ]);
        }

        log_activity($admission, 'Admission Draft Saved', auth()->user()->name . ' saved admission draft at step ' . $data['_step'] . '.');

        return response()->json(['success' => true, 'step' => $data['_step']]);
    }

    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        if (Setting::getValue('enrollment_open', '1') === '0') {
            return back()->withInput()->with('error', 'Enrollment is currently closed. Please try again when enrollment reopens.');
        }

        $student = auth()->user()->student;

        if ($student->student_number) {
            return back()->withInput()->with('error', 'You already have a student number.');
        }

        $data = $request->validated();

        $data['contact_number'] = $this->normalizePhone($data['contact_number'] ?? null);
        $data['guardian_contact'] = $this->normalizePhone($data['guardian_contact'] ?? null);
        $data['emergency_contact_number'] = $this->normalizePhone($data['emergency_contact_number'] ?? null);

        // Free-text detail only applies to Non-binary / Prefer not to say.
        $genderDetail = in_array($data['gender'] ?? null, ['Non-binary', 'Prefer not to say'], true)
            ? ($data['gender_detail'] ?? null)
            : null;

        $admission = DB::transaction(function () use ($student, $data, $genderDetail) {
            $student->update([
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'gender' => $data['gender'],
                'gender_detail' => $genderDetail,
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

            if ($draft) {
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
                // Second tap with a fresh reference but no draft left (already
                // Pending) must not mint a second Pending row.
                $existing = $student->admissions()->where('status', 'Pending')->latest()->first();
                $record = $existing ?? Admission::create([
                    'student_id' => $student->id,
                    'application_type' => $data['application_type'],
                    'grade_level' => $data['grade_level'],
                    'strand' => $data['strand'] ?? null,
                    'school_year' => $data['school_year'],
                    'status' => 'Pending',
                ]);
            }

            log_activity($record, 'Admission Submitted', $student->first_name . ' ' . $student->last_name . ' submitted admission application ' . ($record->application_number ?? '#' . $record->id) . ' (' . $data['application_type'] . ', ' . $data['grade_level'] . ', SY ' . $data['school_year'] . ').');

            return $record;
        });

        return redirect()->to(route('student.admission.status') . '#upload-requirements')
            ->with('success', 'Application submitted! Your application number is ' . $admission->application_number . '. Upload your requirements next.');
    }

    public function discardDraft(Request $request)
    {
        $student = auth()->user()->student;

        $draft = $student->admissions()->where('status', 'Draft')->latest()->first();

        if ($draft) {
            log_activity($draft, 'Admission Draft Discarded', $student->first_name . ' ' . $student->last_name . ' discarded admission draft #' . $draft->id . '.');
            $draft->delete();
        }

        return redirect()->route('student.admission.create')
            ->with('success', 'Draft discarded. You can start a fresh application.');
    }

    public function status(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student = auth()->user()->student;
        $admission = $student->admissions()->latest()->first();
        $requirements = $admission ? $admission->requirements()->select('id', 'document_type', 'original_filename', 'mime_type', 'file_size', 'status', 'admission_id')->get() : collect();

        $requiredDocs = ['PSA Birth Certificate', 'Form 138 (Report Card)', 'Good Moral Certificate'];
        $uploadedTypes = $requirements->pluck('document_type')->toArray();
        $allRequiredUploaded = empty(array_diff($requiredDocs, $uploadedTypes));

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.student.partials.admission-status-results', compact('student', 'admission', 'requirements', 'allRequiredUploaded'))->render(),
            ]);
        }

        return view('portal.student.admission-status', compact('student', 'admission', 'requirements', 'allRequiredUploaded'));
    }

    public function uploadRequirements(Request $request)
    {
        $student = auth()->user()->student;
        $admission = $student->admissions()->where('status', 'Pending')->latest()->firstOrFail();

        if ($request->isMethod('post') && empty($request->all()) && $request->headers->get('content-length', 0) > 0) {
            return back()->with('error', 'The total upload size is too large. Please upload files one at a time, or compress your images to under 5MB each. Max total POST size is 12MB.');
        }

        $data = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $count = 0;

        foreach ($request->file('documents') as $documentType => $file) {
            $existing = Requirement::where('admission_id', $admission->id)
                ->where('document_type', $documentType)
                ->first();

            $fileData = [
                'file_content' => DB::raw("'\\x" . bin2hex($file->getContent()) . "'::bytea"),
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'status' => 'Under Review',
            ];

            if ($existing) {
                $existing->update($fileData);
            } else {
                Requirement::create(array_merge($fileData, [
                    'admission_id' => $admission->id,
                    'document_type' => $documentType,
                ]));
            }

            $count++;
        }

        log_activity($admission, 'Requirements Uploaded', auth()->user()->name . ' uploaded ' . $count . ' admission document(s) for admission #' . $admission->id . '.');

        return back()->with('success', $count . ' document(s) uploaded successfully.');
    }

    public function viewRequirement(Requirement $requirement)
    {
        $user = auth()->user();
        $admission = $requirement->admission;

        $isStudent = $user->role_id === 7 && $admission->student_id === $user->student?->id;
        // Admission documents: student (own) + Registrar only. Cashier access removed.
        $isRegistrar = $user->role_id === 2;

        if (!$isStudent && !$isRegistrar) {
            abort(403);
        }

        if (!$requirement->file_content) {
            abort(404);
        }

        $content = $requirement->file_content;
        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        return response($content, 200, [
            'Content-Type' => $requirement->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . ($requirement->original_filename ?? 'document') . '"',
        ]);
    }
}
