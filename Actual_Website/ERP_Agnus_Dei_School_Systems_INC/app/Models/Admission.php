<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'application_type',
        'school_year',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
