<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Section::with('adviser');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('section_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade_level') && $request->grade_level !== 'All') {
            $query->where('grade_level', $request->grade_level);
        }

        $sections = $query->orderBy('grade_level')->orderBy('section_name')->get()->groupBy('grade_level');
        $gradeLevels = ['All', 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.sections-index-results', compact('sections', 'gradeLevels'))->render(),
            ]);
        }

        return view('portal.admin.sections.index', compact('sections', 'gradeLevels'));
    }

    public function create()
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $teachers = User::where('role_id', 4)->orderBy('name')->get();
        return view('portal.admin.sections.create', compact('gradeLevels', 'teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
            'adviser_id' => 'nullable|exists:users,id',
        ]);

        $exists = Section::where('grade_level', $data['grade_level'])
            ->where('section_name', $data['section_name'])->exists();

        if ($exists) {
            return back()->withInput()->with('error', "Section {$data['section_name']} already exists for {$data['grade_level']}.");
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
        return view('portal.admin.sections.edit', compact('section', 'gradeLevels', 'teachers'));
    }

    public function update(Request $request, Section $section)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
            'adviser_id' => 'nullable|exists:users,id',
        ]);

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
        $section->delete();
        log_activity($section, 'Deleted', "Deleted section: {$section->section_name} ({$section->grade_level})");
        return back()->with('success', 'Section deleted.');
    }
}
