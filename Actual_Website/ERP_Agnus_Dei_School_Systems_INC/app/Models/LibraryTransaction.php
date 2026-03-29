<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'librarian_id',
        'book_title',
        'borrow_date',
        'return_date',
        'status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
