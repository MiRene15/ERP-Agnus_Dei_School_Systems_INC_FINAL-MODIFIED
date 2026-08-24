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
        'book_id',
        'book_title',
        'borrow_date',
        'return_date',
        'returned_at',
        'status',
        'condition_at_borrow',
        'condition_at_return',
        'late_fee',
        'damage_fee',
        'lost_fee',
        'total_fees',
        'damage_notes',
        'fees_assessed',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
        'returned_at' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function librarian()
    {
        return $this->belongsTo(User::class, 'librarian_id');
    }

    public function isOverdue(): bool
    {
        if ($this->status !== 'Borrowed' || !$this->return_date) return false;
        // Treat 1970-01-01 (epoch) as invalid/missing due date
        if ($this->return_date->year === 1970) return false;
        return now()->greaterThan($this->return_date);
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return (int) $this->return_date->diffInDays(now());
    }

    public function calculateFees(float $lateFeePerDay = 5.00, array $damageRates = []): void
    {
        $this->late_fee = 0;
        $this->damage_fee = 0;
        $this->lost_fee = 0;

        if ($this->returned_at && $this->return_date && $this->returned_at->greaterThan($this->return_date)) {
            $days = $this->return_date->diffInDays($this->returned_at);
            $this->late_fee = $days * $lateFeePerDay;
        }

        if ($this->condition_at_return && $this->condition_at_return !== 'Good') {
            if ($this->condition_at_return === 'Lost' && isset($damageRates['lost'])) {
                $this->lost_fee = $damageRates['lost'];
            } elseif ($this->condition_at_return === 'Major Damage' && isset($damageRates['major'])) {
                $this->damage_fee = $damageRates['major'];
            } elseif ($this->condition_at_return === 'Minor Damage' && isset($damageRates['minor'])) {
                $this->damage_fee = $damageRates['minor'];
            }
        }

        $this->total_fees = $this->late_fee + $this->damage_fee + $this->lost_fee;
    }
}
