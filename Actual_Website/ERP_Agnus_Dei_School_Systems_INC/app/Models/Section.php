<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'section_name',
        'is_active',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}
