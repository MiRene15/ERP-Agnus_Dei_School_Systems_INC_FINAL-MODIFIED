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
use App\Http\Controllers\Portal\ExportController;
use App\Http\Controllers\Portal\WithdrawalController;
use App\Http\Controllers\Portal\DirectressController;
use App\Http\Controllers\Portal\PrincipalController;
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
Route::post('/inquiry', [InquiryController::class, 'store'])->middleware('throttle:inquiry');


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
            8 => '/directress/dashboard',
            9 => '/principal/dashboard',
            default => '/',
        };
        return redirect($url);
    })->name('dashboard');

    Route::post('/dismiss-welcome', function() {
        auth()->user()->update(['has_seen_welcome' => true]);
        return response()->json(['ok' => true]);
    })->name('dismiss-welcome');

    Route::middleware(['role:1'])->group(function() {
        Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/admin/pending-accounts', [AdminController::class, 'pendingAccounts'])->name('admin.pending-accounts');
        Route::post('/admin/confirm-account/{ledger}', [AdminController::class, 'confirmAccount'])->name('admin.confirm-account');
        Route::post('/admin/confirm-batch', [AdminController::class, 'confirmBatch'])->name('admin.confirm-batch');
// Staff Account Management
         Route::resource('admin/users', UserController::class)->except(['show', 'destroy'])->names('admin.users');
         Route::post('admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
         Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
         // Student Account Management
         Route::get('admin/student-accounts', [\App\Http\Controllers\Admin\StudentAccountController::class, 'index'])->name('admin.student-accounts.index');
         Route::post('admin/student-accounts/{user}/toggle-status', [\App\Http\Controllers\Admin\StudentAccountController::class, 'toggleStatus'])->name('admin.student-accounts.toggle-status');
         Route::post('admin/student-accounts/{user}/reset-password', [\App\Http\Controllers\Admin\StudentAccountController::class, 'resetPassword'])->name('admin.student-accounts.reset-password');
         // Subjects Management
         Route::resource('admin/subjects', \App\Http\Controllers\Admin\SubjectController::class)->except(['show'])->names('admin.subjects');
         // Sections Management
         Route::resource('admin/sections', \App\Http\Controllers\Admin\SectionController::class)->except(['show'])->names('admin.sections');
         // School Settings
         Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
         Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
         // Audit Logs
         Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit-logs');
         // Exports
        Route::get('/admin/exports/enrollments', [ExportController::class, 'enrollments'])->name('admin.exports.enrollments');
        Route::get('/admin/exports/grades', [ExportController::class, 'grades'])->name('admin.exports.grades');
        Route::get('/admin/exports/collections', [ExportController::class, 'collections'])->name('admin.exports.collections');
        // Promotion / End-of-Year
        Route::get('/admin/promotion', [\App\Http\Controllers\Admin\PromotionController::class, 'index'])->name('admin.promotion.index');
        Route::post('/admin/promotion/process', [\App\Http\Controllers\Admin\PromotionController::class, 'process'])->name('admin.promotion.process');
    });

    Route::middleware(['role:2'])->group(function() {
        Route::get('/registrar/dashboard', [RegistrarController::class, 'index'])->name('registrar.dashboard');
        Route::get('/registrar/admissions', [RegistrarAdmissionController::class, 'index'])->name('registrar.admissions.index');
        Route::get('/registrar/admissions/{admission}', [RegistrarAdmissionController::class, 'show'])->name('registrar.admissions.show');
        Route::post('/registrar/admissions/{admission}/approve', [RegistrarAdmissionController::class, 'approve'])->name('registrar.admissions.approve');
        Route::post('/registrar/admissions/{admission}/reject', [RegistrarAdmissionController::class, 'reject'])->name('registrar.admissions.reject');
        Route::post('/registrar/admissions/{admission}/verify-all', [RegistrarAdmissionController::class, 'verifyAll'])->name('registrar.admissions.verify-all');
        Route::post('/registrar/requirements/{requirement}/verify', [RegistrarAdmissionController::class, 'verifyRequirement'])->name('registrar.admissions.verify-requirement');
        Route::get('/registrar/requirements/{requirement}/view', [StudentAdmissionController::class, 'viewRequirement'])->name('registrar.requirements.view');
        Route::get('/registrar/withdrawals', [WithdrawalController::class, 'index'])->name('registrar.withdrawals.index');
        Route::post('/registrar/withdrawals/{withdrawal}/approve', [WithdrawalController::class, 'approve'])->name('registrar.withdrawals.approve');
        Route::post('/registrar/withdrawals/{withdrawal}/reject', [WithdrawalController::class, 'reject'])->name('registrar.withdrawals.reject');
        // Report Cards
        Route::get('/registrar/report-cards', [ReportCardController::class, 'index'])->name('registrar.report-cards.index');
        Route::get('/registrar/report-cards/{enrollment}', [ReportCardController::class, 'show'])->name('registrar.report-cards.show');
        Route::get('/registrar/report-cards/{enrollment}/print', [ReportCardController::class, 'print'])->name('registrar.report-cards.print');
    });

    Route::middleware(['role:3'])->group(function() {
        Route::get('/cashier/dashboard', [CashierController::class, 'index'])->name('cashier.dashboard');
        Route::get('/cashier/payments', [CashierController::class, 'payments'])->name('cashier.payments');
        Route::get('/cashier/search', [CashierController::class, 'searchStudents'])->name('cashier.search');
        Route::get('/cashier/payment/{student}', [CashierController::class, 'showPayment'])->name('cashier.payment');
        Route::post('/cashier/payment/{student}/process', [CashierController::class, 'processPayment'])->name('cashier.payment.process');
        Route::get('/cashier/financial/{student}', [CashierController::class, 'studentFinancial'])->name('cashier.student-financial');
        Route::get('/cashier/receipt/{payment}', [CashierController::class, 'printReceipt'])->name('cashier.receipt.print');
        Route::get('/cashier/collections', [CashierController::class, 'collectionsReport'])->name('cashier.collections-report');
        Route::get('/cashier/collections/export', [CashierController::class, 'collectionsReportExport'])->name('cashier.collections-report.export');
        Route::get('/cashier/discounts', [CashierController::class, 'discounts'])->name('cashier.discounts');
        Route::post('/cashier/discounts/{ledger}', [CashierController::class, 'updateDiscount'])->name('cashier.discounts.update');
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

        // New sub-tabs
        Route::get('/teacher/class-list', [TeacherController::class, 'classList'])->name('teacher.class-list');
        Route::get('/teacher/class-list/{class}/students', [TeacherController::class, 'classStudents'])->name('teacher.class-list.students');
        Route::get('/teacher/grade-assessment', [TeacherController::class, 'gradeAssessment'])->name('teacher.grade-assessment');
        Route::get('/teacher/grade-assessment/{class}/student/{enrollment}', [TeacherController::class, 'gradeAssessmentStudent'])->name('teacher.grade-assessment.student');
        Route::post('/teacher/grade-assessment/{class}/student/{enrollment}', [TeacherController::class, 'storeGradeAssessmentStudent'])->name('teacher.grade-assessment.student.store');
        Route::get('/teacher/computed-grades', [TeacherController::class, 'computedGrades'])->name('teacher.computed-grades');
        Route::post('/teacher/computed-grades/batch-submit', [TeacherController::class, 'batchSubmitGrades'])->name('teacher.computed-grades.batch-submit');
    });

    Route::middleware(['role:5'])->group(function() {
        Route::get('/librarian/dashboard', [LibrarianController::class, 'index'])->name('librarian.dashboard');
        Route::get('/librarian/books', [LibrarianController::class, 'books'])->name('librarian.books');
        Route::get('/librarian/books/create', [LibrarianController::class, 'createBook'])->name('librarian.books.create');
        Route::post('/librarian/books', [LibrarianController::class, 'storeBook'])->name('librarian.books.store');
        Route::get('/librarian/books/{book}/edit', [LibrarianController::class, 'editBook'])->name('librarian.books.edit');
        Route::patch('/librarian/books/{book}', [LibrarianController::class, 'updateBook'])->name('librarian.books.update');
        Route::delete('/librarian/books/{book}', [LibrarianController::class, 'destroyBook'])->name('librarian.books.destroy');
        // Inactive Books
        Route::get('/librarian/inactive-logs', [LibrarianController::class, 'inactiveBooks'])->name('librarian.inactive-logs');
        Route::patch('/librarian/books/{book}/deactivate', [LibrarianController::class, 'deactivateBook'])->name('librarian.books.deactivate');
        Route::patch('/librarian/books/{book}/reactivate', [LibrarianController::class, 'reactivateBook'])->name('librarian.books.reactivate');
        // Loan Management
        Route::get('/librarian/loans', [LibrarianController::class, 'loans'])->name('librarian.loans');
        Route::get('/librarian/loans/borrow', [LibrarianController::class, 'borrowForm'])->name('librarian.loans.borrow');
        Route::post('/librarian/loans/borrow', [LibrarianController::class, 'storeBorrow'])->name('librarian.loans.store');
        Route::get('/librarian/loans/{transaction}/return', [LibrarianController::class, 'returnForm'])->name('librarian.loans.return-form');
        Route::patch('/librarian/loans/{transaction}/return', [LibrarianController::class, 'processReturn'])->name('librarian.loans.process-return');
        Route::get('/librarian/students/search', [LibrarianController::class, 'searchStudents'])->name('librarian.students.search');
        Route::get('/librarian/books/search', [LibrarianController::class, 'searchBooks'])->name('librarian.books.search');
        Route::get('/librarian/loans/search', [LibrarianController::class, 'searchLoans'])->name('librarian.loans.search');
        // Library Visits
        Route::get('/librarian/visits', [LibrarianController::class, 'visits'])->name('librarian.visits');
        Route::post('/librarian/visits/clock-in', [LibrarianController::class, 'clockIn'])->name('librarian.visits.clock-in');
        Route::patch('/librarian/visits/{visit}/clock-out', [LibrarianController::class, 'clockOut'])->name('librarian.visits.clock-out');
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
        Route::post('/student/admission/draft', [StudentAdmissionController::class, 'saveDraft'])->name('student.admission.draft');
        Route::post('/student/admission/draft/discard', [StudentAdmissionController::class, 'discardDraft'])->name('student.admission.discard');
        Route::get('/student/admission/status', [StudentAdmissionController::class, 'status'])->name('student.admission.status');
        Route::post('/student/admission/requirements', [StudentAdmissionController::class, 'uploadRequirements'])->name('student.admission.requirements');
        Route::get('/student/admission/requirements/{requirement}/view', [StudentAdmissionController::class, 'viewRequirement'])->name('student.admission.requirements.view');
        Route::get('/student/enrollment/apply', [StudentEnrollmentController::class, 'create'])->name('student.enrollment.create');
        Route::post('/student/enrollment/apply', [StudentEnrollmentController::class, 'store'])->name('student.enrollment.store');
        Route::get('/student/withdrawal', [WithdrawalController::class, 'create'])->name('student.withdrawal.create');
        Route::post('/student/withdrawal', [WithdrawalController::class, 'store'])->name('student.withdrawal.store');
        // Report Card
        Route::get('/student/report-card', [ReportCardController::class, 'studentShow'])->name('student.report-card');
        Route::get('/student/cor', [StudentController::class, 'cor'])->name('student.cor');
        Route::get('/student/schedule', [StudentController::class, 'schedule'])->name('student.schedule');
        Route::get('/student/ledger', [StudentController::class, 'ledger'])->name('student.ledger');
    });

    // ─── School Directress (role 8) ────────────────────────────
    Route::middleware(['role:8'])->group(function() {
        Route::get('/directress/dashboard', [DirectressController::class, 'index'])->name('directress.dashboard');
        // Fee Schedule
        Route::get('/directress/fees', [DirectressController::class, 'fees'])->name('directress.fees');
        Route::get('/directress/fees/create', [DirectressController::class, 'feesCreate'])->name('directress.fees.create');
        Route::post('/directress/fees', [DirectressController::class, 'feesStore'])->name('directress.fees.store');
        Route::get('/directress/fees/{fee}/edit', [DirectressController::class, 'feesEdit'])->name('directress.fees.edit');
        Route::patch('/directress/fees/{fee}', [DirectressController::class, 'feesUpdate'])->name('directress.fees.update');
        Route::delete('/directress/fees/{fee}', [DirectressController::class, 'feesDestroy'])->name('directress.fees.destroy');
        // Graduation Fees
        Route::get('/directress/graduation-fees', [DirectressController::class, 'graduationFees'])->name('directress.graduation-fees');
        Route::get('/directress/graduation-fees/create', [DirectressController::class, 'graduationFeesCreate'])->name('directress.graduation-fees.create');
        Route::post('/directress/graduation-fees', [DirectressController::class, 'graduationFeesStore'])->name('directress.graduation-fees.store');
        Route::get('/directress/graduation-fees/{graduationFee}/edit', [DirectressController::class, 'graduationFeesEdit'])->name('directress.graduation-fees.edit');
        Route::patch('/directress/graduation-fees/{graduationFee}', [DirectressController::class, 'graduationFeesUpdate'])->name('directress.graduation-fees.update');
        Route::delete('/directress/graduation-fees/{graduationFee}', [DirectressController::class, 'graduationFeesDestroy'])->name('directress.graduation-fees.destroy');
        Route::get('/directress/graduation-fees/{graduationFee}/assign', [DirectressController::class, 'graduationFeesAssign'])->name('directress.graduation-fees.assign');
        Route::post('/directress/graduation-fees/{graduationFee}/assign', [DirectressController::class, 'graduationFeesAssignStore'])->name('directress.graduation-fees.assign.store');
        Route::get('/directress/graduation-fees/{graduationFee}/assigned', [DirectressController::class, 'graduationFeesAssigned'])->name('directress.graduation-fees.assigned');
        Route::post('/directress/graduation-fees/{assignment}/toggle-paid', [DirectressController::class, 'graduationFeesTogglePaid'])->name('directress.graduation-fees.toggle-paid');
    });

    // ─── School Principal (role 9) ─────────────────────────────
    Route::middleware(['role:9'])->group(function() {
        Route::get('/principal/dashboard', [PrincipalController::class, 'index'])->name('principal.dashboard');
        // Schedules — manual + hybrid CSV
        Route::get('/principal/schedules', [PrincipalController::class, 'schedules'])->name('principal.schedules');
        Route::post('/principal/schedules', [PrincipalController::class, 'schedulesStore'])->name('principal.schedules.store');
        Route::delete('/principal/schedules/{schedule}', [PrincipalController::class, 'schedulesDestroy'])->name('principal.schedules.destroy');
        Route::get('/principal/schedules/template', [PrincipalController::class, 'schedulesTemplate'])->name('principal.schedules.template');
        Route::post('/principal/schedules/import', [PrincipalController::class, 'schedulesImport'])->name('principal.schedules.import');
        // Grades
        Route::get('/principal/grades', [PrincipalController::class, 'grades'])->name('principal.grades');
        // Announcements
        Route::get('/principal/announcements', [PrincipalController::class, 'announcements'])->name('principal.announcements');
        Route::get('/principal/announcements/create', [PrincipalController::class, 'announcementsCreate'])->name('principal.announcements.create');
        Route::post('/principal/announcements', [PrincipalController::class, 'announcementsStore'])->name('principal.announcements.store');
        Route::get('/principal/announcements/{announcement}/edit', [PrincipalController::class, 'announcementsEdit'])->name('principal.announcements.edit');
        Route::patch('/principal/announcements/{announcement}', [PrincipalController::class, 'announcementsUpdate'])->name('principal.announcements.update');
        Route::delete('/principal/announcements/{announcement}', [PrincipalController::class, 'announcementsDestroy'])->name('principal.announcements.destroy');
    });

    // Profile Management (Provided by Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
