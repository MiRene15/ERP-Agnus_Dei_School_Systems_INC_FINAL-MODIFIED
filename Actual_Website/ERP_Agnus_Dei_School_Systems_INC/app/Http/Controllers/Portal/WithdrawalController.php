<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

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
            'reason' => 'required|string|max:1000',
        ]);

        Withdrawal::create([
            'enrollment_id' => $activeEnrollment->id,
            'student_id' => $student->id,
            'reason' => $data['reason'],
            'status' => 'Pending',
        ]);

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

        $withdrawal->status = 'Approved';
        $withdrawal->processed_by = auth()->id();
        $withdrawal->save();

        $withdrawal->enrollment->update(['status' => 'Withdrawn']);

        return back()->with('success', 'Withdrawal approved for ' . $withdrawal->student->first_name . ' ' . $withdrawal->student->last_name . '.');
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

        return back()->with('success', 'Withdrawal request rejected.');
    }
}
