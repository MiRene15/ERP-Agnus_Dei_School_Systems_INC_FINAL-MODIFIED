<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'librarian_id',
        'time_in',
        'time_out',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
