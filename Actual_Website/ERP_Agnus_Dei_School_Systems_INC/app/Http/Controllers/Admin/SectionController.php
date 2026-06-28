<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::orderBy('grade_level')->orderBy('section_name')->get()->groupBy('grade_level');
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        return view('portal.admin.sections.index', compact('sections', 'gradeLevels'));
    }

    public function create()
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        return view('portal.admin.sections.create', compact('gradeLevels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
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
        ]);

        return redirect()->route('admin.sections.index')
            ->with('success', "Section {$data['section_name']} created for {$data['grade_level']}.");
    }

    public function edit(Section $section)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        return view('portal.admin.sections.edit', compact('section', 'gradeLevels'));
    }

    public function update(Request $request, Section $section)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'section_name' => 'required|string|max:50',
            'is_active' => 'boolean',
        ]);

        $section->update([
            'grade_level' => $data['grade_level'],
            'section_name' => $data['section_name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.sections.index')
            ->with('success', "Section {$data['section_name']} updated.");
    }

    public function destroy(Section $section)
    {
        if ($section->enrollments()->where('status', 'Active')->exists()) {
            return back()->with('error', 'Cannot delete — section has active enrollments. Deactivate it instead.');
        }
        $section->delete();
        return back()->with('success', 'Section deleted.');
    }
}
