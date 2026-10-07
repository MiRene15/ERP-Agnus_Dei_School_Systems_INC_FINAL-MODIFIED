<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:search')->only('index');
    }

    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Subject::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('subject_code', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('grade_level') && $request->grade_level !== 'All') {
            $query->where('grade_level', $request->grade_level);
        }

        $subjects = $query->orderBy('grade_level')->orderBy('name')->get()->groupBy('grade_level');
        $gradeLevels = ['All', 'Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];

        // Principal browses read-only (Registrar owns subjects + sections together).
        $readOnly = request()->routeIs('principal.*');

        // Registrar's open change requests (change approval workflow).
        $pendingRequests = $readOnly ? collect() : \App\Models\SubjectChangeRequest::with('subject', 'requester')
            ->where('status', \App\Models\SubjectChangeRequest::STATUS_PENDING)
            ->latest()
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.subjects-index-results', compact('subjects', 'gradeLevels', 'readOnly'))->render(),
            ]);
        }

        return view('portal.registrar.subjects.index', compact('subjects', 'gradeLevels', 'readOnly', 'pendingRequests'));
    }

    public function create()
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        $gradeLevels = ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];
        return view('portal.registrar.subjects.create', compact('categories', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code',
            'name'         => 'required|string|max:255',
            'grade_level'  => 'required|string|max:30',
            'category'     => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        // Registrar stages; Principal approves before anything goes live.
        $dup = \App\Models\SubjectChangeRequest::where('action', \App\Models\SubjectChangeRequest::ACTION_CREATE)
            ->where('status', \App\Models\SubjectChangeRequest::STATUS_PENDING)
            ->where('payload->subject_code', $data['subject_code'])
            ->exists();
        if ($dup) {
            return back()->with('error', "Subject code {$data['subject_code']} already has a pending creation request.")->withInput();
        }

        $change = \App\Models\SubjectChangeRequest::create([
            'action' => \App\Models\SubjectChangeRequest::ACTION_CREATE,
            'subject_id' => null,
            'payload' => $data,
            'requested_by' => auth()->id(),
            'status' => \App\Models\SubjectChangeRequest::STATUS_PENDING,
        ]);

        log_activity($change, 'Subject Creation Requested', auth()->user()->name . " (Registrar) requested new subject: {$data['subject_code']} — {$data['name']}.");

        return redirect()->route('registrar.subjects.index')
            ->with('success', "Subject {$data['subject_code']} sent to the Principal for approval.");
    }

    public function edit(Subject $subject)
    {
        $categories = ['Core', 'Contextualized', 'Specialized', 'TVL'];
        $gradeLevels = ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','SHS'];
        return view('portal.registrar.subjects.edit', compact('subject', 'categories', 'gradeLevels'));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:20|unique:subjects,subject_code,' . $subject->id,
            'name'         => 'required|string|max:255',
            'grade_level'  => 'required|string|max:30',
            'category'     => 'required|in:Core,Contextualized,Specialized,TVL',
        ]);

        // Registrar stages; Principal approves before anything goes live.
        $open = \App\Models\SubjectChangeRequest::where('subject_id', $subject->id)
            ->whereIn('action', [\App\Models\SubjectChangeRequest::ACTION_UPDATE, \App\Models\SubjectChangeRequest::ACTION_DELETE])
            ->where('status', \App\Models\SubjectChangeRequest::STATUS_PENDING)
            ->exists();
        if ($open) {
            return back()->with('error', "Subject {$subject->subject_code} already has a pending change request.")->withInput();
        }

        $change = \App\Models\SubjectChangeRequest::create([
            'action' => \App\Models\SubjectChangeRequest::ACTION_UPDATE,
            'subject_id' => $subject->id,
            'payload' => $data,
            'requested_by' => auth()->id(),
            'status' => \App\Models\SubjectChangeRequest::STATUS_PENDING,
        ]);

        log_activity($change, 'Subject Update Requested', auth()->user()->name . " (Registrar) requested changes to subject: {$subject->subject_code}.");

        return redirect()->route('registrar.subjects.index')
            ->with('success', "Changes to {$subject->subject_code} sent to the Principal for approval.");
    }

    public function destroy(Subject $subject)
    {
        if ($subject->classes()->exists()) {
            return back()->with('error', 'Cannot delete — subject has active classes.');
        }

        // Registrar stages; Principal approves before anything goes live.
        $open = \App\Models\SubjectChangeRequest::where('subject_id', $subject->id)
            ->where('status', \App\Models\SubjectChangeRequest::STATUS_PENDING)
            ->exists();
        if ($open) {
            return back()->with('error', "Subject {$subject->subject_code} already has a pending change request.");
        }

        $change = \App\Models\SubjectChangeRequest::create([
            'action' => \App\Models\SubjectChangeRequest::ACTION_DELETE,
            'subject_id' => $subject->id,
            'payload' => null,
            'requested_by' => auth()->id(),
            'status' => \App\Models\SubjectChangeRequest::STATUS_PENDING,
        ]);

        log_activity($change, 'Subject Deletion Requested', auth()->user()->name . " (Registrar) requested deletion of subject: {$subject->subject_code} — {$subject->name}.");

        return back()->with('success', "Deletion of {$subject->subject_code} sent to the Principal for approval.");
    }
}
