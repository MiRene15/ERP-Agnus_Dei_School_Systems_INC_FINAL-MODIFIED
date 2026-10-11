<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_year',
        'payment_plan',
        'assessment_path',
        'enrollment_fee',
        'tuition_assessed',
        'installment_count',
        'total_assessed',
        'discount_type',
        'discount_applied',
        'total_paid',
        'balance',
        'carried_over_balance',
        'clearance_status',
        'it_confirmed_at',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'ledger_id');
    }

    /**
     * Spec: cashier-fee-rules.md — assessed term/installment lines.
     * Posted lines are immutable on later fee edits.
     */
    public function ledgerLines(): HasMany
    {
        return $this->hasMany(LedgerLine::class, 'ledger_id');
    }
}
