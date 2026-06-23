<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnrollmentSubject extends Model
{
    protected $fillable = ['enrollment_id', 'class_id'];

    protected $table = 'enrollment_subject';

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
