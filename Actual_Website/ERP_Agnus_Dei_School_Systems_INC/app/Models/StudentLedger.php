<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payment_plan',
        'total_assessed',
        'discount_applied',
        'total_paid',
        'balance',
        'clearance_status',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'ledger_id');
    }
}
