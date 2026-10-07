<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    public function create()
    {
        $student = auth()->user()->student;
        $activeEnrollment = $student->enrollments()->where('status', 'Active')->latest()->first();

        if (!$activeEnrollment) {
            return redirect()->route('student.dashboard')->with('error', 'You have no active enrollment to withdraw from.');
        }

        $existingRequest = Withdrawal::where('enrollment_id', $activeEnrollment->id)
            ->where('status', 'Pending')
            ->latest()
            ->first();

        return view('portal.student.withdrawal-create', compact('activeEnrollment', 'existingRequest'));
    }

    public function store(Request $request)
    {
        $student = auth()->user()->student;
        $activeEnrollment = $student->enrollments()->where('status', 'Active')->latest()->firstOrFail();

        $existing = Withdrawal::where('enrollment_id', $activeEnrollment->id)
            ->where('status', 'Pending')
            ->exists();

        if ($existing) {
            return back()->with('error', 'You already have a pending withdrawal request for this enrollment.');
        }

        $data = $request->validate([
            'category' => 'required|string|in:Transfer to Another School,Change of Mind,Financial Issues,Health Reasons,Relocation,Family Reasons,Other',
            'details' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:1000',
        ]);

        $reason = $data['category'];
        if (!empty($data['details'])) {
            $reason .= ' — ' . $data['details'];
        } elseif (!empty($data['reason'])) {
            $reason = $data['reason'];
        }

        $withdrawal = Withdrawal::create([
            'enrollment_id' => $activeEnrollment->id,
            'student_id' => $student->id,
            'reason' => $reason,
            'status' => 'Pending',
        ]);

        log_activity($withdrawal, 'Withdrawal Requested', auth()->user()->name . ' submitted withdrawal request for student #' . $student->id . '. Reason: ' . ($data['reason'] ?? 'N/A'));

        return redirect()->route('student.dashboard')->with('success', 'Withdrawal request submitted. The registrar will review your request.');
    }

    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Withdrawal::with('student.user', 'student.ledger', 'enrollment.section', 'processor');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($sq) use ($search) {
                    $sq->where('name', 'ilike', "%{$search}%");
                })->orWhereHas('enrollment', function ($sq) use ($search) {
                    $sq->whereHas('section', function ($s) use ($search) {
                        $s->where('section_name', 'ilike', "%{$search}%");
                    });
                });
            });
        }

        if (request('status') && request('status') !== 'All') {
            $query->where('status', request('status'));
        }

        if (request('school_year') && request('school_year') !== 'All') {
            $query->whereHas('enrollment', function ($sq) {
                $sq->where('school_year', request('school_year'));
            });
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        $schoolYears = Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year');

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.withdrawals-results', compact('withdrawals'))->render(),
            ]);
        }

        return view('portal.registrar.withdrawals-index', compact('withdrawals', 'schoolYears'));
    }

    /**
     * Registrar approves the withdrawal (academic decision). Computes the refund
     * due but moves NO money — the Cashier releases the payout separately.
     */
    public function approve(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'Pending') {
            return back()->with('error', 'This withdrawal request has already been processed.');
        }

        $student = $withdrawal->student;
        $enrollment = $withdrawal->enrollment;

        // Refund policy: flat 25% of total paid, regardless of term.
        $refundPercentage = 0.25;

        $ledger = $student->ledger;
        $totalPaid = $ledger ? $ledger->total_paid : 0;
        $refundAmount = round($totalPaid * $refundPercentage, 2);

        DB::transaction(function () use ($withdrawal, $student, $enrollment, $refundAmount) {
            $withdrawal->status = 'Approved';
            $withdrawal->processed_by = auth()->id();
            $withdrawal->refund_amount = $refundAmount;
            // refund_processed_at stays null until the Cashier releases the payout.
            $withdrawal->save();

            $enrollment->update(['status' => 'Withdrawn']);

            $refundLabel = $refundAmount > 0
                ? " — Refund due: ₱" . number_format($refundAmount, 2) . " (flat 25% of total paid, awaiting Cashier release)"
                : " — No refund (nothing paid)";

            log_activity($student, 'Withdrawal Approved', auth()->user()->name . " (Registrar) approved withdrawal for {$student->first_name} {$student->last_name}{$refundLabel}");
        });

        $msg = 'Withdrawal approved for ' . $student->first_name . ' ' . $student->last_name . '.';
        if ($refundAmount > 0) {
            $msg .= " Refund of ₱" . number_format($refundAmount, 2) . " computed — the Cashier releases the payout.";
        }

        return back()->with('success', $msg);
    }

    public function reject(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'Pending') {
            return back()->with('error', 'This withdrawal request has already been processed.');
        }

        $data = $request->validate(['remarks' => 'nullable|string|max:500']);

        $withdrawal->status = 'Rejected';
        $withdrawal->processed_by = auth()->id();
        $withdrawal->remarks = $data['remarks'] ?? null;
        $withdrawal->save();

        log_activity($withdrawal, 'Withdrawal Rejected', auth()->user()->name . ' rejected withdrawal request for student #' . $withdrawal->student_id . '. Reason: ' . ($data['remarks'] ?? 'N/A'));

        return back()->with('success', 'Withdrawal request rejected.');
    }
}
