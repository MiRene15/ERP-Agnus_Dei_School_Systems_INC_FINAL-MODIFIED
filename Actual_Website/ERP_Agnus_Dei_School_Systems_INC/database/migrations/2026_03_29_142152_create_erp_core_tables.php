<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles (RBAC)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Admin, Registrar, Cashier, Teacher, Librarian, Nurse, Student
            $table->timestamps();
        });

        // 2. Users (Core Authentication for Staff, Teachers, & Unified Parents)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(7)->constrained('roles'); // Default 7 = Student/Parent Unified
            $table->string('contact_number')->nullable();
        });

        // 3. Teachers Profile
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('teacher_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department'); 
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. Students Profile (Unified Account Proxy rules apply)
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The portal account
            $table->string('student_number')->unique()->nullable(); // Format: 2026-00123
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('legacy_lrn')->nullable(); // For Old Students claiming records
            $table->string('status')->default('pre-admission'); // pre-admission, enrolled, archived, graduated
            $table->timestamps();
        });

        // 5. Subjects Catalog
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->unique();
            $table->string('name')->nullable();
            $table->string('category')->nullable(); // Core, Contextualized, Specialized
            $table->timestamps();
        });

        // 6. Sections Directory
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('grade_level');
            $table->string('section_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Classes (The Junction bridging Section, Subject, and Teacher)
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('section');
            $table->string('grade_level');
            $table->string('school_year');
            $table->string('semester')->nullable();
            $table->string('room')->nullable();
            $table->integer('capacity')->default(30);
            $table->boolean('is_advisory')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 8. Schedules
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();
        });

        // 9. Admissions Queue (Handles Phase 2 Workflow)
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('application_type', ['New', 'Old', 'Transferee']);
            $table->string('school_year');
            $table->string('status')->default('Pending'); // Pending, Approved By Registrar
            $table->timestamps();
        });

        // 10. Requirements (Document Parsing)
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->string('document_type'); // PSA, Form 138, Good Moral, ESC Grant
            $table->string('file_path');
            $table->string('status')->default('Under Review');
            $table->timestamps();
        });

        // 11. Enrollments (Official placement into the academic engine)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->string('school_year');
            $table->string('status')->default('Active'); // Active, Withdrawn, Promoted
            $table->timestamps();
        });

        // 12. Assessments (DepEd Logic: Written, Performance, Quarterly)
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->enum('type', ['Written Work', 'Performance Task', 'Quarterly Assessment']);
            $table->text('title')->nullable(); // e.g. "Quiz 1 Space Science"
            $table->decimal('raw_score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2);
            $table->timestamps();
        });

        // 13. Grades (The Final Computed Period Grades)
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->string('grading_period'); // 1st Quarter, 2nd Quarter, etc.
            $table->decimal('final_grade', 5, 2);
            $table->string('status')->default('Pending'); // Blocked by PTC, Submitted
            $table->timestamps();
        });

        // 14. Fee Schedules (Admin/Cashier Matrix)
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('grade_level');
            $table->decimal('tuition_fee', 10, 2);
            $table->decimal('misc_fee', 10, 2);
            $table->string('school_year');
            $table->timestamps();
        });

        // 15. Student Ledgers (The Financial Backbone)
        Schema::create('student_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('payment_plan', ['Plan A', 'Plan B', 'Plan C'])->default('Plan A');
            $table->decimal('total_assessed', 10, 2);
            $table->decimal('discount_applied', 10, 2)->default(0); // For Honors or Sibling
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('balance', 10, 2);
            $table->string('clearance_status')->default('Uncleared'); // Cleared = 0 balance
            $table->timestamps();
        });

        // 16. Payments (Offline Ledger Strictness)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('student_ledgers')->onDelete('cascade');
            $table->foreignId('cashier_id')->constrained('users'); // Tracks who received cash
            $table->decimal('amount_paid', 10, 2);
            $table->string('receipt_number')->unique();
            $table->date('payment_date');
            $table->timestamps();
        });

        // 17. Library Manual Logs (Manual Entry of Student Numbers)
        Schema::create('library_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('librarian_id')->constrained('users');
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->timestamps();
        });

        // 18. Library Transactions (Holds/Clearances)
        Schema::create('library_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('librarian_id')->constrained('users');
            $table->string('book_title');
            $table->date('borrow_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('Borrowed'); // Blocks clearance if Overdue
            $table->timestamps();
        });

        // 19. Clinic Logs (Medical Ledgers)
        Schema::create('clinic_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('nurse_id')->constrained('users');
            $table->text('symptoms');
            $table->text('treatment');
            $table->dateTime('incident_date');
            $table->timestamps();
        });

        // 20. Public Announcements
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('clinic_logs');
        Schema::dropIfExists('library_transactions');
        Schema::dropIfExists('library_visits');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('student_ledgers');
        Schema::dropIfExists('fee_schedules');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('requirements');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('students');
        Schema::dropIfExists('teachers');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropColumn('contact_number');
        });

        Schema::dropIfExists('roles');
    }
};
