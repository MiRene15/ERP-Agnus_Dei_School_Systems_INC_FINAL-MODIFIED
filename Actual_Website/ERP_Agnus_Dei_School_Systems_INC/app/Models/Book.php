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
        'serial_number',
        'publisher',
        'year_published',
        'quantity',
        'available_quantity',
        'price',
        'is_active',
        'inactive_reason',
        'inactive_at',
        'deactivated_by',
        'condition',
        'replacement_of',
    ];

    protected $casts = [
        'inactive_at' => 'datetime',
    ];

    public function borrowings()
    {
        return $this->hasMany(LibraryTransaction::class, 'book_id');
    }

    public function replacementOf()
    {
        return $this->belongsTo(Book::class, 'replacement_of');
    }

    public function deactivator()
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }
}
