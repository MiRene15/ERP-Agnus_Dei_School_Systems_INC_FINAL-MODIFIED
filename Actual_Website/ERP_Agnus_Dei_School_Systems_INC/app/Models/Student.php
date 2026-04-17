<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'personal_email',
        'date_of_birth',
        'legacy_lrn',
        'status',
    ];

    /**
     * Generate a unique Student Number (YYYY-XXXXX).
     * Called by the Registrar when an admission is formally approved.
     * This is separate from the Application Number (ADM-YYYY-XXXXX).
     */
    public static function generateStudentNumber(): string
    {
        $year = date('Y');
        // Count students who already have a student number this year
        $count = static::whereYear('created_at', $year)
                        ->whereNotNull('student_number')
                        ->count() + 1;
        return $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function ledger()
    {
        return $this->hasOne(StudentLedger::class);
    }
}

