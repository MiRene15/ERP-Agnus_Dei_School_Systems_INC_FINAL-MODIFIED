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
        'is_open',
        'closed_at',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
