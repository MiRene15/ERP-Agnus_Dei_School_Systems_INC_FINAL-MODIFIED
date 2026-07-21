<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraduationFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'school_year',
        'graduation_fee',
        'other_fees',
    ];

    public function studentGraduationFees()
    {
        return $this->hasMany(StudentGraduationFee::class);
    }
}
