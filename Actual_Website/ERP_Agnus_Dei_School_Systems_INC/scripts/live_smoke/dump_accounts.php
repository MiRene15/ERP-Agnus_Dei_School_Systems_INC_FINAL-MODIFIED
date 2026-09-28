<?php
/**
 * Dumps record ids/emails needed by run_smoke.php to accounts.json.
 * Boots the Laravel app directly (read-only queries).
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admission;
use App\Models\Announcement;
use App\Models\Book;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\GraduationFee;
use App\Models\LibraryTransaction;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

$a = [];
$a['generated_at'] = now()->toDateTimeString();
$a['password'] = 'Agnus2026!';

$a['staff'] = [
    'admin'      => 'admin@agnusdei.local',
    'registrar'  => 'registrar@agnusdei.local',
    'cashier'    => 'cashier1@agnusdei.local',
    'directress' => 'directress@agnusdei.local',
    'principal'  => 'principal@agnusdei.local',
    'librarian'  => 'library@agnusdei.local',
    'nurse'      => 'clinic@agnusdei.local',
];
$a['staff_presence'] = [];
foreach ($a['staff'] as $k => $email) {
    $u = DB::table('users')->where('email', $email)->first();
    $a['staff_presence'][$k] = $u ? ['id' => $u->id, 'role_id' => $u->role_id, 'status' => $u->status ?? null] : null;
}

// ---- Teacher with the most active classes --------------------------------
$tid = Classes::where('status', 'active')->whereNotNull('teacher_id')
    ->groupBy('teacher_id')->orderByRaw('count(*) desc')->value('teacher_id');
$teacher = DB::table('users')->find($tid);
$a['teacher'] = ['id' => $teacher->id ?? null, 'email' => $teacher->email ?? null];

$cls = Classes::where('teacher_id', $tid)->where('status', 'active')
    ->withCount('enrollments')->orderByDesc('enrollments_count')->first();
$a['teacher_class_id'] = $cls?->id;
$a['teacher_class_enrollment_id'] = $cls?->enrollments()->first()?->id;
$a['teacher_class_grade'] = $cls ? ['grade_level' => $cls->grade_level, 'section' => $cls->section] : null;

// ---- Student: enrolled, has grades on an enrollment, has payments --------
$enr = Enrollment::whereHas('grades')->where('status', 'Active')
    ->with(['student.user', 'student.ledger.payments'])
    ->get()
    ->first(fn ($e) => $e->student && $e->student->ledger && $e->student->ledger->payments->isNotEmpty());
$st = $enr?->student;
$a['student'] = [
    'id'             => $st?->id,
    'user_id'        => $st?->user_id,
    'email'          => $st?->user?->email,
    'enrollment_id'  => $enr?->id,
    'ledger_id'      => $st?->ledger?->id,
    'ledger_balance' => $st?->ledger?->balance,
    'existing_payment_id' => $st?->ledger?->payments->last()?->id,
];

// full ledger snapshot so cleanup can revert the test payment exactly
$a['payment_ledger_snapshot'] = $st?->ledger?->getAttributes();
$a['payment_max_id_before'] = DB::table('payments')->max('id');

// student with no open library borrows (borrow-limit safe for the borrow test)
$openBorrowers = DB::table('library_transactions')->where('status', 'Borrowed')->pluck('student_id')->unique();
$a['borrow_student_id'] = DB::table('students')->whereNotIn('id', $openBorrowers->all())->value('id');


// the student's own admission + requirement (for own-record view tests)
$studentAdm = $st ? Admission::where('student_id', $st->id)->with('requirements')->latest()->first() : null;
$a['student_admission_id'] = $studentAdm?->id;
$a['student_requirement_id'] = $studentAdm?->requirements->first()?->id;

// requirements with stored file bytes (viewRequirement 404s without content)
$a['requirement_with_content_id'] = DB::table('requirements')->whereNotNull('file_content')->value('id');
$ownWithContent = $st ? DB::table('requirements')
    ->join('admissions', 'admissions.id', '=', 'requirements.admission_id')
    ->where('admissions.student_id', $st->id)
    ->whereNotNull('requirements.file_content')
    ->value('requirements.id') : null;
$a['student_requirement_with_content_id'] = $ownWithContent;

// ---- Admissions / requirements / withdrawals ----------------------------
$adm = Admission::whereHas('requirements')->latest()->first();
$a['admission_id'] = $adm?->id;
$a['requirement_id'] = $adm?->requirements->first()?->id;
$a['withdrawal_id'] = Withdrawal::latest()->first()?->id;

// ---- Registrar: sections -------------------------------------------------
$a['section_id'] = Section::latest()->first()?->id;

// ---- Admin: edit targets -------------------------------------------------
$a['admin_edit_user_id'] = DB::table('users')->where('role_id', 2)->value('id');
$a['subject_id'] = Subject::latest()->first()?->id;

// ---- Cashier: receipt + payment student ---------------------------------
$pay = Payment::latest()->first();
$a['payment_id'] = $pay?->id;
$a['payment_ledger_id'] = $pay?->ledger_id;

// ---- Librarian: books + open transaction ---------------------------------
$a['book_available_id'] = Book::where('is_active', true)->where('available_quantity', '>', 0)->latest()->first()?->id;
$a['book_edit_id'] = Book::where('is_active', true)->latest()->first()?->id;
$txn = LibraryTransaction::whereNull('return_date')->latest()->first()
    ?? LibraryTransaction::latest()->first();
$a['txn_id'] = $txn?->id;

// ---- Directress: fee + graduation fee ------------------------------------
$a['fee_id'] = FeeSchedule::latest()->first()?->id;
$a['grad_fee_id'] = GraduationFee::latest()->first()?->id;

// ---- Principal: schedules (existing + conflict candidates) ---------------
$sched = Schedule::with('schoolClass')->latest()->first();
$a['schedule_id'] = $sched?->id;
$a['existing_schedule'] = $sched ? [
    'id'         => $sched->id,
    'class_id'   => $sched->class_id,
    'grade'      => $sched->schoolClass?->grade_level,
    'section'    => $sched->schoolClass?->section,
    'day'        => $sched->day_of_week,
    'start'      => substr((string) $sched->start_time, 0, 5),
    'end'        => substr((string) $sched->end_time, 0, 5),
    'room'       => $sched->room,
    'teacher_id' => $sched->schoolClass?->teacher_id,
] : null;

// another active class in the same grade+section (for section-overlap test)
$secConflict = null;
if ($sched && $sched->schoolClass) {
    $secConflict = Classes::where('status', 'active')
        ->where('grade_level', $sched->schoolClass->grade_level)
        ->where('section', $sched->schoolClass->section)
        ->where('id', '!=', $sched->class_id)
        ->first();
}
$a['section_conflict_class_id'] = $secConflict?->id;

// any active class (for valid-create + same-class conflict tests)
$a['any_active_class_id'] = Classes::where('status', 'active')->value('id');
$a['announcement_id'] = Announcement::latest()->first()?->id;

// ---- misc ---------------------------------------------------------------
$a['active_school_year'] = active_school_year()?->school_year ?? active_school_year()?->year ?? null;
$a['counts'] = [
    'users'       => DB::table('users')->count(),
    'students'    => DB::table('students')->count(),
    'classes'     => DB::table('classes')->where('status', 'active')->count(),
    'schedules'   => DB::table('schedules')->count(),
    'enrollments' => DB::table('enrollments')->count(),
    'grades'      => DB::table('grades')->count(),
    'payments'    => DB::table('payments')->count(),
    'books'       => DB::table('books')->count(),
];

$out = dirname(__DIR__, 2) . '/scripts/live_smoke/accounts.json';
file_put_contents($out, json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Wrote {$out}\n" . json_encode($a, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
