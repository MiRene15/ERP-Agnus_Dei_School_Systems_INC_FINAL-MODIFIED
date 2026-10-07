<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\BulkSectionAssignRequest;
use App\Models\Admission;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:search')->only('index');
    }

    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Section::with('adviser');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section_name', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('grade_level') && $request->grade_level !== 'All') {
            $query->where('grade_level', $request->grade_level);
        }

        $sections = $query->orderBy('grade_level')->orderBy('section_name')->get()->groupBy('grade_level');
        $gradeLevels = ['All', 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.sections-index-results', compact('sections', 'gradeLevels'))->render(),
            ]);
        }

        $assignYear = active_school_year();
        $assignEnrollments = Enrollment::with(['student', 'section'])
            ->where('status', 'Active')
            ->where('school_year', $assignYear)
            ->get()
            ->sortBy(fn($e) => ($e->student?->last_name ?? '') . ', ' . ($e->student?->first_name ?? ''))
            ->values();
        $assignSections = Section::where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('section_name')
            ->get();

        return view('portal.registrar.sections.index', compact('sections', 'gradeLevels', 'assignEnrollments', 'assignSections', 'assignYear'));
    }

    public function create()
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $teachers = User::where('role_id', 4)->orderBy('name')->get();
        return view('portal.registrar.sections.create', compact('gradeLevels', 'teachers'));
    }

    public function store(Request $request)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $data = $request->validate([
            'grade_level' => ['required', 'string', 'max:20', 'in:' . implode(',', $gradeLevels)],
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
            'adviser_id' => 'nullable|exists:users,id',
        ]);

        $exists = Section::where('grade_level', $data['grade_level'])
            ->where('section_name', $data['section_name'])->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Section {$data['section_name']} already exists for {$data['grade_level']}.");
        }

        // Advisers must be teacher accounts (the dropdown lists teachers only).
        if (!empty($data['adviser_id']) && (int) User::where('id', $data['adviser_id'])->value('role_id') !== 4) {
            return back()->withInput()->with('error', 'Adviser must be a teacher account.');
        }

        Section::create([
            'grade_level' => $data['grade_level'],
            'section_name' => $data['section_name'],
            'is_active' => $request->boolean('is_active', true),
            'adviser_id' => $data['adviser_id'] ?? null,
        ]);

        log_activity(new \App\Models\Section, 'Created', "Created section: {$data['section_name']} ({$data['grade_level']})");

        return redirect()->route('registrar.sections.index')
            ->with('success', "Section {$data['section_name']} created for {$data['grade_level']}.");
    }

    public function edit(Section $section)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $teachers = User::where('role_id', 4)->orderBy('name')->get();
        return view('portal.registrar.sections.edit', compact('section', 'gradeLevels', 'teachers'));
    }

    public function update(Request $request, Section $section)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $data = $request->validate([
            'grade_level' => ['required', 'string', 'max:20', 'in:' . implode(',', $gradeLevels)],
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
            'adviser_id' => 'nullable|exists:users,id',
        ]);

        $exists = Section::where('grade_level', $data['grade_level'])
            ->where('section_name', $data['section_name'])
            ->where('id', '!=', $section->id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Section {$data['section_name']} already exists for {$data['grade_level']}.");
        }

        // Advisers must be teacher accounts (the dropdown lists teachers only).
        if (!empty($data['adviser_id']) && (int) User::where('id', $data['adviser_id'])->value('role_id') !== 4) {
            return back()->withInput()->with('error', 'Adviser must be a teacher account.');
        }

        $section->update([
            'grade_level' => $data['grade_level'],
            'section_name' => $data['section_name'],
            'is_active' => $request->boolean('is_active', true),
            'adviser_id' => $data['adviser_id'] ?? null,
        ]);

        log_activity($section, 'Updated', "Updated section: {$section->section_name}");

        return redirect()->route('registrar.sections.index')
            ->with('success', "Section {$data['section_name']} updated.");
    }

    public function destroy(Section $section)
    {
        if ($section->enrollments()->where('status', 'Active')->exists()) {
            return back()->with('error', 'Cannot delete — section has active enrollments. Deactivate it instead.');
        }
        // Classes reference sections by (grade_level, section) name — no FK to
        // cascade, so deleting a class-bearing section would orphan class rows.
        $hasClasses = \App\Models\Classes::where('grade_level', $section->grade_level)
            ->where('section', $section->section_name)
            ->exists();
        if ($hasClasses) {
            return back()->with('error', 'Cannot delete — section still has classes. Deactivate it instead.');
        }
        $section->delete();
        log_activity($section, 'Deleted', "Deleted section: {$section->section_name} ({$section->grade_level})");
        return back()->with('success', 'Section deleted.');
    }

    public function bulkAssign(BulkSectionAssignRequest $request)
    {
        $data = $request->validated();
        $section = Section::findOrFail($data['section_id']);

        if (!$section->is_active) {
            return back()->with('error', 'Cannot assign — the target section is inactive.');
        }

        $result = DB::transaction(function () use ($data, $section) {
            $assigned = 0;
            $reassigned = 0;
            $skipped = 0;

            foreach (array_map('intval', $data['enrollment_ids']) as $enrollmentId) {
                $enrollment = Enrollment::with(['student', 'section'])->find($enrollmentId);

                if ($enrollment === null || $enrollment->status !== 'Active') {
                    $skipped++;
                    continue;
                }

                if (school_year_locked($enrollment->school_year)) {
                    $skipped++;
                    continue;
                }

                $gradeLevel = $enrollment->section?->grade_level;
                if ($gradeLevel === null) {
                    $gradeLevel = Admission::where('student_id', $enrollment->student_id)
                        ->where('school_year', $enrollment->school_year)
                        ->orderByDesc('id')
                        ->value('grade_level');
                }
                if ($gradeLevel !== null && $gradeLevel !== $section->grade_level) {
                    $skipped++;
                    continue;
                }

                $wasAssigned = $enrollment->section_id !== null;
                $enrollment->update(['section_id' => $section->id]);

                if ($wasAssigned) {
                    $reassigned++;
                } else {
                    $assigned++;
                }

                $name = $enrollment->student ? $enrollment->student->first_name . ' ' . $enrollment->student->last_name : "student #{$enrollment->student_id}";
                log_activity($enrollment, 'Section Assigned', auth()->user()->name . " (Registrar) assigned {$name} to {$section->grade_level} — {$section->section_name}.");
            }

            return ['assigned' => $assigned, 'reassigned' => $reassigned, 'skipped' => $skipped];
        });

        $message = "Bulk assign done: {$result['assigned']} assigned, {$result['reassigned']} reassigned to {$section->grade_level} — {$section->section_name}.";
        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped (inactive, locked year, or grade mismatch).";
        }

        return back()->with('success', $message);
    }
}
