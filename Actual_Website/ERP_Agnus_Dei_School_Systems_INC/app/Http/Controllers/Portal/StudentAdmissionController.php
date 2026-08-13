<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Admission;
use App\Models\Requirement;
use App\Services\SupabaseStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function create()
    {
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
            $draftStep = $draftAdmission->draft_data['_step'] ?? 1;
        }

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
            'strand' => 'nullable|in:STEM,ABM,HUMSS,GAS',
            'school_year' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
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

        return response()->json(['success' => true, 'step' => $data['_step']]);
    }

    public function store(Request $request)
    {
        $student = auth()->user()->student;

        if ($student->student_number) {
            return back()->with('error', 'You already have a student number.');
        }

        $data = $request->validate([
            'application_type' => 'required|in:New,Transferee',
            'grade_level' => 'required|string|max:20',
            'strand' => 'nullable|required_if:grade_level,Grade 11,Grade 12|in:STEM,ABM,HUMSS,GAS',
            'school_year' => 'required|string|max:20',

            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date',
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

        $student->update([
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'],
            'place_of_birth' => $data['place_of_birth'],
            'citizenship' => $data['citizenship'],
            'religion' => $data['religion'],
            'legacy_lrn' => $data['legacy_lrn'],
            'contact_number' => $data['contact_number'],
            'permanent_address' => $data['permanent_address'],
            'current_address' => ($data['same_as_permanent'] ?? false) ? $data['permanent_address'] : $data['current_address'],
            'father_name' => $data['father_name'],
            'father_occupation' => $data['father_occupation'],
            'mother_name' => $data['mother_name'],
            'mother_occupation' => $data['mother_occupation'],
            'guardian_name' => $data['guardian_name'],
            'guardian_contact' => $data['guardian_contact'],
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_number' => $data['emergency_contact_number'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'previous_school' => $data['previous_school'],
            'previous_school_address' => $data['previous_school_address'],
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
            $admission = $draft;
        } else {
            $admission = Admission::create([
                'student_id' => $student->id,
                'application_type' => $data['application_type'],
                'grade_level' => $data['grade_level'],
                'strand' => $data['strand'] ?? null,
                'school_year' => $data['school_year'],
                'status' => 'Pending',
            ]);
        }

        return redirect()->route('student.admission.status')
            ->with('success', 'Application submitted! Your application number is ' . $admission->application_number);
    }

    public function discardDraft(Request $request)
    {
        $student = auth()->user()->student;

        $draft = $student->admissions()->where('status', 'Draft')->latest()->first();

        if ($draft) {
            $draft->delete();
        }

        return redirect()->route('student.admission.create')
            ->with('success', 'Draft discarded. You can start a fresh application.');
    }

    public function status()
    {
        $student = auth()->user()->student;
        $admission = $student->admissions()->latest()->first();
        $requirements = $admission ? $admission->requirements()->get() : collect();

        $requiredDocs = ['PSA Birth Certificate', 'Form 138 (Report Card)', 'Good Moral Certificate'];
        $uploadedTypes = $requirements->pluck('document_type')->toArray();
        $allRequiredUploaded = empty(array_diff($requiredDocs, $uploadedTypes));

        return view('portal.student.admission-status', compact('student', 'admission', 'requirements', 'allRequiredUploaded'));
    }

    public function uploadRequirements(Request $request)
    {
        $student = auth()->user()->student;
        $admission = $student->admissions()->where('status', 'Pending')->latest()->firstOrFail();

        $data = $request->validate([
            'documents' => 'required|array',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $supabase = new SupabaseStorage();
        $count = 0;

        foreach ($request->file('documents') as $documentType => $file) {
            $existing = Requirement::where('admission_id', $admission->id)
                ->where('document_type', $documentType)
                ->first();

            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = 'requirements/' . $admission->id . '/' . $filename;

            $supabase->delete($path);

            $content = file_get_contents($file->getRealPath());
            $supabase->upload($path, $content);

            if ($existing) {
                $supabase->delete($existing->file_path);
                $existing->update([
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'status' => 'Under Review',
                ]);
            } else {
                Requirement::create([
                    'admission_id' => $admission->id,
                    'document_type' => $documentType,
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'status' => 'Under Review',
                ]);
            }

            $count++;
        }

        return back()->with('success', $count . ' document(s) uploaded successfully.');
    }
}
