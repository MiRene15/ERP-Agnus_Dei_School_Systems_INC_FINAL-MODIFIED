<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'class_id',
        'type',
        'title',
        'raw_score',
        'max_score',
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
