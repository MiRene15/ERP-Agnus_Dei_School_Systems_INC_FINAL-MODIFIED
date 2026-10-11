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
        $sourceYear = (string) (request('source_year', active_school_year()));
        $gradeFilter = (string) (request('grade_level', 'All'));
        $search = trim((string) request('search', ''));

        // Counts-first: one lightweight grouped query, no eager loads.
        $gradeCounts = Enrollment::query()
            ->join('sections', 'sections.id', '=', 'enrollments.section_id')
            ->where('enrollments.status', 'Active')
            ->where('enrollments.school_year', $sourceYear)
            ->groupBy('sections.grade_level')
            ->selectRaw('sections.grade_level as grade_level, COUNT(*) as total')
            ->pluck('total', 'grade_level');

        $query = Enrollment::with([
                'student:id,first_name,last_name,student_number',
                'section:id,grade_level,section_name',
                'grades:id,enrollment_id,class_id,final_grade',
            ])
            ->where('status', 'Active')
            ->where('school_year', $sourceYear)
            ->when($gradeFilter !== 'All', function ($q) use ($gradeFilter) {
                $q->whereHas('section', fn($sq) => $sq->where('grade_level', $gradeFilter));
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('student_number', 'ilike', "%{$search}%");
                });
            })
            ->orderBy(Student::selectRaw("CONCAT(first_name, ' ', last_name)")
                ->whereColumn('students.id', 'enrollments.student_id')
            );

        $page = $query->paginate(100)->withQueryString();
        $enrollments = $page->getCollection()
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown')
            ->sortBy(fn($_, $k) => PromotionService::gradeRank()[$k] ?? 99);

        $actions = PromotionProposal::ACTIONS;
        $schoolYears = Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year');
        $passingGrade = (int) \App\Models\Setting::getValue('passing_grade', '75');

        $openProposals = PromotionProposal::whereIn('enrollment_id', $enrollments->flatten()->pluck('id'))
            ->whereNotIn('status', [PromotionProposal::STATUS_REJECTED, PromotionProposal::STATUS_EXECUTED])
            ->get()
            ->keyBy('enrollment_id');

        // Hold badges (batched — 3 queries for the shown page).
        $holdMap = \App\Services\HoldService::forStudentIds($enrollments->flatten()->pluck('student_id'));

        $sectionOccupancy = \App\Services\PromotionService::sectionOccupancy($sourceYear);

        $duplicatePairs = self::duplicatePairs($sourceYear);

        return view('portal.registrar.promotion.index', compact('enrollments', 'actions', 'schoolYears', 'passingGrade', 'openProposals', 'holdMap', 'gradeCounts', 'page', 'sourceYear', 'gradeFilter', 'search', 'sectionOccupancy', 'duplicatePairs'));
    }

    public function registrarMergeDuplicate(Request $request)
    {
        $data = $request->validate([
            'survivor_id' => 'required|integer|exists:students,id',
            'loser_id' => 'required|integer|exists:students,id',
        ]);
        $result = \App\Services\StudentMergeService::merge(
            (int) $data['survivor_id'],
            (int) $data['loser_id'],
            auth()->user()->name
        );
        return back()->with($result['merged'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Same first+last name (case-insensitive) among Active enrollments for
     * the year. Returns pairs with both student records for survivor-pick.
     * @return array<int, array{key: string, students: \Illuminate\Support\Collection}>
     */
    private static function duplicatePairs(string $schoolYear): array
    {
        $rows = Enrollment::with(['student:id,first_name,last_name,student_number,legacy_lrn,date_of_birth', 'section:id,grade_level,section_name'])
            ->where('status', 'Active')
            ->where('school_year', $schoolYear)
            ->get()
            ->groupBy(fn($e) => mb_strtolower(trim($e->student->first_name . '|' . $e->student->last_name)))
            ->filter(fn($g) => $g->pluck('student_id')->unique()->count() > 1)
            ->take(20);
        $out = [];
        foreach ($rows as $key => $group) {
            $out[] = ['key' => $key, 'students' => $group->unique('student_id')->take(3)];
        }
        return $out;
    }

    public function registrarPropose(Request $request)
    {
        $data = $request->validate([
            'actions' => 'required|array',
            'actions.*' => 'nullable|in:promote,retain,graduate,transfer,dropped',
            'school_year' => 'required|string|max:20',
            'reasons' => 'nullable|array',
            'reasons.*' => 'nullable|string|max:500',
        ]);

        // Skip-blank rows are selection aids only — only non-blank rows propose.
        $data['actions'] = array_filter($data['actions'] ?? [], fn($a) => in_array($a, ['promote', 'retain', 'graduate', 'transfer', 'dropped'], true));
        if (empty($data['actions'])) {
            return redirect()->route('registrar.promotion.index')->with('error', 'Nothing proposed — tick at least one student and pick an action (Promote / Retain / Graduate / Transfer / Dropped Out).');
        }

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

    public function registrarBatchQualified(Request $request)
    {
        $data = $request->validate([
            'school_year' => 'required|string|max:20',
        ]);

        if (school_year_locked($data['school_year'])) {
            return redirect()->route('registrar.promotion.index')->with('error', 'Cannot propose — school year ' . $data['school_year'] . ' is locked.');
        }

        $passing = (int) \App\Models\Setting::getValue('passing_grade', '75');

        $created = 0;
        $skipped = 0;

        foreach ($this->activeEnrollments()->flatten() as $enrollment) {
            $gradeLevel = $enrollment->section?->grade_level ?? 'Unknown';
            $grades = $enrollment->grades ?? collect();
            $finals = $grades->groupBy('class_id')->map(fn($g) => round($g->avg('final_grade'), 2));
            $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
            $failCount = $finals->filter(fn($f) => $f < $passing)->count();
            if ($avg === null || $avg < $passing || $failCount > 0) {
                $skipped++;
                continue;
            }

            $open = PromotionProposal::where('enrollment_id', $enrollment->id)
                ->whereNotIn('status', [PromotionProposal::STATUS_REJECTED, PromotionProposal::STATUS_EXECUTED])
                ->exists();
            if ($open) {
                $skipped++;
                continue;
            }

            $action = $gradeLevel === 'Grade 12' ? 'graduate' : 'promote';

            $proposal = PromotionProposal::create([
                'enrollment_id' => $enrollment->id,
                'action' => $action,
                'school_year' => $data['school_year'],
                'reason' => null,
                'status' => PromotionProposal::STATUS_PROPOSED,
                'proposed_by' => auth()->id(),
            ]);

            log_activity($proposal, 'Promotion Proposed', auth()->user()->name . " (Registrar) batch-proposed {$action} for {$enrollment->student->first_name} {$enrollment->student->last_name}.");
            $created++;
        }

        if ($created === 0) {
            return redirect()->route('registrar.promotion.index')->with('error', 'No qualified students found for this school year — nothing was proposed.');
        }

        $message = "Batch proposed: {$created} qualified student(s) sent to the Principal for approval.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (unqualified or already proposed).";
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
        $yearSummary = self::yearSummary();

        return view('portal.principal.promotion.index', compact('proposals', 'passingGrade', 'yearSummary'));
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

    public function principalBulkApprove(Request $request)
    {
        $data = $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer|exists:promotion_proposals,id',
        ]);
        $approved = 0;
        $skipped = 0;
        foreach ($data['selected'] as $id) {
            $proposal = PromotionProposal::find($id);
            if ($proposal === null || $proposal->status !== PromotionProposal::STATUS_PROPOSED) {
                $skipped++;
                continue;
            }
            $proposal->update([
                'status' => PromotionProposal::STATUS_PRINCIPAL_APPROVED,
                'principal_by' => auth()->id(),
                'principal_at' => now(),
            ]);
            $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
            log_activity($proposal, 'Promotion Approved', auth()->user()->name . " (Principal) bulk-approved {$proposal->action} for {$name}.");
            $approved++;
        }
        $message = "Bulk approved: {$approved} proposal(s) sent to the Directress for sign-off.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already decided).";
        }
        return back()->with('success', $message);
    }

    public function principalBulkReject(Request $request)
    {
        $data = $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer|exists:promotion_proposals,id',
        ]);
        $rejected = 0;
        $skipped = 0;
        foreach ($data['selected'] as $id) {
            $proposal = PromotionProposal::find($id);
            if ($proposal === null || $proposal->status !== PromotionProposal::STATUS_PROPOSED) {
                $skipped++;
                continue;
            }
            $proposal->update(['status' => PromotionProposal::STATUS_REJECTED]);
            $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
            log_activity($proposal, 'Promotion Rejected', auth()->user()->name . " (Principal) bulk-rejected {$proposal->action} for {$name}.");
            $rejected++;
        }
        $message = "Bulk rejected: {$rejected} proposal(s) returned to Registrar.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already decided).";
        }
        return back()->with('success', $message);
    }

    // ─── Directress: sign off (executes) ─────────────────────────
    public function directressIndex()
    {
        $proposals = PromotionProposal::with(['enrollment.student.ledger', 'enrollment.section', 'proposer'])
            ->where('status', PromotionProposal::STATUS_PRINCIPAL_APPROVED)
            ->latest()
            ->get();
        $yearSummary = self::yearSummary();

        return view('portal.directress.promotion.index', compact('proposals', 'yearSummary'));
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

    public function directressBulkSignoff(Request $request)
    {
        $data = $request->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer|exists:promotion_proposals,id',
        ]);
        $executed = 0;
        $skipped = 0;
        $errors = [];
        foreach ($data['selected'] as $id) {
            $proposal = PromotionProposal::with('enrollment.student')->find($id);
            if ($proposal === null) {
                $skipped++;
                continue;
            }
            if ($proposal->status === PromotionProposal::STATUS_EXECUTED) {
                $skipped++; // resume-safe: re-run skips already-moved
                continue;
            }
            if ($proposal->status !== PromotionProposal::STATUS_PRINCIPAL_APPROVED) {
                $skipped++;
                continue;
            }
            $holds = $proposal->enrollment && $proposal->enrollment->student
                ? \App\Services\HoldService::forStudent($proposal->enrollment->student)
                : [];
            if (!empty($holds)) {
                $errors[] = "#{$id}: holds must be cleared first.";
                continue;
            }
            if (school_year_locked($proposal->school_year)) {
                $errors[] = "#{$id}: school year {$proposal->school_year} is locked.";
                continue;
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
                $proposal->update(['status' => PromotionProposal::STATUS_EXECUTED, 'executed_at' => now()]);
                log_activity($proposal, 'Promotion Executed', auth()->user()->name . " (Directress) bulk signed off and executed {$proposal->action} for {$name}.");
                $executed++;
            } catch (\Exception $e) {
                log_activity($proposal, 'Promotion Execution Failed', auth()->user()->name . " (Directress) bulk signed off {$proposal->action} for {$name}, but execution failed: {$e->getMessage()}");
                $errors[] = "{$name}: {$e->getMessage()}";
            }
        }
        $message = "Signed off and executed {$executed} student(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already moved or not approved).";
        }
        if ($errors) {
            $message .= ' Failed: ' . implode(' | ', $errors);
            return back()->with('error', $message);
        }
        return back()->with('success', $message);
    }

    /**
     * Year-over-year summary (read-only): executed counts by action per
     * school year, current year pre-selected first.
     * @return array<string, array<string, int>>
     */
    private static function yearSummary(): array
    {
        $rows = PromotionProposal::where('status', PromotionProposal::STATUS_EXECUTED)
            ->selectRaw('school_year, action, COUNT(*) as total')
            ->groupBy('school_year', 'action')
            ->orderBy('school_year', 'desc')
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $out[$row->school_year][$row->action] = (int) $row->total;
        }
        return $out;
    }
}
