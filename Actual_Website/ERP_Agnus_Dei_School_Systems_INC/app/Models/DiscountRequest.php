<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_APPLIED = 'applied';

    public const TYPES = [
        'honor' => 'Honor',
        'sibling' => 'Sibling',
        'esc' => 'ESC Grant',
        'other' => 'Other',
    ];

    /**
     * Single quick-pick percent list shared by the request form and the
     * payment display (spec: cashier-discount-auto-apply.md). One source —
     * the two screens can never drift apart again.
     */
    public const DISCOUNT_PERCENTS = [5, 10, 20, 25, 30, 40, 50, 75, 100];

    protected $fillable = [
        'student_ledger_id',
        'discount_type',
        'discount_amount',
        'proof_details',
        'requested_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'applied_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function ledger()
    {
        return $this->belongsTo(StudentLedger::class, 'student_ledger_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
