<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\FeeSchedule;
use App\Models\GraduationFee;
use App\Models\StudentGraduationFee;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $totalStudents = Enrollment::where('status', 'Active')->count();
        $byGrade = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->grade_level ?? 'Unknown')->map->count()->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);
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
        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $byGrade = Enrollment::with('section')->where('status','Active')->get()->groupBy(fn($e)=>$e->section?->grade_level ?? 'Unknown')->map->count()->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);
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

        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($data['school_year'])) {
            return back()->withInput()->with('error', 'School year ' . $data['school_year'] . ' is locked — its settings can no longer be edited.');
        }

        $feeSchedule = FeeSchedule::create($data);

        \Illuminate\Support\Facades\Cache::forget("fee_totals:{$data['school_year']}:{$data['grade_level']}");

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

        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($data['school_year']) || school_year_locked($fee->school_year)) {
            return back()->with('error', 'School year ' . $fee->school_year . ' is locked — its settings can no longer be edited.');
        }

        $oldSchoolYear = $fee->school_year;
        $oldGradeLevel = $fee->grade_level;
        $fee->update($data);

        \Illuminate\Support\Facades\Cache::forget("fee_totals:{$oldSchoolYear}:{$oldGradeLevel}");
        \Illuminate\Support\Facades\Cache::forget("fee_totals:{$data['school_year']}:{$data['grade_level']}");

        log_activity($fee, 'Fee Schedule Updated', auth()->user()->name . ' updated fee schedule for ' . $fee->grade_level . '.');

        return redirect()->route('directress.fees')
            ->with('success', 'Fee schedule updated.');
    }

    public function feesDestroy(FeeSchedule $fee)
    {
        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($fee->school_year)) {
            return back()->with('error', 'School year ' . $fee->school_year . ' is locked — its settings can no longer be edited.');
        }
        $gradeLevel = $fee->grade_level;
        $feeSchoolYear = $fee->school_year;
        $fee->delete();
        \Illuminate\Support\Facades\Cache::forget("fee_totals:{$feeSchoolYear}:{$gradeLevel}");
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

        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($data['school_year'])) {
            return back()->withInput()->with('error', 'School year ' . $data['school_year'] . ' is locked — its settings can no longer be edited.');
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

        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($data['school_year']) || school_year_locked($graduationFee->school_year)) {
            return back()->with('error', 'School year ' . $graduationFee->school_year . ' is locked — its settings can no longer be edited.');
        }

        $graduationFee->update($data);

        log_activity($graduationFee, 'Graduation Fee Updated', auth()->user()->name . ' updated graduation fee: ' . $graduationFee->grade_level . '.');

        return redirect()->route('directress.graduation-fees')
            ->with('success', 'Graduation fee updated.');
    }

    public function graduationFeesDestroy(GraduationFee $graduationFee)
    {
        // Locked school years are frozen — their settings can no longer be edited.
        if (school_year_locked($graduationFee->school_year)) {
            return back()->with('error', 'School year ' . $graduationFee->school_year . ' is locked — its settings can no longer be edited.');
        }
        $name = $graduationFee->grade_level;
        $graduationFee->delete();
        log_activity('App\\Models\\GraduationFee', 'Graduation Fee Deleted', auth()->user()->name . ' deleted graduation fee: ' . $name . '.');
        return back()->with('success', 'Graduation fee deleted.');
    }

    public function graduationFeesAssigned(GraduationFee $graduationFee)
    {
        $assignments = StudentGraduationFee::with('student', 'enrollment.section')
            ->where('graduation_fee_id', $graduationFee->id)
            ->get();

        return view('portal.directress.graduation-fees.assigned', compact('graduationFee', 'assignments'));
    }

    // ─── Announcements awareness ────────────────────────────────
    // Whole-school announcements come from the Principal; the Directress
    // acknowledges them here so nothing goes out unnoticed.
    public function announcements()
    {
        $announcements = Announcement::with('admin')->latest()->paginate(20);
        $unseenCount = Announcement::whereNull('directress_seen_at')->count();

        return view('portal.directress.announcements.index', compact('announcements', 'unseenCount'));
    }

    public function acknowledgeAnnouncement(Announcement $announcement)
    {
        $announcement->directress_seen_at = now();
        $announcement->save();

        log_activity($announcement, 'Announcement Acknowledged', auth()->user()->name . ' (Directress) acknowledged: "' . $announcement->title . '".');

        return back()->with('success', 'Announcement acknowledged.');
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
        
        // Single library path shared with the librarian single (spec: librarian-library-reports.md).
        $data = app(\App\Services\LibraryReportService::class)->libraryData();
        $totalBooks = $data['totalBooks'];
        $totalCopies = $data['totalCopies'];
        $availableCopies = $data['availableCopies'];
        $borrowedCount = $data['borrowedCount'];
        $overdueCount = $data['overdueCount'];
        $totalTransactions = $data['totalTransactions'];
        $totalFines = $data['totalFines'];
        $recentTransactions = $data['recentTransactions'];
        $popularBooks = $data['popularBooks'];
        
        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.library-reports-results', compact(
                    'totalBooks', 'totalCopies', 'availableCopies', 'borrowedCount', 'overdueCount', 'totalTransactions', 'totalFines', 'recentTransactions', 'popularBooks'
                ))->render(),
            ]);
        }

        return view('portal.directress.reports', ['activeTab' => 'library']);
    }

    // ─── Reports Hub (Collections / Receivables / Clinic / Library / Students) ──
    public function reports(Request $request)
    {
        return view('portal.directress.reports', ['activeTab' => $request->query('tab', 'collections')]);
    }

    public function collectionsReport(Request $request)
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

        $byPlan = $payments->groupBy(fn($p) => $p->ledger->payment_plan ?? 'Unknown')
            ->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('amount_paid')]);

        $dailyBreakdown = $payments->groupBy(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d'))
            ->map(fn($group) => [
                'date' => $group->first()->payment_date,
                'count' => $group->count(),
                'total' => $group->sum('amount_paid'),
            ])
            ->sortBy('date')
            ->values()
            ->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.collections-report-results', compact(
                    'payments', 'totalCollected', 'receiptCount', 'byPlan', 'dailyBreakdown', 'dateFrom', 'dateTo'
                ))->render(),
            ]);
        }

        return view('portal.directress.reports', ['activeTab' => 'collections']);
    }

    public function receivablesReport(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        // Same source logic as the cashier's receivables (parity by construction):
        // identical ledger set, identical date filtering, identical breakdowns.
        $receivables = \App\Models\StudentLedger::with(['student.enrollments.section', 'payments' => fn($q) => $q->orderByDesc('payment_date')->orderByDesc('id')])->where('balance', '>', 0)->orderByDesc('balance')->get();
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom || $dateTo) {
            $receivables = $receivables->filter(fn($l) => $l->payments->isEmpty() || (($d = $l->payments->first()?->payment_date?->format('Y-m-d')) && (!$dateFrom || $d >= $dateFrom) && (!$dateTo || $d <= $dateTo)))->values();
        }
        $dailyBreakdown = $receivables->filter(fn($l) => $l->payments->isNotEmpty())->groupBy(fn($l) => $l->payments->first()->payment_date->format('Y-m-d'))->map(fn($g, $date) => ['date' => $date, 'count' => $g->count(), 'total' => $g->sum('balance')])->sortKeys()->values();
        $unpaidDues = $receivables->filter(fn($l) => $l->payments->isEmpty());
        $unpaid = ['count' => $unpaidDues->count(), 'total' => $unpaidDues->sum('balance')];
        $byPlan = $receivables->groupBy(fn($l) => $l->payment_plan ?? 'N/A')->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('balance')]);
        $totalReceivable = $receivables->sum('balance');
        $countReceivable = $receivables->count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.receivables-results', compact('receivables', 'byPlan', 'dailyBreakdown', 'unpaid', 'totalReceivable', 'countReceivable'))->render(),
            ]);
        }

        return view('portal.directress.reports', ['activeTab' => 'receivables']);
    }

    public function clinicReport(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        // Single totals path shared with the nurse single (spec: nurse-clinic-reports.md).
        $data = app(\App\Services\ClinicReportService::class)->rangeData($dateFrom, $dateTo);
        $logs = $data['logs'];
        $totalVisits = $data['totalVisits'];
        $uniquePatients = $data['uniquePatients'];
        $referralsOut = $data['referralsOut'];
        $activeDays = $data['activeDays'];
        $byGrade = $data['byGrade'];
        $topSymptoms = $data['topSymptoms'];
        // Privacy: Directress sees totals and trends only — no per-student
        // diagnosis details, no recent-visit rows. Full details stay with the clinic.
        $openCases = $data['openCases'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.clinic-reports-results', compact(
                    'logs', 'totalVisits', 'uniquePatients', 'referralsOut', 'activeDays', 'byGrade', 'topSymptoms', 'openCases', 'dateFrom', 'dateTo'
                ))->render(),
            ]);
        }

        return view('portal.directress.reports', ['activeTab' => 'clinic']);
    }

    public function studentStatsReport(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        // Single students path shared with the registrar single (spec: registrar-student-stats-reports.md).
        $data = app(\App\Services\StudentStatsService::class)->studentStatsData();
        $byGrade = $data['byGrade'];
        $bySection = $data['bySection'];
        $byYear = $data['byYear'];
        $byGender = $data['byGender'];
        $byStrand = $data['byStrand'];
        $total = $data['total'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.student-stats-results', compact(
                    'byGrade', 'bySection', 'byYear', 'byGender', 'byStrand', 'total'
                ))->render(),
            ]);
        }

        return view('portal.directress.reports', ['activeTab' => 'students']);
    }

    public function exportLibraryReports(Request $request)
    {
        $transactions = \App\Models\LibraryTransaction::with('student', 'book')->latest('borrow_date')->get();
        $filename = 'library_report_' . now()->format('Ymd_His') . '.csv';
        log_activity(\App\Models\LibraryTransaction::class, 'Exported', auth()->user()->name . ' exported the library report CSV (' . $transactions->count() . ' transactions).');
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

        $payments = \App\Models\Payment::with('ledger.student', 'cashier')
            ->whereBetween('payment_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderBy('payment_date')
            ->get();

        $filename = "collections-{$dateFrom}-to-{$dateTo}.csv";

        log_activity(\App\Models\Payment::class, 'Exported', auth()->user()->name . ' exported the collections report CSV (' . $payments->count() . ' payments, ' . $dateFrom . ' to ' . $dateTo . ').');

        return response()->stream(function () use ($payments) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Student', 'Number', 'LRN', 'AR No.', 'Cashier', 'Amount']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->payment_date->format('Y-m-d'),
                    ($p->ledger?->student?->first_name ?? '') . ' ' . ($p->ledger?->student?->last_name ?? ''),
                    $p->ledger?->student?->student_number ?? '',
                    $p->ledger?->student?->legacy_lrn ?? '',
                    $p->ar_number ?? '',
                    $p->cashier?->name ?? '',
                    $p->amount_paid,
                ]);
            }
            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportReceivablesReport(Request $request)
    {
        $ledgers = \App\Models\StudentLedger::with(['student', 'payments' => fn($q) => $q->orderByDesc('payment_date')->orderByDesc('id')])
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom || $dateTo) {
            $ledgers = $ledgers->filter(fn($l) => $l->payments->isEmpty() || (($d = $l->payments->first()?->payment_date?->format('Y-m-d')) && (!$dateFrom || $d >= $dateFrom) && (!$dateTo || $d <= $dateTo)))->values();
        }

        $total = $ledgers->sum('balance');
        $filename = 'receivables-' . now()->format('Y-m-d') . '.csv';

        log_activity(\App\Models\StudentLedger::class, 'Exported', auth()->user()->name . ' exported the receivables report CSV (' . $ledgers->count() . ' student(s), total ₱' . number_format($total, 2) . ').');

        return response()->stream(function () use ($ledgers, $total) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Student', 'Number', 'LRN', 'Balance']);
            foreach ($ledgers as $ledger) {
                $student = $ledger->student;
                $pay = $ledger->payments->first();
                fputcsv($fh, [
                    $pay?->payment_date?->format('Y-m-d') ?? '',
                    trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                    $student?->student_number ?? '',
                    $student?->legacy_lrn ?? '',
                    $ledger->balance,
                ]);
            }
            fputcsv($fh, ['', 'TOTAL', '', '', $total]);
            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportClinicReport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        $logs = \App\Models\ClinicLog::with('student.enrollments.section')
            ->whereBetween('visit_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderByDesc('visit_date')
            ->get();
        $filename = 'clinic_report_' . $dateFrom . '_to_' . $dateTo . '.csv';
        log_activity(\App\Models\ClinicLog::class, 'Exported', auth()->user()->name . ' exported the clinic report CSV (aggregates, ' . $logs->count() . ' visit(s), ' . $dateFrom . ' to ' . $dateTo . ').');

        // Privacy: aggregates only — no per-student diagnosis rows.
        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $byGrade = $logs->groupBy(fn($l) => $l->student?->enrollments->where('status', 'Active')->first()?->section?->grade_level ?? 'Unknown')
            ->map->count()->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);
        $topSymptoms = $logs->pluck('symptoms')->filter()->flatMap(fn($s) => array_map('trim', explode(',', $s)))
            ->countBy()->sortDesc()->take(10);

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function () use ($logs, $byGrade, $topSymptoms, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Clinic Report (Aggregates)', $dateFrom . ' to ' . $dateTo]);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Visits', $logs->count()]);
            fputcsv($file, ['Unique Patients', $logs->pluck('student_id')->unique()->count()]);
            fputcsv($file, ['Referred Out', $logs->whereNotNull('referred_to')->count()]);
            fputcsv($file, ['Open Cases', $logs->where('is_open', true)->count()]);
            fputcsv($file, []);
            fputcsv($file, ['Visits by Grade', 'Count']);
            foreach ($byGrade as $grade => $count) {
                fputcsv($file, [$grade, $count]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Top Symptoms', 'Count']);
            foreach ($topSymptoms as $symptom => $count) {
                fputcsv($file, [$symptom, $count]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function exportStudentStatsReport()
    {
        $gradeRank = ['Kinder'=>0,'Grade 1'=>1,'Grade 2'=>2,'Grade 3'=>3,'Grade 4'=>4,'Grade 5'=>5,'Grade 6'=>6,'Grade 7'=>7,'Grade 8'=>8,'Grade 9'=>9,'Grade 10'=>10,'Grade 11'=>11,'Grade 12'=>12];
        $byGrade = Enrollment::with('section')->where('status', 'Active')->get()
            ->groupBy(fn($e) => $e->section?->grade_level ?? 'Unknown')->map->count()
            ->sortBy(fn($_, $k) => $gradeRank[$k] ?? 99);
        $bySection = Enrollment::with('section')->where('status', 'Active')->get()
            ->groupBy(fn($e) => $e->section?->section_name ?? 'Unknown')->map->count()->sortKeys();
        $byYear = Enrollment::where('status', 'Active')->get()->groupBy('school_year')->map->count()->sortKeysDesc();
        $byGender = Student::whereHas('enrollments', fn($q) => $q->where('status', 'Active'))->get()
            ->groupBy(fn($s) => $s->gender ?? 'Unknown')->map->count();
        $byStrand = Enrollment::where('status', 'Active')->whereNotNull('strand')->get()->groupBy('strand')->map->count();

        $filename = 'student_statistics_' . now()->format('Ymd_His') . '.csv';
        log_activity(Enrollment::class, 'Exported', auth()->user()->name . ' exported the student statistics CSV (' . $byGrade->sum() . ' enrolled).');
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"$filename\""];
        $callback = function () use ($byGrade, $bySection, $byYear, $byGender, $byStrand) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Category', 'Label', 'Count']);
            foreach (['By Grade' => $byGrade, 'By Section' => $bySection, 'By School Year' => $byYear, 'By Gender' => $byGender, 'By Strand' => $byStrand] as $category => $rows) {
                foreach ($rows as $label => $count) {
                    fputcsv($file, [$category, $label, $count]);
                }
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Assign Fees hub — Fee Assign / Graduation Fees on one page
     * (spec: directress-menu-restructure.md). Composition only: the same
     * queries as the two standalone pages, so lists can never drift apart.
     * Standalone routes stay valid by being untouched. First paint is
     * unfiltered; tab searches post to the standalone addresses.
     */
    public function assignFees(): View
    {
        // Fee Assign tab — same data as fees() (unfiltered first paint).
        $fees = FeeSchedule::query()->orderBy('grade_level')->orderBy('term')->get()->groupBy('grade_level');
        $terms = ['1st Term', '2nd Term', '3rd Term'];
        $schoolYears = all_school_years();

        // Graduation Fees tab — same data as graduationFees().
        // Named $gradFees to avoid colliding with the fee tab's $fees.
        $gradFees = GraduationFee::orderBy('grade_level')->orderBy('school_year')->get()->groupBy('grade_level');

        return view('portal.directress.assign-fees', compact('fees', 'terms', 'schoolYears', 'gradFees'));
    }

    /**
     * Approvals hub — Discount Approvals / Promotion sign-off on one page
     * (spec: directress-menu-restructure.md). Composition only: the same
     * queries as the two standalone pages, so lists and badge counts can
     * never drift apart. Standalone routes stay valid by being untouched.
     */
    public function approvals(): View
    {
        // Discount Approvals tab — same data as DiscountRequestController@reviewIndex.
        $pending = \App\Models\DiscountRequest::with(['ledger.student.user', 'ledger.student.enrollments.section', 'requester'])
            ->where('status', \App\Models\DiscountRequest::STATUS_PENDING)
            ->latest()
            ->get();
        $history = \App\Models\DiscountRequest::with(['ledger.student.user', 'requester', 'reviewer'])
            ->whereIn('status', [\App\Models\DiscountRequest::STATUS_APPROVED, \App\Models\DiscountRequest::STATUS_REJECTED, \App\Models\DiscountRequest::STATUS_APPLIED])
            ->latest()
            ->take(50)
            ->get();

        // Promotion tab — same data as PromotionWorkflowController@directressIndex.
        $proposals = \App\Models\PromotionProposal::with(['enrollment.student.ledger', 'enrollment.section', 'proposer'])
            ->where('status', \App\Models\PromotionProposal::STATUS_PRINCIPAL_APPROVED)
            ->latest()
            ->get();

        // Badges — counted from the same collections above, so they always match.
        $badgeCounts = [
            'discount-approvals' => $pending->count(),
            'promotion' => $proposals->count(),
        ];

        return view('portal.directress.approvals', compact('pending', 'history', 'proposals', 'badgeCounts'));
    }
}
