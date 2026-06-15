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
        // Staff Account Management
        Route::resource('admin/users', UserController::class)->names('admin.users');
        Route::post('admin/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('admin.users.toggle-status');
        Route::post('admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('admin.users.reset-password');
    });

    Route::middleware(['role:2'])->group(function() {
        Route::get('/registrar/dashboard', [RegistrarController::class, 'index'])->name('registrar.dashboard');
        Route::get('/registrar/admissions', [RegistrarAdmissionController::class, 'index'])->name('registrar.admissions.index');
        Route::get('/registrar/admissions/{admission}', [RegistrarAdmissionController::class, 'show'])->name('registrar.admissions.show');
        Route::post('/registrar/admissions/{admission}/approve', [RegistrarAdmissionController::class, 'approve'])->name('registrar.admissions.approve');
        Route::post('/registrar/admissions/{admission}/reject', [RegistrarAdmissionController::class, 'reject'])->name('registrar.admissions.reject');
    });

    Route::middleware(['role:3'])->group(function() {
        Route::get('/cashier/dashboard', [CashierController::class, 'index'])->name('cashier.dashboard');
    });

    Route::middleware(['role:4'])->group(function() {
        Route::get('/teacher/dashboard', [TeacherController::class, 'index'])->name('teacher.dashboard');
    });

    Route::middleware(['role:5'])->group(function() {
        Route::get('/librarian/dashboard', [LibrarianController::class, 'index'])->name('librarian.dashboard');
    });

    Route::middleware(['role:6'])->group(function() {
        Route::get('/nurse/dashboard', [NurseController::class, 'index'])->name('nurse.dashboard');
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
    });

    // Profile Management (Provided by Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
