<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade_level') && $request->grade_level !== 'All') {
            $query->where('grade_level', $request->grade_level);
        }

        $subjects = $query->orderBy('grade_level')->orderBy('name')->get()->groupBy('grade_level');
        $gradeLevels = ['All', 'Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.admin.partials.subjects-index-results', compact('subjects', 'gradeLevels'))->render(),
            ]);
        }

        return view('portal.admin.subjects.index', compact('subjects', 'gradeLevels'));
    }

    public function create()
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        $gradeLevels = ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];
        return view('portal.admin.subjects.create', compact('categories', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code',
            'name'         => 'required|string|max:255',
            'grade_level'  => 'required|string|max:30',
            'category'     => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        Subject::create($data);

        log_activity(new \App\Models\Subject, 'Created', "Created subject: {$data['subject_code']} — {$data['name']}");

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject {$data['subject_code']} — {$data['name']} created.");
    }

    public function edit(Subject $subject)
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        $gradeLevels = ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];
        return view('portal.admin.subjects.edit', compact('subject', 'categories', 'gradeLevels'));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code,' . $subject->id,
            'name'         => 'required|string|max:255',
            'grade_level'  => 'required|string|max:30',
            'category'     => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        $subject->update($data);

        log_activity($subject, 'Updated', "Updated subject: {$data['subject_code']}");

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject {$data['subject_code']} updated.");
    }

    public function destroy(Subject $subject)
    {
        if ($subject->classes()->exists()) {
            return back()->with('error', 'Cannot delete — subject has active classes.');
        }
        $subject->delete();
        log_activity($subject, 'Deleted', "Deleted subject: {$subject->name}");
        return back()->with('success', 'Subject deleted.');
    }
}
