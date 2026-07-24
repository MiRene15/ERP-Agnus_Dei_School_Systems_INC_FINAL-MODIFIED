<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'section_id',
        'school_year',
        'strand',
        'status',
        'promoted_to_enrollment_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Classes::class, 'enrollment_subject', 'enrollment_id', 'class_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function promotedToEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'promoted_to_enrollment_id');
    }
}
