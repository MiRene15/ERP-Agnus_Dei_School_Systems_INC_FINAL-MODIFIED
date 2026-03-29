<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'tuition_fee',
        'misc_fee',
        'school_year',
    ];
}
