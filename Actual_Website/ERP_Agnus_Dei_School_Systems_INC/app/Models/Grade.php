<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'class_id',
        'grading_period',
        'final_grade',
        'status',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function classes()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
