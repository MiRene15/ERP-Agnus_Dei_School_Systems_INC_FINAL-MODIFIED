<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admission;
use App\Models\Book;
use App\Models\Classes;
use App\Models\ClinicLog;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Grade;
use App\Models\LibraryTransaction;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['status' => 'success', 'message' => $message, 'data' => $data], $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null): JsonResponse
    {
        $payload = ['status' => 'error', 'message' => $message];
        if ($errors) $payload['errors'] = $errors;
        return response()->json($payload, $code);
    }

    // ─── Public: Token ──────────────────────────────

    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if ($user->status !== 'active') {
            return $this->error('Account is inactive.', 403);
        }

        $deviceName = $request->device_name ?? ($request->header('User-Agent') ?: 'api-token');
        $token = $user->createToken($deviceName)->plainTextToken;

        log_activity($user, 'API Token Created', 'API access token issued.');

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => match($user->role_id) {
                    1 => 'admin', 2 => 'registrar', 3 => 'cashier', 4 => 'teacher',
                    5 => 'librarian', 6 => 'nurse', 7 => 'student', 8 => 'directress', 9 => 'principal',
                    default => 'unknown',
                },
            ],
        ], 'Token issued.');
    }

    // ─── Any Authenticated User ─────────────────────

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');
        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'status' => $user->status,
            'role' => $user->role?->name,
        ]);
    }

    // ─── Admin (role 1) ─────────────────────────────

    public function adminUsers(): JsonResponse
    {
        $users = User::with('role')->whereNotIn('role_id', [7])->orderBy('role_id')->get();
        return $this->success($users);
    }

    public function adminUserShow(User $user): JsonResponse
    {
        return $this->success($user->load('role'));
    }

    public function adminActivityLogs(Request $request): JsonResponse
    {
        $query = ActivityLog::with('causer');
        if ($request->filled('user_id')) $query->where('causer_id', $request->user_id);
        if ($request->filled('event')) $query->where('event', $request->event);
        $logs = $query->latest()->paginate($request->get('per_page', 25));
        return $this->success($logs);
    }

    // ─── Registrar (role 2) ─────────────────────────

    public function registrarAdmissions(): JsonResponse
    {
        $admissions = Admission::with('student.user')->latest()->get();
        return $this->success($admissions);
    }

    public function registrarAdmissionShow(Admission $admission): JsonResponse
    {
        return $this->success($admission->load('student.user', 'requirements'));
    }

    public function registrarStudents(): JsonResponse
    {
        $students = Student::with('user', 'enrollments.section')->latest()->get();
        return $this->success($students);
    }

    // ─── Cashier (role 3) ───────────────────────────

    public function cashierPayments(): JsonResponse
    {
        $payments = Payment::with('ledger.student', 'cashier')->latest()->paginate(25);
        return $this->success($payments);
    }

    public function cashierLedgers(): JsonResponse
    {
        $ledgers = StudentLedger::with('student.user')->latest()->get();
        return $this->success($ledgers);
    }

    // ─── Teacher (role 4) ───────────────────────────

    public function teacherClasses(): JsonResponse
    {
        $classes = Classes::where('teacher_id', auth()->id())->with('section', 'subject')->get();
        return $this->success($classes);
    }

    public function teacherClassShow(Classes $class): JsonResponse
    {
        if ($class->teacher_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }
        return $this->success($class->load('section', 'subject', 'enrollments.student'));
    }

    public function teacherClassGrades(Classes $class): JsonResponse
    {
        if ($class->teacher_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }
        $grades = Grade::where('class_id', $class->id)
            ->with('enrollment.student')
            ->get();
        return $this->success($grades);
    }

    // ─── Librarian (role 5) ─────────────────────────

    public function librarianBooks(): JsonResponse
    {
        $books = Book::latest()->get();
        return $this->success($books);
    }

    public function librarianLoans(): JsonResponse
    {
        $loans = LibraryTransaction::with('book', 'student.user')->latest()->paginate(25);
        return $this->success($loans);
    }

    // ─── Nurse (role 6) ─────────────────────────────

    public function nurseClinicLogs(): JsonResponse
    {
        $logs = ClinicLog::with('student.user')->latest()->paginate(25);
        return $this->success($logs);
    }

    // ─── Student (role 7) ───────────────────────────

    public function studentMe(Request $request): JsonResponse
    {
        $student = Student::where('user_id', $request->user()->id)
            ->with('user', 'enrollments.section')
            ->firstOrFail();
        return $this->success($student);
    }

    public function studentAdmission(Request $request): JsonResponse
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $admission = Admission::where('student_id', $student->id)
            ->with('requirements')
            ->latest()
            ->first();
        return $this->success($admission);
    }

    public function studentEnrollments(Request $request): JsonResponse
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $enrollments = Enrollment::where('student_id', $student->id)
            ->with('section')
            ->latest()
            ->get();
        return $this->success($enrollments);
    }

    public function studentGrades(Request $request): JsonResponse
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $grades = Grade::whereHas('enrollment', fn($q) => $q->where('student_id', $student->id))
            ->with('class.subject')
            ->get();
        return $this->success($grades);
    }

    public function studentLedger(Request $request): JsonResponse
    {
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $ledger = StudentLedger::where('student_id', $student->id)
            ->with('payments')
            ->first();
        return $this->success($ledger);
    }

    // ─── Directress (role 8) ────────────────────────

    public function directressFees(): JsonResponse
    {
        $fees = FeeSchedule::orderBy('grade_level')->orderBy('term')->get();
        return $this->success($fees);
    }

    public function directressCollections(): JsonResponse
    {
        $collections = Payment::selectRaw('DATE(payment_date) as date, SUM(amount_paid) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderByDesc('date')
            ->paginate(30);
        return $this->success($collections);
    }

    // ─── Principal (role 9) ─────────────────────────

    public function principalSchedules(): JsonResponse
    {
        $schedules = Schedule::with('teacher', 'section', 'subject')->get();
        return $this->success($schedules);
    }

    public function principalGrades(): JsonResponse
    {
        $grades = Grade::with('class.section', 'class.subject', 'enrollment.student')
            ->latest()
            ->paginate(50);
        return $this->success($grades);
    }

    public function principalAnnouncements(): JsonResponse
    {
        $announcements = \App\Models\Announcement::latest()->get();
        return $this->success($announcements);
    }
}
