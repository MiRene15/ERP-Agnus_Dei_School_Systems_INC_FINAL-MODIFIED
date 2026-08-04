<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'application_number',
        'application_type',
        'grade_level',
        'strand',
        'school_year',
        'status',
        'draft_data',
    ];

    protected function casts(): array
    {
        return [
            'draft_data' => 'array',
        ];
    }

    /**
     * Auto-generate a unique Application Number (ADM-YYYY-XXXXX)
     * when a new admission record is created.
     * This ID tracks the application process only.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admission) {
            $admission->application_number = \Illuminate\Support\Facades\DB::transaction(function () {
                $year = date('Y');
                if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                    \Illuminate\Support\Facades\DB::statement(
                        'SELECT pg_advisory_xact_lock(?)',
                        [intval(hash('crc32', 'application_number_' . $year))]
                    );
                }
                $count = static::whereYear('created_at', $year)
                    ->when(\Illuminate\Support\Facades\DB::getDriverName() !== 'pgsql', fn ($q) => $q->lockForUpdate())
                    ->count() + 1;
                return 'ADM-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            });
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }
}

