<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FeeSchedule;
use App\Models\GraduationFee;
use App\Models\StudentGraduationFee;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;

class DirectressController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $feeSchedules = FeeSchedule::count();
        $graduationFees = GraduationFee::count();

        // Demographics (light for dashboard)
        $totalStudents = Enrollment::where('status', 'Active')->count();
        $byGrade = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->grade_level ?? 'Unknown')->map->count()->sortKeys();
        $bySection = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->section_name ?? 'Unknown')->map->count();
        $byYear = Enrollment::where('status','Active')->get()->groupBy('school_year')->map->count()->sortKeysDesc();
        $totalFeesAssessed = \App\Models\FeeSchedule::sum(\DB::raw('tuition_fee + misc_fee'));

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.dashboard-results', compact(
                    'feeSchedules', 'graduationFees', 'totalStudents', 'byGrade', 'bySection', 'byYear', 'totalFeesAssessed'
                ))->render(),
            ]);
        }

        return view('portal.directress.dashboard', compact(
            'feeSchedules', 'graduationFees', 'totalStudents', 'byGrade', 'bySection', 'byYear', 'totalFeesAssessed'
        ));
    }

    public function demographics(Request $request)
    {
        $byGrade = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->grade_level ?? 'Unknown')->map->count()->sortKeys();
        $bySection = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->section_name ?? 'Unknown')->map->count()->sortKeys();
        $byYear = Enrollment::where('status','Active')->get()->groupBy('school_year')->map->count()->sortKeysDesc();
        $byGender = \App\Models\Student::whereHas('enrollments', fn($q)=>$q->where('status','Active'))->get()->groupBy(fn($s)=>$s->gender ?? 'Unknown')->map->count();
        $byStrand = Enrollment::where('status','Active')->whereNotNull('strand')->get()->groupBy('strand')->map->count();

        $total = $byGrade->sum();

        // Payment collection data
        $paymentsByMonth = \App\Models\Payment::whereYear('payment_date', date('Y'))
            ->get()
            ->groupBy(fn($p) => $p->payment_date->format('M'))
            ->map(fn($group) => $group->sum('amount_paid'))
            ->sortKeys();
        $paymentsByYear = \App\Models\Payment::get()
            ->groupBy(fn($p) => $p->payment_date->format('Y'))
            ->map(fn($group) => $group->sum('amount_paid'))
            ->sortKeys();
        $totalCollected = \App\Models\Payment::sum('amount_paid');
        $totalTransactions = \App\Models\Payment::count();

        return view('portal.directress.demographics', compact(
            'byGrade','bySection','byYear','byGender','byStrand','total',
            'paymentsByMonth','paymentsByYear','totalCollected','totalTransactions'
        ));
    }

    // ─── Fee Schedule (moved from Admin) ────────────────────────
    public function fees(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = FeeSchedule::query();

        if (request('school_year')) {
            $query->where('school_year', request('school_year'));
        }

        $fees = $query->orderBy('grade_level')->orderBy('term')->get()->groupBy('grade_level');
        $terms = ['1st Term', '2nd Term', '3rd Term'];
        $schoolYears = all_school_years();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.fees-results', compact('fees', 'terms'))->render(),
            ]);
        }

        return view('portal.directress.fees.index', compact('fees', 'terms', 'schoolYears'));
    }

    public function feesCreate()
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5',
            'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $terms = ['1st Term', '2nd Term', '3rd Term'];
        return view('portal.directress.fees.create', compact('gradeLevels', 'terms'));
    }

    public function feesStore(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'term' => 'required|in:1st Term,2nd Term,3rd Term',
            'tuition_fee' => 'required|numeric|min:0',
            'misc_fee' => 'required|numeric|min:0',
            'school_year' => 'required|string|max:20',
        ]);

        $exists = FeeSchedule::where('grade_level', $data['grade_level'])
            ->where('term', $data['term'])
            ->where('school_year', $data['school_year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A fee schedule already exists for this grade level, term, and school year.');
        }

        $feeSchedule = FeeSchedule::create($data);

        log_activity($feeSchedule, 'Fee Schedule Created', auth()->user()->name . ' created fee schedule for ' . $data['grade_level'] . ' (SY: ' . $data['school_year'] . ').');

        return redirect()->route('directress.fees')
            ->with('success', 'Fee schedule created for ' . $data['grade_level'] . ' - ' . $data['term'] . '.');
    }

    public function feesEdit(FeeSchedule $fee)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5',
            'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $terms = ['1st Term', '2nd Term', '3rd Term'];
        return view('portal.directress.fees.edit', compact('fee', 'gradeLevels', 'terms'));
    }

    public function feesUpdate(Request $request, FeeSchedule $fee)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'term' => 'required|in:1st Term,2nd Term,3rd Term',
            'tuition_fee' => 'required|numeric|min:0',
            'misc_fee' => 'required|numeric|min:0',
            'school_year' => 'required|string|max:20',
        ]);

        $fee->update($data);

        log_activity($fee, 'Fee Schedule Updated', auth()->user()->name . ' updated fee schedule for ' . $fee->grade_level . '.');

        return redirect()->route('directress.fees')
            ->with('success', 'Fee schedule updated.');
    }

    public function feesDestroy(FeeSchedule $fee)
    {
        $gradeLevel = $fee->grade_level;
        $fee->delete();
        log_activity('App\\Models\\FeeSchedule', 'Fee Schedule Deleted', auth()->user()->name . ' deleted fee schedule for ' . $gradeLevel . '.');
        return back()->with('success', 'Fee schedule deleted.');
    }

    // ─── Graduation Fees ────────────────────────────────────────
    public function graduationFees()
    {
        $fees = GraduationFee::orderBy('grade_level')->orderBy('school_year')->get()->groupBy('grade_level');
        return view('portal.directress.graduation-fees.index', compact('fees'));
    }

    public function graduationFeesCreate()
    {
        $gradeLevels = ['Grade 6', 'Grade 10', 'Grade 12'];
        return view('portal.directress.graduation-fees.create', compact('gradeLevels'));
    }

    public function graduationFeesStore(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
            'graduation_fee' => 'required|numeric|min:0',
            'other_fees' => 'required|numeric|min:0',
        ]);

        $exists = GraduationFee::where('grade_level', $data['grade_level'])
            ->where('school_year', $data['school_year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A graduation fee already exists for this grade level and school year.');
        }

        $graduationFee = GraduationFee::create($data);

        log_activity($graduationFee, 'Graduation Fee Created', auth()->user()->name . ' created graduation fee: ' . $data['grade_level'] . ' (₱' . number_format($data['graduation_fee'], 2) . ').');

        return redirect()->route('directress.graduation-fees')
            ->with('success', 'Graduation fee created for ' . $data['grade_level'] . '.');
    }

    public function graduationFeesEdit(GraduationFee $graduationFee)
    {
        $gradeLevels = ['Grade 6', 'Grade 10', 'Grade 12'];
        return view('portal.directress.graduation-fees.edit', compact('graduationFee', 'gradeLevels'));
    }

    public function graduationFeesUpdate(Request $request, GraduationFee $graduationFee)
    {
        $data = $request->validate([
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
            'graduation_fee' => 'required|numeric|min:0',
            'other_fees' => 'required|numeric|min:0',
        ]);

        $graduationFee->update($data);

        log_activity($graduationFee, 'Graduation Fee Updated', auth()->user()->name . ' updated graduation fee: ' . $graduationFee->grade_level . '.');

        return redirect()->route('directress.graduation-fees')
            ->with('success', 'Graduation fee updated.');
    }

    public function graduationFeesDestroy(GraduationFee $graduationFee)
    {
        $name = $graduationFee->grade_level;
        $graduationFee->delete();
        log_activity('App\\Models\\GraduationFee', 'Graduation Fee Deleted', auth()->user()->name . ' deleted graduation fee: ' . $name . '.');
        return back()->with('success', 'Graduation fee deleted.');
    }

    public function graduationFeesAssign(GraduationFee $graduationFee)
    {
        $query = Enrollment::with('student', 'section')
            ->where('status', 'Active')
            ->whereHas('section', function ($q) use ($graduationFee) {
                if ($graduationFee->grade_level) {
                    $q->where('grade_level', $graduationFee->grade_level);
                }
            });

        if (request('search')) {
            $search = request('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->get();

        $alreadyAssigned = StudentGraduationFee::where('graduation_fee_id', $graduationFee->id)
            ->pluck('student_id')
            ->toArray();

        return view('portal.directress.graduation-fees.assign', compact('graduationFee', 'enrollments', 'alreadyAssigned'));
    }

    public function graduationFeesAssignStore(Request $request, GraduationFee $graduationFee)
    {
        $request->validate([
            'student_ids' => 'required|array',
        ]);

        $totalPerStudent = $graduationFee->graduation_fee + $graduationFee->other_fees;

        foreach ($request->student_ids as $enrollmentId) {
            $enrollment = Enrollment::with('student')->find($enrollmentId);
            if (!$enrollment) continue;

            $exists = StudentGraduationFee::where('student_id', $enrollment->student_id)
                ->where('graduation_fee_id', $graduationFee->id)
                ->exists();

            if (!$exists) {
                StudentGraduationFee::create([
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollmentId,
                    'graduation_fee_id' => $graduationFee->id,
                    'amount' => $totalPerStudent,
                ]);
            }
        }

        log_activity('App\\Models\\GraduationFee', 'Graduation Fee Assigned', auth()->user()->name . ' assigned graduation fee to ' . count($request->student_ids) . ' student(s).');

        return back()->with('success', 'Graduation fees assigned to selected students.');
    }

    public function graduationFeesAssigned(GraduationFee $graduationFee)
    {
        $assignments = StudentGraduationFee::with('student', 'enrollment.section')
            ->where('graduation_fee_id', $graduationFee->id)
            ->get();

        return view('portal.directress.graduation-fees.assigned', compact('graduationFee', 'assignments'));
    }

    public function graduationFeesTogglePaid(StudentGraduationFee $assignment)
    {
        $assignment->paid = !$assignment->paid;
        $assignment->save();

        log_activity($assignment, 'Graduation Fee Payment Toggled', auth()->user()->name . ' toggled paid status for student #' . $assignment->student_id . ' on "' . $assignment->graduationFee->name . '".');

        return back()->with('success', 'Payment status updated.');
    }

    // ─── School Year ──────────────────────────────────────────────
    public function schoolYears()
    {
        $years = collect(all_school_years())->map(fn($y) => [
            'year' => $y,
            'count' => FeeSchedule::where('school_year', $y)->count(),
            'locked' => in_array($y, $this->getLockedSchoolYears()),
        ])->sortByDesc('year');
        return view('portal.directress.school-years', compact('years'));
    }

    public function storeSchoolYear(Request $request)
    {
        $data = $request->validate(['school_year' => 'required|regex:/^\d{4}-\d{4}$/']);
        if (FeeSchedule::where('school_year', $data['school_year'])->exists() || \App\Models\Setting::getValue('active_school_year') === $data['school_year']) {
            return back()->with('error', 'School year '.$data['school_year'].' already exists.');
        }
        FeeSchedule::create(['grade_level' => 'Grade 1', 'term' => '1st Term', 'school_year' => $data['school_year'], 'tuition_fee' => 0, 'misc_fee' => 0]);
        \App\Models\Setting::setValue('active_school_year', $data['school_year']);
        \Illuminate\Support\Facades\Cache::forget('active_school_year');
        \Illuminate\Support\Facades\Cache::forget('all_school_years');

        log_activity('App\\Models\\Setting', 'School Year Created', auth()->user()->name . ' created and activated school year: ' . $data['school_year'] . '.');

        return back()->with('success', 'School year '.$data['school_year'].' added and set as active.');
    }

    public function toggleLockSchoolYear(Request $request)
    {
        $data = $request->validate(['school_year' => 'required|string']);
        $locked = $this->getLockedSchoolYears();

        if (in_array($data['school_year'], $locked)) {
            $locked = array_values(array_diff($locked, [$data['school_year']]));
            $msg = 'School year '.$data['school_year'].' unlocked.';
        } else {
            $locked[] = $data['school_year'];
            $msg = 'School year '.$data['school_year'].' locked. Settings for this year can no longer be edited.';
        }

        \App\Models\Setting::setValue('locked_school_years', implode(',', $locked));
        \Illuminate\Support\Facades\Cache::forget('setting_locked_school_years');

        $isLocked = in_array($data['school_year'], $locked);
        log_activity('App\\Models\\SchoolYear', 'School Year Lock Toggled', auth()->user()->name . ' ' . ($isLocked ? 'locked' : 'unlocked') . ' school year: ' . $data['school_year'] . '.');

        return back()->with('success', $msg);
    }

    private function getLockedSchoolYears(): array
    {
        $val = \App\Models\Setting::getValue('locked_school_years', '');
        return $val ? array_map('trim', explode(',', $val)) : [];
    }

    public function libraryReports(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        
        $totalBooks = \App\Models\Book::where('is_active', true)->count();
        $totalCopies = \App\Models\Book::where('is_active', true)->sum('quantity');
        $availableCopies = \App\Models\Book::where('is_active', true)->sum('available_quantity');
        $borrowedCount = \App\Models\LibraryTransaction::where('status', 'Borrowed')->count();
        $overdueCount = \App\Models\LibraryTransaction::where('status', 'Borrowed')
            ->where('return_date', '<', now())
            ->where('return_date', '>', '1970-01-02')
            ->whereNotNull('return_date')
            ->count();
        $totalTransactions = \App\Models\LibraryTransaction::count();
        $totalFines = \App\Models\LibraryTransaction::where('fees_assessed', true)->sum('total_fees');
        
        $recentTransactions = \App\Models\LibraryTransaction::with('student', 'book')
            ->latest('borrow_date')
            ->take(10)
            ->get();
        
        $popularBooks = \App\Models\Book::withCount('borrowings')
            ->where('is_active', true)
            ->orderByDesc('borrowings_count')
            ->take(5)
            ->get();
        
        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.library-reports-results', compact(
                    'totalBooks', 'totalCopies', 'availableCopies', 'borrowedCount', 'overdueCount', 'totalTransactions', 'totalFines', 'recentTransactions', 'popularBooks'
                ))->render(),
            ]);
        }
        
        return view('portal.directress.library-reports', compact(
            'totalBooks', 'totalCopies', 'availableCopies', 'borrowedCount', 'overdueCount', 'totalTransactions', 'totalFines', 'recentTransactions', 'popularBooks'
        ));
    }

    public function cashierReports(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        
        $payments = \App\Models\Payment::with('ledger.student', 'cashier')
            ->whereBetween('payment_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderBy('payment_date')
            ->get();
        
        $totalCollected = $payments->sum('amount_paid');
        $receiptCount = $payments->count();
        $avgPayment = $receiptCount > 0 ? $totalCollected / $receiptCount : 0;
        
        $totalAssessed = \App\Models\StudentLedger::sum('total_assessed');
        $totalPaid = \App\Models\StudentLedger::sum('total_paid');
        $totalBalance = \App\Models\StudentLedger::sum('balance');
        
        $monthlyData = $payments->groupBy(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y-m'))
            ->map(fn($group) => ['count' => $group->count(), 'total' => $group->sum('amount_paid')])
            ->sortKeysDesc();
        
        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.cashier-reports-results', compact(
                    'totalCollected', 'receiptCount', 'avgPayment', 'totalAssessed', 'totalPaid', 'totalBalance', 'monthlyData', 'dateFrom', 'dateTo', 'payments'
                ))->render(),
            ]);
        }
        
        return view('portal.directress.cashier-reports', compact(
            'totalCollected', 'receiptCount', 'avgPayment', 'totalAssessed', 'totalPaid', 'totalBalance', 'monthlyData', 'dateFrom', 'dateTo', 'payments'
        ));
    }

    public function exportLibraryReports(Request $request)
    {
        $transactions = \App\Models\LibraryTransaction::with('student', 'book')->latest('borrow_date')->get();
        $filename = 'library_report_' . now()->format('Ymd_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student', 'Book', 'Status', 'Borrow Date', 'Return Date', 'Fees']);
            foreach ($transactions as $t) {
                fputcsv($file, [
                    ($t->student->first_name ?? '') . ' ' . ($t->student->last_name ?? ''),
                    $t->book->title ?? $t->book_title,
                    $t->status,
                    $t->borrow_date,
                    $t->return_date,
                    $t->total_fees ?? 0,
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportCashierReports(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        $payments = \App\Models\Payment::with('ledger.student')->whereBetween('payment_date', [$dateFrom, $dateTo . ' 23:59:59'])->orderBy('payment_date')->get();
        $filename = 'cashier_report_' . $dateFrom . '_to_' . $dateTo . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function() use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Student', 'Amount', 'Receipt', 'AR Number']);
            foreach ($payments as $p) {
                fputcsv($file, [$p->payment_date, ($p->ledger->student->first_name ?? '') . ' ' . ($p->ledger->student->last_name ?? ''), $p->amount_paid, $p->receipt_number, $p->ar_number ?? '']);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
