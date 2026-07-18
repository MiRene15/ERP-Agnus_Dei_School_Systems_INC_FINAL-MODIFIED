<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'publisher',
        'year_published',
        'quantity',
        'available_quantity',
        'price',
    ];

    public function borrowings()
    {
        return $this->hasMany(LibraryTransaction::class, 'book_title', 'title');
    }
}
