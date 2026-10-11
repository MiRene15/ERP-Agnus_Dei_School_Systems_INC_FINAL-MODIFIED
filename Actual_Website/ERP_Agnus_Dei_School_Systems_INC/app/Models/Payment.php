<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ledger_id',
        'cashier_id',
        'amount_paid',
        'tuition_amount',
        'library_amount',
        'receipt_number',
        'ar_number',
        'receipt_file_path',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(StudentLedger::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Spec: cashier-fee-rules.md — one shared receipt covering tuition +
     * library lines. One due, one receipt: the pivot's unique on
     * library_transaction_id blocks a second receipt for the same due.
     */
    public function libraryTransactions(): BelongsToMany
    {
        return $this->belongsToMany(LibraryTransaction::class, 'library_transaction_payment')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function generateArNumber(): string
    {
        $year = date('Y');
        $lastAr = self::where('ar_number', 'like', "AR-{$year}-%")
            ->orderByDesc('ar_number')
            ->value('ar_number');

        if ($lastAr) {
            $lastNum = (int) substr($lastAr, -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 500;
        }

        return "AR-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
