<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Setting;
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
        $query = Withdrawal::with('student.user', 'enrollment.section', 'processor');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                })->orWhereHas('enrollment', function ($sq) use ($search) {
                    $sq->whereHas('section', function ($s) use ($search) {
                        $s->where('section_name', 'like', "%{$search}%");
                    });
                });
            });
        }

        if (request('status') && request('status') !== 'All') {
            $query->where('status', request('status'));
        }

        $withdrawals = $query->latest()->paginate(20)->withQueryString();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.withdrawals-results', compact('withdrawals'))->render(),
            ]);
        }

        return view('portal.registrar.withdrawals-index', compact('withdrawals'));
    }

    public function approve(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'Pending') {
            return back()->with('error', 'This withdrawal request has already been processed.');
        }

        $student = $withdrawal->student;
        $enrollment = $withdrawal->enrollment;

        $currentTerm = Setting::getValue('current_term', '1st Term');

        $hasGrades = $enrollment->grades()->exists();

        if ($currentTerm === '1st Term' && !$hasGrades) {
            $refundPercentage = 1.0;
        } elseif ($currentTerm === '1st Term') {
            $refundPercentage = 0.5;
        } elseif ($currentTerm === '2nd Term') {
            $refundPercentage = 0.25;
        } else {
            $refundPercentage = 0;
        }

        $ledger = $student->ledger;
        $totalPaid = $ledger ? $ledger->total_paid : 0;
        $refundAmount = round($totalPaid * $refundPercentage, 2);

        DB::transaction(function () use ($withdrawal, $student, $enrollment, $refundAmount, $ledger, $refundPercentage) {
            $withdrawal->status = 'Approved';
            $withdrawal->processed_by = auth()->id();
            $withdrawal->refund_amount = $refundAmount;
            $withdrawal->refund_processed_at = now();
            $withdrawal->save();

            $enrollment->update(['status' => 'Withdrawn']);

            if ($refundAmount > 0 && $ledger) {
                $ledger->total_paid = max(0, $ledger->total_paid - $refundAmount);
                $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
                $ledger->save();

                $receiptNumber = 'REF-' . now()->format('Ymd') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT);

                $ledger->payments()->create([
                    'cashier_id' => auth()->id(),
                    'amount_paid' => -$refundAmount,
                    'receipt_number' => $receiptNumber,
                    'payment_date' => now(),
                ]);
            }

            $refundLabel = $refundPercentage > 0
                ? " — Refund: ₱" . number_format($refundAmount, 2) . " (" . ($refundPercentage * 100) . "%)"
                : " — No refund (0%)";

            log_activity($student, 'Withdrawal Approved', "Withdrawal approved for {$student->first_name} {$student->last_name}{$refundLabel}");
        });

        $msg = 'Withdrawal approved for ' . $student->first_name . ' ' . $student->last_name . '.';
        if ($refundAmount > 0) {
            $msg .= " Refund of ₱" . number_format($refundAmount, 2) . " processed.";
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
