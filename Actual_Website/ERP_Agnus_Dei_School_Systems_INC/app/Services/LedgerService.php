<?php

namespace App\Services;

use App\Models\StudentLedger;

/**
 * Clearance is derived from money state, not from IT clicks (role reform).
 * Cleared = nothing owed. Anything owed = Uncleared. Called after every
 * mutation that can move the balance.
 */
class LedgerService
{
    public static function refreshClearance(StudentLedger $ledger): void
    {
        // Round to cents first: the DB stores decimal(10,2), so float dust
        // (e.g. 1.8E-12) must not keep a settled account "Uncleared".
        $balance = round((float) $ledger->balance, 2);
        $ledger->clearance_status = $balance <= 0 ? 'Cleared' : 'Uncleared';
        $ledger->save();
    }
}
