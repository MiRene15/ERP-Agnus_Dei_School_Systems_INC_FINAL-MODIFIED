<?php
/**
 * Removes every row created by run_smoke.php and reverts the test payment.
 * Boots the Laravel app (writes are limited to smoke-test artifacts).
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$accPath = __DIR__ . '/accounts.json';
$statePath = __DIR__ . '/state.json';
$acc = file_exists($accPath) ? json_decode(file_get_contents($accPath), true) : [];
$state = file_exists($statePath) ? json_decode(file_get_contents($statePath), true) : [];

$deleted = [];

// 1. Principal: smoke announcement
$n = DB::table('announcements')->where('title', 'like', 'SMOKE TEST ANNOUNCEMENT%')->delete();
$deleted['announcements'] = $n;

// 2. Principal: smoke schedule row
$n = DB::table('schedules')->where('room', 'SMOKE-99')->delete();
$deleted['schedules(SMOKE-99)'] = $n;

// 3. Registrar: smoke section
$n = DB::table('sections')->where('section_name', 'like', 'SMOKEZZ%')->delete();
$deleted['sections(SMOKEZZ)'] = $n;

// 4. Nurse: smoke clinic log
$n = DB::table('clinic_logs')->where('complaint', 'SMOKE-TEST-COMPLAINT')->delete();
$deleted['clinic_logs'] = $n;

// 5. Librarian: smoke borrow transactions + smoke book
$bookIds = DB::table('books')->where('isbn', '9999-SMOKE-01')
    ->orWhere('title', 'SMOKE TEST BOOK')->pluck('id');
$n = DB::table('library_transactions')
    ->whereIn('book_id', $bookIds)
    ->orWhere('book_title', 'SMOKE TEST BOOK')
    ->delete();
$deleted['library_transactions'] = $n;
$n = DB::table('books')->whereIn('id', $bookIds)
    ->orWhere('title', 'SMOKE TEST BOOK')->delete();
$deleted['books'] = $n;

// 6. Public inquiry: pre-admission user + student
$uids = DB::table('users')->where('name', 'Smoke Tester')->pluck('id');
$n1 = DB::table('students')->whereIn('user_id', $uids)->delete();
$n2 = DB::table('users')->whereIn('id', $uids)->delete();
$deleted['inquiry_students'] = $n1;
$deleted['inquiry_users'] = $n2;

// 7. Cashier: revert test payment + restore ledger snapshot
$snapshot = $acc['payment_ledger_snapshot'] ?? null;
$maxId = $acc['payment_max_id_before'] ?? 0;
if ($snapshot && !empty($snapshot['id'])) {
    $n = DB::table('payments')
        ->where('ledger_id', $snapshot['id'])
        ->where('id', '>', (int) $maxId)
        ->delete();
    $deleted['payments(reverted)'] = $n;
    unset($snapshot['id'], $snapshot['created_at'], $snapshot['updated_at']);
    DB::table('student_ledgers')->where('id', $acc['payment_ledger_snapshot']['id'])->update($snapshot);
    $deleted['ledger(restored)'] = 1;
    // Snapshots predate auto-clearance: re-derive status from restored numbers.
    $restored = DB::table('student_ledgers')->where('id', $acc['payment_ledger_snapshot']['id'])->first();
    if ($restored) {
        DB::table('student_ledgers')->where('id', $restored->id)->update([
            'clearance_status' => round((float) $restored->balance, 2) <= 0 ? 'Cleared' : 'Uncleared',
        ]);
    }
}

// 8. API tokens issued during the run
$n = DB::table('personal_access_tokens')->where('name', 'like', 'smoke-test-%')->delete();
$deleted['api_tokens'] = $n;

echo "== cleanup report ==\n";
foreach ($deleted as $k => $v) echo sprintf("  %-26s %s row(s)\n", $k, $v);

// verify nothing smoke-related remains
$left = [];
if (DB::table('announcements')->where('title', 'like', 'SMOKE TEST%')->exists()) $left[] = 'announcement';
if (DB::table('schedules')->where('room', 'SMOKE-99')->exists()) $left[] = 'schedule';
if (DB::table('sections')->where('section_name', 'like', 'SMOKEZZ%')->exists()) $left[] = 'section';
if (DB::table('clinic_logs')->where('complaint', 'SMOKE-TEST-COMPLAINT')->exists()) $left[] = 'clinic log';
if (DB::table('books')->where('title', 'SMOKE TEST BOOK')->exists()) $left[] = 'book';
if (DB::table('users')->where('name', 'Smoke Tester')->exists()) $left[] = 'inquiry user';
echo $left ? "REMAINING: " . implode(', ', $left) . "\n" : "verified: no smoke-test rows remain\n";

// 9. sanity: key metrics unchanged
echo "counts: users=" . DB::table('users')->count()
    . " students=" . DB::table('students')->count()
    . " schedules=" . DB::table('schedules')->count()
    . " sections=" . DB::table('sections')->count()
    . " payments=" . DB::table('payments')->count()
    . " grades=" . DB::table('grades')->count() . "\n";
