<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PromotionalWebsite\HomeController;
use App\Http\Controllers\PromotionalWebsite\InquiryController;

// Portal Controllers
use App\Http\Controllers\Portal\AdminController;
use App\Http\Controllers\Portal\RegistrarController;
use App\Http\Controllers\Portal\CashierController;
use App\Http\Controllers\Portal\TeacherController;
use App\Http\Controllers\Portal\StudentController;
use App\Http\Controllers\Portal\StudentAdmissionController;
use App\Http\Controllers\Portal\StudentEnrollmentController;
use App\Http\Controllers\Portal\RegistrarAdmissionController;
use App\Http\Controllers\Portal\LibrarianController;
use App\Http\Controllers\Portal\NurseController;
use App\Http\Controllers\Portal\ReportCardController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Promotional Website Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index']);

Route::get('/vision', function () {
    return view('PromotionalWebsite.vision');
});

Route::get('/mission', function () {
    return view('PromotionalWebsite.mission');
});

Route::get('/academics', function () {
    return view('PromotionalWebsite.academics');
});

Route::get('/admissions', function () {
    return view('PromotionalWebsite.admissions');
});

Route::get('/identity', function () {
    return view('PromotionalWebsite.identity');
});

Route::get('/educational-philosophy', function () {
    return view('PromotionalWebsite.educational-philosophy');
});

Route::get('/institutional-background', function () {
    return view('PromotionalWebsite.institutional-background');
});

Route::get('/contact-information', function () {
    return view('PromotionalWebsite.contact-information');
});

Route::get('/program-offerings', function () {
    return view('PromotionalWebsite.program-offerings');
});

Route::get('/requirements-procedures', function () {
    return view('PromotionalWebsite.requirements-procedures');
});

Route::get('/discounts-privileges', function () {
    return view('PromotionalWebsite.discounts-privileges');
});

Route::get('/inquiry', [InquiryController::class, 'show']);
Route::post('/inquiry', [InquiryController::class, 'store']);


