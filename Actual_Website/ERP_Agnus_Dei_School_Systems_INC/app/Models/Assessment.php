<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'class_id',
        'type',
        'title',
        'raw_score',
        'max_score',
        'grading_period',
        'assessment_date',
        'remarks',
    ];

    /**
     * Single assessment-type list shared by batch + per-student entry and the
     * computed formula (specs: assessment-batch-entry.md, computed-single-grade.md).
     * Complete words everywhere — no abbreviations. Old stored values
     * ('Written Work', 'Quiz', 'Seatwork', 'Exam') are grandfathered by the
     * formula grouping, never offered for new rows.
     */
    public const ASSESSMENT_TYPES = [
        'Written Works',
        'Performance Tasks',
        'Quarterly Assessment',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
