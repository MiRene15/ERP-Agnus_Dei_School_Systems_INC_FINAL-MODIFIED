<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_code',
        'name',
        'grade_level',
        'category', // Core, Contextualized, Specialized, TVL
    ];

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }
}
