<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\GradeUnlockRequest;
use Illuminate\Http\Request;

/**
 * Grade correction unlocks (role reform Phase 2c).
 * Teacher requests -> Principal/Registrar approves (submitted grades reopened)
 * -> Teacher corrects -> Teacher re-submits.
 */
class GradeUnlockController extends Controller
{
    // ─── Request (Teacher) ───────────────────────────────────────
    public function teacherIndex()
    {
        $classes = Classes::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->with('subject')
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get();

        $submittedPeriods = Grade::whereIn('class_id', $classes->pluck('id'))
            ->where('status', 'Submitted')
            ->select('class_id', 'grading_period')
            ->distinct()
            ->get()
            ->groupBy('class_id')
            ->map(fn($rows) => $rows->pluck('grading_period')->values());

        $requests = GradeUnlockRequest::with(['schoolClass.subject', 'reviewer'])
            ->where('requested_by', auth()->id())
            ->latest()
            ->paginate(20);

        return view('portal.teacher.grade-unlocks.index', compact('classes', 'submittedPeriods', 'requests'));
    }

    public function teacherStore(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'grading_period' => 'required|string|in:1st Term,2nd Term,3rd Term',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $class = Classes::findOrFail($data['class_id']);
        if ($class->teacher_id !== auth()->id()) {
            abort(403);
        }

        $submittedCount = Grade::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', 'Submitted')
            ->count();
        if ($submittedCount === 0) {
            return back()->with('error', 'Nothing to unlock — no submitted grades for that class and term (unsubmitted grades can still be edited directly).');
        }

        $open = GradeUnlockRequest::where('class_id', $class->id)
            ->where('grading_period', $data['grading_period'])
            ->where('status', GradeUnlockRequest::STATUS_PENDING)
            ->exists();
        if ($open) {
            return back()->with('error', 'An unlock request for this class and term is already pending review.');
        }

        $unlockRequest = GradeUnlockRequest::create([
            'class_id' => $class->id,
            'grading_period' => $data['grading_period'],
            'requested_by' => auth()->id(),
            'reason' => $data['reason'],
            'status' => GradeUnlockRequest::STATUS_PENDING,
        ]);

        log_activity($unlockRequest, 'Grade Unlock Requested', auth()->user()->name . " requested to reopen {$data['grading_period']} grades for {$class->subject->name} ({$class->grade_level} {$class->section}). Reason: {$data['reason']}");

        return back()->with('success', 'Unlock request sent to the Principal/Registrar for review.');
    }

    // ─── Review (Principal / Registrar) ──────────────────────────
    public function reviewIndex()
    {
        $pending = GradeUnlockRequest::with(['schoolClass.subject', 'schoolClass.teacher', 'requester'])
            ->where('status', GradeUnlockRequest::STATUS_PENDING)
            ->latest()
            ->get();

        $history = GradeUnlockRequest::with(['schoolClass.subject', 'requester', 'reviewer'])
            ->whereIn('status', [GradeUnlockRequest::STATUS_APPROVED, GradeUnlockRequest::STATUS_REJECTED])
            ->latest()
            ->take(50)
            ->get();

        return view('portal.registrar.grade-unlocks.index', compact('pending', 'history'));
    }

    public function approve(GradeUnlockRequest $unlockRequest)
    {
        if ($unlockRequest->status !== GradeUnlockRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        // Locked school years are frozen — reopening grades there is meaningless
        // (the teacher write guards would refuse every correction anyway).
        if ($unlockRequest->schoolClass && school_year_locked($unlockRequest->schoolClass->school_year)) {
            return back()->with('error', 'School year ' . $unlockRequest->schoolClass->school_year . ' is locked — unlocks can no longer be approved for it.');
        }

        $reopened = Grade::where('class_id', $unlockRequest->class_id)
            ->where('grading_period', $unlockRequest->grading_period)
            ->where('status', 'Submitted')
            ->update(['status' => 'Pending']);

        $unlockRequest->update([
            'status' => GradeUnlockRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $class = $unlockRequest->schoolClass;
        $label = $class ? "{$class->subject->name} ({$class->grade_level} {$class->section})" : "class #{$unlockRequest->class_id}";
        log_activity($unlockRequest, 'Grade Unlock Approved', auth()->user()->name . " reopened {$unlockRequest->grading_period} grades for {$label} — {$reopened} grade(s) returned to Pending for correction.");

        return back()->with('success', "Unlocked — {$reopened} grade(s) reopened. The teacher can now correct and re-submit.");
    }

    public function reject(GradeUnlockRequest $unlockRequest)
    {
        if ($unlockRequest->status !== GradeUnlockRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $unlockRequest->update([
            'status' => GradeUnlockRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        log_activity($unlockRequest, 'Grade Unlock Rejected', auth()->user()->name . ' rejected the grade unlock request for ' . $unlockRequest->grading_period . '.');

        return back()->with('success', 'Unlock request rejected — grades stay submitted.');
    }
}
