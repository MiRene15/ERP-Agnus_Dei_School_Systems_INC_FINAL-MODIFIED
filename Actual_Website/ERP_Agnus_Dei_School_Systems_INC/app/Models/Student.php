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
        'date_of_birth',
        'legacy_lrn',
        'status',
    ];

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
