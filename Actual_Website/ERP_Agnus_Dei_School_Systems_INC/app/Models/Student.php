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
        'middle_name',
        'last_name',
        'personal_email',
        'date_of_birth',
        'place_of_birth',
        'citizenship',
        'religion',
        'permanent_address',
        'current_address',
        'contact_number',
        'legacy_lrn',
        'previous_school',
        'previous_school_address',
        'father_name',
        'father_occupation',
        'mother_name',
        'mother_occupation',
        'guardian_name',
        'guardian_contact',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_relationship',
        'status',
        'scholarship',
        'archive_action',
        'archive_reason',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique Student Number (YYYY-XXXXX).
     * Called by the Registrar when an admission is formally approved.
     * This is separate from the Application Number (ADM-YYYY-XXXXX).
     */
    public static function generateStudentNumber(): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $year = date('Y');
            if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement(
                    'SELECT pg_advisory_xact_lock(?)',
                    [intval(hash('crc32', 'student_number_' . $year))]
                );
            }
            $count = static::whereYear('created_at', $year)
                            ->whereNotNull('student_number')
                            ->when(\Illuminate\Support\Facades\DB::getDriverName() !== 'pgsql', fn ($q) => $q->lockForUpdate())
                            ->count() + 1;
            return $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
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