/*
|--------------------------------------------------------------------------
| Account Portal Routes (Protected by Auth & Role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Default dashboard route - redirects to role-specific dashboard
    Route::get('/dashboard', function() {
        $url = match(auth()->user()->role_id) {
            1 => '/admin/dashboard',
            2 => '/registrar/dashboard',
            3 => '/cashier/dashboard',
            4 => '/teacher/dashboard',
            5 => '/librarian/dashboard',
            6 => '/nurse/dashboard',
            7 => '/student/dashboard',
            default => '/',
        };
        return redirect($url);
    })->name('dashboard');

    Route::middleware(['role:1'])->group(function() {
        Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/pending-accounts', [AdminController::class, 'pendingAccounts'])->name('admin.pending-accounts');
        Route::post('/admin/confirm-account/{ledger}', [AdminController::class, 'confirmAccount'])->name('admin.confirm-account');
        // Staff Account Management
        Route::resource('admin/users', UserController::class)->names('admin.users');
        Route::post('admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
        // Schedule Management
        Route::get('/admin/schedules', [\App\Http\Controllers\Admin\ScheduleController::class, 'index'])->name('admin.schedules.index');
        Route::get('/admin/schedules/create', [\App\Http\Controllers\Admin\ScheduleController::class, 'create'])->name('admin.schedules.create');
        Route::post('/admin/schedules', [\App\Http\Controllers\Admin\ScheduleController::class, 'store'])->name('admin.schedules.store');
        Route::get('/admin/schedules/{class}/slots', [\App\Http\Controllers\Admin\ScheduleController::class, 'manageSlots'])->name('admin.schedules.slots');
        Route::post('/admin/schedules/{class}/slots', [\App\Http\Controllers\Admin\ScheduleController::class, 'storeSlot'])->name('admin.schedules.slots.store');
        Route::delete('/admin/schedules/slots/{schedule}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroySlot'])->name('admin.schedules.slots.destroy');
        Route::get('/admin/schedules/{class}/edit', [\App\Http\Controllers\Admin\ScheduleController::class, 'edit'])->name('admin.schedules.edit');
        Route::patch('/admin/schedules/{class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'update'])->name('admin.schedules.update');
        Route::delete('/admin/schedules/{class}', [\App\Http\Controllers\Admin\ScheduleController::class, 'destroy'])->name('admin.schedules.destroy');
        // Subjects Management
        Route::resource('admin/subjects', \App\Http\Controllers\Admin\SubjectController::class)->names('admin.subjects');
        // Sections Management
        Route::resource('admin/sections', \App\Http\Controllers\Admin\SectionController::class)->names('admin.sections');
        // Fee Schedule Management
        Route::get('/admin/fees', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'index'])->name('admin.fees.index');
        Route::get('/admin/fees/create', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'create'])->name('admin.fees.create');
        Route::post('/admin/fees', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'store'])->name('admin.fees.store');
        Route::get('/admin/fees/{fee}/edit', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'edit'])->name('admin.fees.edit');
        Route::patch('/admin/fees/{fee}', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'update'])->name('admin.fees.update');
        Route::delete('/admin/fees/{fee}', [\App\Http\Controllers\Admin\FeeScheduleController::class, 'destroy'])->name('admin.fees.destroy');
        // Promotion / End-of-Year
        Route::get('/admin/promotion', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('admin.promotion.index');
        Route::post('/admin/promotion/process', [\App\Http\Controllers\Admin\PromotionController::class, 'process'])->name('admin.promotion.process');
        // School Settings
        Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    });

    Route::middleware(['role:2'])->group(function() {
        Route::get('/registrar/dashboard', [RegistrarController::class, 'index'])->name('registrar.dashboard');
        Route::get('/registrar/admissions', [RegistrarAdmissionController::class, 'index'])->name('registrar.admissions.index');
        Route::get('/registrar/admissions/{admission}', [RegistrarAdmissionController::class, 'show'])->name('registrar.admissions.show');
        Route::post('/registrar/admissions/{admission}/approve', [RegistrarAdmissionController::class, 'approve'])->name('registrar.admissions.approve');
        Route::post('/registrar/admissions/{admission}/reject', [RegistrarAdmissionController::class, 'reject'])->name('registrar.admissions.reject');
        Route::post('/registrar/requirements/{requirement}/verify', [RegistrarAdmissionController::class, 'verifyRequirement'])->name('registrar.admissions.verify-requirement');
        Route::get('/registrar/withdrawals', [\App\Http\Controllers\Portal\WithdrawalController::class, 'index'])->name('registrar.withdrawals.index');
        Route::post('/registrar/withdrawals/{withdrawal}/approve', [\App\Http\Controllers\Portal\WithdrawalController::class, 'approve'])->name('registrar.withdrawals.approve');
        Route::post('/registrar/withdrawals/{withdrawal}/reject', [\App\Http\Controllers\Portal\WithdrawalController::class, 'reject'])->name('registrar.withdrawals.reject');
        // Report Cards
        Route::get('/registrar/report-cards', [ReportCardController::class, 'index'])->name('registrar.report-cards.index');
        Route::get('/registrar/report-cards/{enrollment}', [ReportCardController::class, 'show'])->name('registrar.report-cards.show');
        Route::get('/registrar/report-cards/{enrollment}/print', [ReportCardController::class, 'print'])->name('registrar.report-cards.print');
    });

    Route::middleware(['role:3'])->group(function() {
        Route::get('/cashier/dashboard', [CashierController::class, 'index'])->name('cashier.dashboard');
        Route::get('/cashier/payment/{student}', [CashierController::class, 'showPayment'])->name('cashier.payment');
        Route::post('/cashier/payment/{student}/process', [CashierController::class, 'processPayment'])->name('cashier.payment.process');
    });

    Route::middleware(['role:4'])->group(function() {
        Route::get('/teacher/dashboard', [TeacherController::class, 'index'])->name('teacher.dashboard');
        Route::get('/teacher/classes', [TeacherController::class, 'classes'])->name('teacher.classes');
        Route::get('/teacher/classes/{class}', [TeacherController::class, 'showClass'])->name('teacher.classes.show');
        Route::post('/teacher/classes/{class}/grades', [TeacherController::class, 'storeGrades'])->name('teacher.grades.store');
        Route::post('/teacher/classes/{class}/submit', [TeacherController::class, 'submitGrades'])->name('teacher.grades.submit');
        Route::get('/teacher/classes/{class}/assessments', [TeacherController::class, 'assessments'])->name('teacher.assessments');
        Route::post('/teacher/classes/{class}/assessments', [TeacherController::class, 'storeAssessments'])->name('teacher.assessments.store');
        Route::get('/teacher/schedule', [TeacherController::class, 'schedule'])->name('teacher.schedule');
    });

    Route::middleware(['role:5'])->group(function() {
        Route::get('/librarian/dashboard', [LibrarianController::class, 'index'])->name('librarian.dashboard');
        Route::get('/librarian/books', [LibrarianController::class, 'books'])->name('librarian.books');
        Route::get('/librarian/books/create', [LibrarianController::class, 'createBook'])->name('librarian.books.create');
        Route::post('/librarian/books', [LibrarianController::class, 'storeBook'])->name('librarian.books.store');
    });

    Route::middleware(['role:6'])->group(function() {
        Route::get('/nurse/dashboard', [NurseController::class, 'index'])->name('nurse.dashboard');
        Route::get('/nurse/logs', [NurseController::class, 'logs'])->name('nurse.logs');
        Route::get('/nurse/logs/create', [NurseController::class, 'createLog'])->name('nurse.logs.create');
        Route::post('/nurse/logs', [NurseController::class, 'storeLog'])->name('nurse.logs.store');
    });

    // Using role 7 for Students (and potentially Parents/Guardians under unified)
    Route::middleware(['role:7'])->group(function() {
        Route::get('/student/dashboard', [StudentController::class, 'index'])->name('student.dashboard');
        Route::get('/student/admission/apply', [StudentAdmissionController::class, 'create'])->name('student.admission.create');
        Route::post('/student/admission/apply', [StudentAdmissionController::class, 'store'])->name('student.admission.store');
        Route::get('/student/admission/status', [StudentAdmissionController::class, 'status'])->name('student.admission.status');
        Route::post('/student/admission/requirements', [StudentAdmissionController::class, 'uploadRequirements'])->name('student.admission.requirements');
        Route::get('/student/enrollment/apply', [StudentEnrollmentController::class, 'create'])->name('student.enrollment.create');
        Route::post('/student/enrollment/apply', [StudentEnrollmentController::class, 'store'])->name('student.enrollment.store');
        Route::get('/student/withdrawal', [\App\Http\Controllers\Portal\WithdrawalController::class, 'create'])->name('student.withdrawal.create');
        Route::post('/student/withdrawal', [\App\Http\Controllers\Portal\WithdrawalController::class, 'store'])->name('student.withdrawal.store');
        // Report Card
        Route::get('/student/report-card', [ReportCardController::class, 'studentShow'])->name('student.report-card');
    });

    // Profile Management (Provided by Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
