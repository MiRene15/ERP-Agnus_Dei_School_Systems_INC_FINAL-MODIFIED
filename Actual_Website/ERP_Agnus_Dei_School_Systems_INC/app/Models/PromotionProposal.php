<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionProposal extends Model
{
    use HasFactory;

    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_PRINCIPAL_APPROVED = 'principal_approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DIRECTRESS_APPROVED = 'directress_approved';
    public const STATUS_EXECUTED = 'executed';

    public const ACTIONS = [
        'promote' => 'Promote to next grade',
        'retain' => 'Retain in same grade',
        'graduate' => 'Graduate',
        'transfer' => 'Transfer Out',
        'dropped' => 'Dropped Out',
    ];

    protected $fillable = [
        'enrollment_id',
        'action',
        'school_year',
        'reason',
        'status',
        'proposed_by',
        'principal_by',
        'principal_at',
        'directress_by',
        'directress_at',
        'executed_at',
    ];

    protected $casts = [
        'principal_at' => 'datetime',
        'directress_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_EXECUTED], true);
    }
}
