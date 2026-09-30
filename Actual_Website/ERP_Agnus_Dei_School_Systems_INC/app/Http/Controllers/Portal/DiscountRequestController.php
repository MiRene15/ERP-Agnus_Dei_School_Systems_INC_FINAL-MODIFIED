<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DiscountRequest;
use App\Models\StudentLedger;
use Illuminate\Http\Request;

/**
 * Two-step discount approvals (role reform Phase 2a).
 * Cashier or Registrar requests with proof -> Directress approves/rejects -> Cashier applies.
 * Nobody grants and collects alone anymore.
 */
class DiscountRequestController extends Controller
{
    // ─── Request (Cashier / Registrar) ───────────────────────────
    public function index()
    {
        $requests = DiscountRequest::with(['ledger.student.user', 'ledger.student.enrollments.section', 'requester', 'reviewer'])
            ->latest()
            ->paginate(20);

        $ledgers = StudentLedger::with('student.user')
            ->whereHas('student.enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->orderBy('id')
            ->get();

        return view('portal.discount-requests.index', [
            'requests' => $requests,
            'ledgers' => $ledgers,
            'discountTypes' => DiscountRequest::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_ledger_id' => 'required|exists:student_ledgers,id',
            'discount_type' => 'required|in:honor,sibling,esc,other',
            'discount_amount' => 'required|numeric|min:0',
            'proof_details' => 'required|string|min:10|max:1000',
        ]);

        $ledger = StudentLedger::with('student')->findOrFail($data['student_ledger_id']);
        $discountAmount = min((float) $data['discount_amount'], (float) $ledger->total_assessed);

        $open = DiscountRequest::where('student_ledger_id', $ledger->id)
            ->whereIn('status', [DiscountRequest::STATUS_PENDING, DiscountRequest::STATUS_APPROVED])
            ->exists();
        if ($open) {
            return back()->with('error', 'This student already has an open discount request.');
        }

        $discountRequest = DiscountRequest::create([
            'student_ledger_id' => $ledger->id,
            'discount_type' => $data['discount_type'],
            'discount_amount' => $discountAmount,
            'proof_details' => $data['proof_details'],
            'requested_by' => auth()->id(),
            'status' => DiscountRequest::STATUS_PENDING,
        ]);

        $name = $ledger->student ? $ledger->student->first_name . ' ' . $ledger->student->last_name : 'Student #' . $ledger->student_id;
        log_activity($discountRequest, 'Discount Requested', auth()->user()->name . " requested {$data['discount_type']} discount (₱" . number_format($discountAmount, 2) . ") for {$name}. Proof: {$data['proof_details']}");

        return back()->with('success', 'Discount request sent to the Directress for approval.');
    }

    // ─── Review (Directress) ─────────────────────────────────────
    public function reviewIndex()
    {
        $pending = DiscountRequest::with(['ledger.student.user', 'ledger.student.enrollments.section', 'requester'])
            ->where('status', DiscountRequest::STATUS_PENDING)
            ->latest()
            ->get();

        $history = DiscountRequest::with(['ledger.student.user', 'requester', 'reviewer'])
            ->whereIn('status', [DiscountRequest::STATUS_APPROVED, DiscountRequest::STATUS_REJECTED, DiscountRequest::STATUS_APPLIED])
            ->latest()
            ->take(50)
            ->get();

        return view('portal.directress.discount-requests.index', compact('pending', 'history'));
    }

    public function approve(DiscountRequest $discountRequest)
    {
        if ($discountRequest->status !== DiscountRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $discountRequest->update([
            'status' => DiscountRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ledger = $discountRequest->ledger;
        $name = $ledger->student ? $ledger->student->first_name . ' ' . $ledger->student->last_name : 'Student #' . $ledger->student_id;
        log_activity($discountRequest, 'Discount Approved', auth()->user()->name . " (Directress) approved {$discountRequest->discount_type} discount (₱" . number_format($discountRequest->discount_amount, 2) . ") for {$name}. Awaiting Cashier application.");

        return back()->with('success', 'Discount approved — the Cashier can now apply it.');
    }

    public function reject(DiscountRequest $discountRequest)
    {
        if ($discountRequest->status !== DiscountRequest::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $discountRequest->update([
            'status' => DiscountRequest::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $ledger = $discountRequest->ledger;
        $name = $ledger->student ? $ledger->student->first_name . ' ' . $ledger->student->last_name : 'Student #' . $ledger->student_id;
        log_activity($discountRequest, 'Discount Rejected', auth()->user()->name . " (Directress) rejected the {$discountRequest->discount_type} discount request for {$name}.");

        return back()->with('success', 'Discount request rejected.');
    }
}
