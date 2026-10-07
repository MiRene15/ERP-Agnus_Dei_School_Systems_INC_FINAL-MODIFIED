<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'enrollment_id', 'student_id', 'reason', 'status', 'processed_by', 'remarks',
        'refund_amount', 'refund_processed_at', 'refund_released_by',
    ];

    protected $casts = [
        'refund_processed_at' => 'datetime',
    ];

    /**
     * Estimate of the refund due if this withdrawal is approved.
     * Mirrors the flat-25%-of-total-paid policy in WithdrawalController@approve.
     */
    public function getPossibleRefundAttribute(): float
    {
        $totalPaid = $this->student?->ledger?->total_paid ?? 0;

        return round((float) $totalPaid * 0.25, 2);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
