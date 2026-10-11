<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Spec: cashier-full-payment-label.md — one assessed line per ledger.
 *
 * kind = enrollment (installment-only ₱1.5k downpayment, never discounted)
 *      | tuition (tuition slices AND misc add-on lines — books, uniform, ID,
 *        miscellaneous — each at 100%, named with its amount).
 * The tuition 10% lives INSIDE the tuition line (reduced assessment), never
 * in ledger.discount_applied. Misc lines reuse the tuition kind (no migration)
 * and are told apart by label. Posted lines are immutable: later fee edits
 * only affect new assessments.
 */
class LedgerLine extends Model
{
    use HasFactory;

    public const KIND_ENROLLMENT = 'enrollment';

    public const KIND_TUITION = 'tuition';

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'ledger_id',
        'school_year',
        'kind',
        'label',
        'term_label',
        'due_month',
        'due_date',
        'amount_assessed',
        'amount_paid',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(StudentLedger::class, 'ledger_id');
    }

    public function remaining(): float
    {
        return round((float) $this->amount_assessed - (float) $this->amount_paid, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID || $this->remaining() <= 0;
    }
}
