<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ledger_id',
        'cashier_id',
        'amount_paid',
        'receipt_number',
        'ar_number',
        'receipt_file_path',
        'payment_date',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    public function ledger()
    {
        return $this->belongsTo(StudentLedger::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
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
