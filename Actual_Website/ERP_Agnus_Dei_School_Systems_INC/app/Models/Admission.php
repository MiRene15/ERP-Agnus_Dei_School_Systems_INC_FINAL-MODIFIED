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
        'school_year',
        'status',
    ];

    /**
     * Auto-generate a unique Application Number (ADM-YYYY-XXXXX)
     * when a new admission record is created.
     * This ID tracks the application process only.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admission) {
            $year = date('Y');
            // Count existing applications this year and pad to 5 digits
            $count = static::whereYear('created_at', $year)->count() + 1;
            $admission->application_number = 'ADM-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

