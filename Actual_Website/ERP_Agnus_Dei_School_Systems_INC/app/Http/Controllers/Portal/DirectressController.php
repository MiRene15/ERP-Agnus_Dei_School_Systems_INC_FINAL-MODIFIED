<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\FeeSchedule;
use App\Models\GraduationFee;
use App\Models\StudentGraduationFee;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DirectressController extends Controller
{
    // ─── Dashboard ───────────────────────────────────────────────
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $totalTeachers = Teacher::count();
        $activeTeachers = Teacher::where('status', 'Active')->count();
        $feeSchedules = FeeSchedule::count();
        $graduationFees = GraduationFee::count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.dashboard-results', compact(
                    'totalTeachers', 'activeTeachers', 'feeSchedules', 'graduationFees'
                ))->render(),
            ]);
        }

        return view('portal.directress.dashboard', compact(
            'totalTeachers', 'activeTeachers', 'feeSchedules', 'graduationFees'
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

        FeeSchedule::create($data);

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

        return redirect()->route('directress.fees')
            ->with('success', 'Fee schedule updated.');
    }

    public function feesDestroy(FeeSchedule $fee)
    {
        $fee->delete();
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

        GraduationFee::create($data);

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

        return redirect()->route('directress.graduation-fees')
            ->with('success', 'Graduation fee updated.');
    }

    public function graduationFeesDestroy(GraduationFee $graduationFee)
    {
        $graduationFee->delete();
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

        return back()->with('success', 'Payment status updated.');
    }

    // ─── Teacher Management ─────────────────────────────────────
    public function teachers(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Teacher::with('user');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        $teachers = $query->orderBy('last_name')->paginate(20)->withQueryString();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.directress.partials.teachers-results', compact('teachers'))->render(),
            ]);
        }

        return view('portal.directress.teachers.index', compact('teachers'));
    }

    public function teachersCreate()
    {
        return view('portal.directress.teachers.create');
    }

    public function teachersStore(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:15',
            'department' => 'nullable|string|max:100',
            'teacher_number' => 'nullable|string|max:20|unique:teachers,teacher_number',
        ]);

        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);

        $user = User::create([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'role_id' => 4, // Teacher
            'contact_number' => $data['phone'],
            'password' => Hash::make($rawPassword),
            'status' => 'active',
        ]);

        Teacher::create([
            'user_id' => $user->id,
            'teacher_number' => $data['teacher_number'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'department' => $data['department'],
            'status' => 'Active',
        ]);

        return redirect()->route('directress.teachers')
            ->with('success', "Teacher account created! Temporary password: <strong>{$rawPassword}</strong>.");
    }

    public function teachersEdit(Teacher $teacher)
    {
        return view('portal.directress.teachers.edit', compact('teacher'));
    }

    public function teachersUpdate(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,
            'phone' => 'nullable|string|max:15',
            'department' => 'nullable|string|max:100',
            'teacher_number' => 'nullable|string|max:20|unique:teachers,teacher_number,' . $teacher->id,
            'status' => 'required|in:Active,Inactive',
        ]);

        $teacher->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'department' => $data['department'],
            'teacher_number' => $data['teacher_number'],
            'status' => $data['status'],
        ]);

        $teacher->user->update([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $data['email'],
            'contact_number' => $data['phone'],
            'status' => $data['status'] === 'Active' ? 'active' : 'inactive',
        ]);

        return redirect()->route('directress.teachers')
            ->with('success', 'Teacher profile updated.');
    }

    public function teachersResetPassword(Teacher $teacher)
    {
        $rawPassword = Str::upper(Str::random(4)) . rand(100, 999) . '!' . Str::random(3);
        $teacher->user->update(['password' => Hash::make($rawPassword)]);

        return back()->with('success', "Password reset for {$teacher->first_name} {$teacher->last_name}. New temporary password: <strong>{$rawPassword}</strong>");
    }
}
