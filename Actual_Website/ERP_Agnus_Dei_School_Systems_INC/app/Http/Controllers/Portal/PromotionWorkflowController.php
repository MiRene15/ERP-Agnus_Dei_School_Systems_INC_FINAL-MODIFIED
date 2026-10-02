<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\PromotionProposal;
use App\Models\Student;
use App\Services\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * End-of-year promotion handoff (role reform Phase 2b).
 * Registrar prepares proposals -> Principal approves -> Directress signs off (executes).
 * Replaces the retired Admin\PromotionController (IT no longer runs promotion).
 */
class PromotionWorkflowController extends Controller
{
    private function activeEnrollments()
    {
        return Enrollment::with(['student.ledger', 'student', 'section', 'grades.schoolClass.subject'])
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->orderBy('school_year', 'desc')
            ->orderBy(Student::selectRaw("CONCAT(first_name, ' ', last_name)")
                ->whereColumn('students.id', 'enrollments.student_id')
            )
            ->get()
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown')
            ->sortBy(fn($_, $k) => PromotionService::gradeRank()[$k] ?? 99);
    }

    // ─── Registrar: prepare ──────────────────────────────────────
    public function registrarIndex()
    {
        $enrollments = $this->activeEnrollments();
        $actions = PromotionProposal::ACTIONS;
        $schoolYears = Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year');
        $passingGrade = (int) \App\Models\Setting::getValue('passing_grade', '75');

        $openProposals = PromotionProposal::whereIn('enrollment_id', $enrollments->flatten()->pluck('id'))
            ->whereNotIn('status', [PromotionProposal::STATUS_REJECTED, PromotionProposal::STATUS_EXECUTED])
            ->get()
            ->keyBy('enrollment_id');

        // Hold badges (batched — 3 queries for the whole roster).
        $holdMap = \App\Services\HoldService::forStudentIds($enrollments->flatten()->pluck('student_id'));

        return view('portal.registrar.promotion.index', compact('enrollments', 'actions', 'schoolYears', 'passingGrade', 'openProposals', 'holdMap'));
    }

    public function registrarPropose(Request $request)
    {
        $data = $request->validate([
            'actions' => 'required|array',
            'actions.*' => 'required|in:promote,retain,graduate,transfer,dropped',
            'school_year' => 'required|string|max:20',
            'reasons' => 'nullable|array',
            'reasons.*' => 'nullable|string|max:500',
        ]);

        // Locked school years are frozen — nothing moves into them.
        if (school_year_locked($data['school_year'])) {
            return redirect()->route('registrar.promotion.index')->with('error', 'Cannot propose — school year ' . $data['school_year'] . ' is locked.');
        }

        $created = 0;
        $errors = [];

        foreach ($data['actions'] as $enrollmentId => $action) {
            $enrollment = Enrollment::with('student')->find($enrollmentId);

            if (!$enrollment || $enrollment->status !== 'Active') {
                $errors[] = "Enrollment #{$enrollmentId} not found or not active.";
                continue;
            }

            $open = PromotionProposal::where('enrollment_id', $enrollmentId)
                ->whereNotIn('status', [PromotionProposal::STATUS_REJECTED, PromotionProposal::STATUS_EXECUTED])
                ->exists();
            if ($open) {
                $errors[] = "{$enrollment->student->first_name} {$enrollment->student->last_name}: already has an open proposal.";
                continue;
            }

            $reason = $data['reasons'][$enrollmentId] ?? null;
            if (in_array($action, ['transfer', 'dropped'], true) && empty(trim((string) $reason))) {
                $errors[] = "{$enrollment->student->first_name} {$enrollment->student->last_name}: Reason required for " . ($action === 'transfer' ? 'Transfer' : 'Dropped Out') . '.';
                continue;
            }

            $proposal = PromotionProposal::create([
                'enrollment_id' => $enrollmentId,
                'action' => $action,
                'school_year' => $data['school_year'],
                'reason' => $reason,
                'status' => PromotionProposal::STATUS_PROPOSED,
                'proposed_by' => auth()->id(),
            ]);

            log_activity($proposal, 'Promotion Proposed', auth()->user()->name . " (Registrar) proposed {$action} for {$enrollment->student->first_name} {$enrollment->student->last_name}.");
            $created++;
        }

        $message = "Proposed: {$created} student(s) sent to the Principal for approval.";
        if ($errors) {
            $message .= ' Skipped: ' . implode(' | ', $errors);
        }

        return redirect()->route('registrar.promotion.index')->with('success', $message);
    }

