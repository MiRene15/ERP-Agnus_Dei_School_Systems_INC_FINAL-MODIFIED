<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\SubjectChangeRequest;

/**
 * Subject-change approvals: Registrar stages every subject create/update/delete,
 * Principal approves (applies to the live table) or rejects. Nothing goes live
 * without Principal approval.
 */
class SubjectApprovalController extends Controller
{
    public function index()
    {
        $pending = SubjectChangeRequest::with('subject', 'requester')
            ->where('status', SubjectChangeRequest::STATUS_PENDING)
            ->latest()
            ->get();

        $history = SubjectChangeRequest::with('subject', 'requester', 'reviewer')
            ->whereIn('status', [SubjectChangeRequest::STATUS_APPROVED, SubjectChangeRequest::STATUS_REJECTED])
            ->latest()
            ->take(50)
            ->get();

        return view('portal.principal.subject-approvals.index', compact('pending', 'history'));
    }

    public function approve(SubjectChangeRequest $changeRequest)
    {
        if ($changeRequest->status !== SubjectChangeRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $oldGradeLevel = $changeRequest->subject?->grade_level;
        try {
            $label = $this->apply($changeRequest);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not apply: ' . $e->getMessage());
        }

        $changeRequest->update([
            'status' => SubjectChangeRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $payloadGradeLevel = $changeRequest->payload['grade_level'] ?? null;
        foreach (array_unique(array_filter([$oldGradeLevel, $payloadGradeLevel])) as $gradeLevel) {
            \Illuminate\Support\Facades\Cache::forget("subject_list:{$gradeLevel}");
        }

        log_activity($changeRequest, 'Subject Change Approved', auth()->user()->name . " (Principal) approved and applied {$changeRequest->action}: {$label}.");

        return back()->with('success', "Approved and applied {$changeRequest->action}: {$label}.");
    }

    public function reject(SubjectChangeRequest $changeRequest)
    {
        if ($changeRequest->status !== SubjectChangeRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $changeRequest->update([
            'status' => SubjectChangeRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        log_activity($changeRequest, 'Subject Change Rejected', auth()->user()->name . " (Principal) rejected {$changeRequest->action} request #" . $changeRequest->id . ".");

        return back()->with('success', 'Change request rejected — live subjects untouched.');
    }

    /**
     * Apply an approved request to the live subjects table. Throws on rule violations.
     */
    private function apply(SubjectChangeRequest $changeRequest): string
    {
        $payload = $changeRequest->payload ?? [];

        if ($changeRequest->action === SubjectChangeRequest::ACTION_CREATE) {
            if (Subject::where('subject_code', $payload['subject_code'] ?? '')->exists()) {
                throw new \Exception("Subject code {$payload['subject_code']} was taken while this request was pending.");
            }
            $subject = Subject::create([
                'subject_code' => $payload['subject_code'],
                'name' => $payload['name'],
                'grade_level' => $payload['grade_level'],
                'category' => $payload['category'],
            ]);
            $changeRequest->subject()->associate($subject);
            $changeRequest->save();
            return "{$subject->subject_code} — {$subject->name}";
        }

        $subject = $changeRequest->subject;
        if (!$subject) {
            throw new \Exception('Target subject no longer exists.');
        }

        if ($changeRequest->action === SubjectChangeRequest::ACTION_UPDATE) {
            if (Subject::where('subject_code', $payload['subject_code'] ?? '')->where('id', '!=', $subject->id)->exists()) {
                throw new \Exception("Subject code {$payload['subject_code']} is already in use.");
            }
            $subject->update([
                'subject_code' => $payload['subject_code'],
                'name' => $payload['name'],
                'grade_level' => $payload['grade_level'],
                'category' => $payload['category'],
            ]);
            return "{$subject->subject_code} — {$subject->name}";
        }

        if ($changeRequest->action === SubjectChangeRequest::ACTION_DELETE) {
            if ($subject->classes()->exists()) {
                throw new \Exception("{$subject->subject_code} now has classes — cannot delete.");
            }
            $label = "{$subject->subject_code} — {$subject->name}";
            $subject->delete();
            return $label . ' (deleted)';
        }

        throw new \Exception("Unknown action: {$changeRequest->action}.");
    }
}
