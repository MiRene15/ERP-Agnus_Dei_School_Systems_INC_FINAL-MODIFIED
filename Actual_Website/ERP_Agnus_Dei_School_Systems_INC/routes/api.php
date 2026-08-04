<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

// Public: Token issuance
Route::post('/auth/token', [ApiController::class, 'token']);

// Protected: All authenticated API routes
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Any authenticated user
    Route::get('/me', [ApiController::class, 'me']);

    // Admin (role 1)
    Route::middleware(['role:1'])->prefix('admin')->group(function () {
        Route::get('/users', [ApiController::class, 'adminUsers']);
        Route::get('/users/{user}', [ApiController::class, 'adminUserShow']);
        Route::get('/activity-logs', [ApiController::class, 'adminActivityLogs']);
    });

    // Registrar (role 2)
    Route::middleware(['role:2'])->prefix('registrar')->group(function () {
        Route::get('/admissions', [ApiController::class, 'registrarAdmissions']);
        Route::get('/admissions/{admission}', [ApiController::class, 'registrarAdmissionShow']);
        Route::get('/students', [ApiController::class, 'registrarStudents']);
    });

    // Cashier (role 3)
    Route::middleware(['role:3'])->prefix('cashier')->group(function () {
        Route::get('/payments', [ApiController::class, 'cashierPayments']);
        Route::get('/ledgers', [ApiController::class, 'cashierLedgers']);
    });

    // Teacher (role 4)
    Route::middleware(['role:4'])->prefix('teacher')->group(function () {
        Route::get('/classes', [ApiController::class, 'teacherClasses']);
        Route::get('/classes/{class}', [ApiController::class, 'teacherClassShow']);
        Route::get('/classes/{class}/grades', [ApiController::class, 'teacherClassGrades']);
    });

    // Librarian (role 5)
    Route::middleware(['role:5'])->prefix('librarian')->group(function () {
        Route::get('/books', [ApiController::class, 'librarianBooks']);
        Route::get('/loans', [ApiController::class, 'librarianLoans']);
    });

    // Nurse (role 6)
    Route::middleware(['role:6'])->prefix('nurse')->group(function () {
        Route::get('/clinic-logs', [ApiController::class, 'nurseClinicLogs']);
    });

    // Student (role 7)
    Route::middleware(['role:7'])->prefix('student')->group(function () {
        Route::get('/me', [ApiController::class, 'studentMe']);
        Route::get('/admission', [ApiController::class, 'studentAdmission']);
        Route::get('/enrollments', [ApiController::class, 'studentEnrollments']);
        Route::get('/grades', [ApiController::class, 'studentGrades']);
        Route::get('/ledger', [ApiController::class, 'studentLedger']);
    });

    // Directress (role 8)
    Route::middleware(['role:8'])->prefix('directress')->group(function () {
        Route::get('/fees', [ApiController::class, 'directressFees']);
        Route::get('/collections', [ApiController::class, 'directressCollections']);
    });

    // Principal (role 9)
    Route::middleware(['role:9'])->prefix('principal')->group(function () {
        Route::get('/schedules', [ApiController::class, 'principalSchedules']);
        Route::get('/grades', [ApiController::class, 'principalGrades']);
        Route::get('/announcements', [ApiController::class, 'principalAnnouncements']);
    });
});
