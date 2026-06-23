<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'nurse_id',
        'symptoms',
        'complaint',
        'diagnosis',
        'treatment',
        'notes',
        'referred_to',
        'incident_date',
        'visit_date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
