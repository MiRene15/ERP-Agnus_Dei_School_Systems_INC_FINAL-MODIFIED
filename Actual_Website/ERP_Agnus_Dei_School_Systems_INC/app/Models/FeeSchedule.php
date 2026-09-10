<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'term',
        'tuition_fee',
        'misc_fee',
        'misc_fee_items',
        'school_year',
    ];

    protected $casts = [
        'misc_fee_items' => 'array',
    ];
}
