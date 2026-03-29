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
        'payment_date',
    ];

    public function ledger()
    {
        return $this->belongsTo(StudentLedger::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