    // ─── Principal: approve ──────────────────────────────────────
    public function principalIndex()
    {
        $proposals = PromotionProposal::with(['enrollment.student.ledger', 'enrollment.section', 'enrollment.grades.schoolClass.subject', 'proposer'])
            ->where('status', PromotionProposal::STATUS_PROPOSED)
            ->latest()
            ->get();
        $passingGrade = (int) \App\Models\Setting::getValue('passing_grade', '75');

        return view('portal.principal.promotion.index', compact('proposals', 'passingGrade'));
    }

    public function principalApprove(PromotionProposal $proposal)
    {
        if ($proposal->status !== PromotionProposal::STATUS_PROPOSED) {
            return back()->with('error', 'Only proposed items can be approved.');
        }

        $proposal->update([
            'status' => PromotionProposal::STATUS_PRINCIPAL_APPROVED,
            'principal_by' => auth()->id(),
            'principal_at' => now(),
        ]);

        $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
        log_activity($proposal, 'Promotion Approved', auth()->user()->name . " (Principal) approved {$proposal->action} for {$name}.");

        return back()->with('success', "Approved {$proposal->action} for {$name} — sent to the Directress for sign-off.");
    }

    public function principalReject(PromotionProposal $proposal)
    {
        if ($proposal->status !== PromotionProposal::STATUS_PROPOSED) {
            return back()->with('error', 'Only proposed items can be rejected.');
        }

        $proposal->update(['status' => PromotionProposal::STATUS_REJECTED]);

        $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
        log_activity($proposal, 'Promotion Rejected', auth()->user()->name . " (Principal) rejected {$proposal->action} for {$name}.");

        return back()->with('success', "Rejected proposal for {$name}.");
    }

    // ─── Directress: sign off (executes) ─────────────────────────
    public function directressIndex()
    {
        $proposals = PromotionProposal::with(['enrollment.student.ledger', 'enrollment.section', 'proposer'])
            ->where('status', PromotionProposal::STATUS_PRINCIPAL_APPROVED)
            ->latest()
            ->get();

        return view('portal.directress.promotion.index', compact('proposals'));
    }

    public function directressSignoff(PromotionProposal $proposal)
    {
        if ($proposal->status !== PromotionProposal::STATUS_PRINCIPAL_APPROVED) {
            return back()->with('error', 'Only Principal-approved proposals can be signed off.');
        }

        // Holds block promotion until cleared (library / clinic / finance).
        $proposal->loadMissing('enrollment.student');
        $holds = $proposal->enrollment && $proposal->enrollment->student
            ? \App\Services\HoldService::forStudent($proposal->enrollment->student)
            : [];
        if (!empty($holds)) {
            return back()->with('error', 'Cannot execute — holds must be cleared first. ' . \App\Services\HoldService::blockingMessage($holds));
        }

        // Locked school years are frozen — nothing moves into them.
        if (school_year_locked($proposal->school_year)) {
            return back()->with('error', 'Cannot execute — school year ' . $proposal->school_year . ' is locked.');
        }

        $proposal->update([
            'status' => PromotionProposal::STATUS_DIRECTRESS_APPROVED,
            'directress_by' => auth()->id(),
            'directress_at' => now(),
        ]);

        $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;

        try {
            DB::transaction(function () use ($proposal) {
                PromotionService::execute($proposal->fresh('enrollment'));
            });
            $proposal->update([
                'status' => PromotionProposal::STATUS_EXECUTED,
                'executed_at' => now(),
            ]);
            log_activity($proposal, 'Promotion Executed', auth()->user()->name . " (Directress) signed off and executed {$proposal->action} for {$name}.");
            $message = "Signed off and executed {$proposal->action} for {$name}.";
        } catch (\Exception $e) {
            log_activity($proposal, 'Promotion Execution Failed', auth()->user()->name . " (Directress) signed off {$proposal->action} for {$name}, but execution failed: {$e->getMessage()}");
            return back()->with('error', "Signed off, but execution failed for {$name}: {$e->getMessage()}");
        }

        return back()->with('success', $message);
    }
}
