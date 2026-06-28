<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('category')->orderBy('name')->get()->groupBy('category');
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        return view('portal.admin.subjects.index', compact('subjects', 'categories'));
    }

    public function create()
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        return view('portal.admin.subjects.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code',
            'name' => 'required|string|max:255',
            'category' => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        Subject::create($data);

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject {$data['subject_code']} — {$data['name']} created.");
    }

    public function edit(Subject $subject)
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        return view('portal.admin.subjects.edit', compact('subject', 'categories'));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code,' . $subject->id,
            'name' => 'required|string|max:255',
            'category' => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        $subject->update($data);

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject {$data['subject_code']} updated.");
    }

    public function destroy(Subject $subject)
    {
        if ($subject->classes()->exists()) {
            return back()->with('error', 'Cannot delete — subject has active classes.');
        }
        $subject->delete();
        return back()->with('success', 'Subject deleted.');
    }
}
