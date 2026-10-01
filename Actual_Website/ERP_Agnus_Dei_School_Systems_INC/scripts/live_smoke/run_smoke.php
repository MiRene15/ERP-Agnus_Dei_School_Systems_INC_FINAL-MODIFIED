<?php
/**
 * Live HTTP smoke test for every department of the Agnus Dei School ERP.
 * Requires a running local server (see run.bat / README) on BASE.
 *
 * Usage:  php run_smoke.php [--filter=substring] [--verbose]
 * Output: progress lines + results.json summary. Exit code 1 on failures.
 *
 * Flow: real logins (CSRF + session cookies) -> every portal route per role ->
 * AJAX/export variants -> write flows (create/read/update/delete) -> API tokens.
 * Cleanup of all created rows is done by cleanup.php afterwards.
 */

const BASE = 'http://127.0.0.1:8010';

$filter = null;
$verbose = false;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--filter=')) $filter = substr($arg, 9);
    if ($arg === '--verbose') $verbose = true;
}

$acc = json_decode(file_get_contents(__DIR__ . '/accounts.json'), true);
if (!$acc) { fwrite(STDERR, "accounts.json missing - run dump_accounts.php first\n"); exit(2); }

$BAD_MARKERS = [
    'sqlstate[', 'whoops!', 'server error', 'typeerror', 'undefined array key',
    'undefined variable', 'undefined property', 'class "', 'allowed memory size',
    'maximum execution time', '500 internal server error', 'fatal error',
    'csrf token mismatch', 'method not allowed',
];

$results = [];
$state = ['started_at' => date('c'), 'receipt_number' => null, 'payment_max_id_before' => null];

function mark(string $label): bool {
    global $filter;
    return !$filter || stripos($label, $filter) !== false;
}
function out(string $s): void { echo $s . "\n"; flush(); }

function scanMarkers(string $body): string {
    global $BAD_MARKERS;
    $hits = [];
    foreach ($BAD_MARKERS as $m) {
        if (stripos($body, $m) !== false) $hits[] = $m;
    }
    return implode(', ', $hits);
}

function rec(string $group, string $label, int $status, array $expect, int $ms, string $detail = ''): bool {
    global $results;
    $pass = in_array($status, $expect, true) && $detail === '';
    $results[] = compact('group', 'label', 'status', 'expect', 'ms', 'detail', 'pass');
    $tag = $pass ? 'PASS' : 'FAIL';
    out(sprintf('[%s] %-11s %-62s %s%s', $tag, $group, $label,
        $status . ' in ' . $ms . 'ms',
        $detail ? '  << ' . $detail : ''));
    return $pass;
}

class Http
{
    public $ch;
    private $jar;
    public ?string $lastBody = null;
    public array $lastHeaders = [];

    public function __construct()
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'smokejar');
        $this->ch = curl_init();
        curl_setopt_array($this->ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE     => '',
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_MAXREDIRS      => 0,
        ]);
    }

    public function req(string $method, string $uri, ?array $data = null, array $headers = []): array
    {
        $url = str_starts_with($uri, 'http') ? $uri : BASE . $uri;
        $method = strtoupper($method);
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) {
            $this->lastHeaders[] = trim($line);
            return strlen($line);
        });
        $this->lastHeaders = [];

        if ($method === 'GET') {
            curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        } else {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data === null ? '' : http_build_query($data));
        }
        $hdrs = array_merge(['Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8'], $headers);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $hdrs);

        $t0 = microtime(true);
        $body = curl_exec($this->ch);
        $ms = (int) round((microtime(true) - $t0) * 1000);
        $err = curl_error($this->ch);
        $code = (int) curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

        $loc = null;
        foreach ($this->lastHeaders as $h) {
            if (stripos($h, 'Location:') === 0) $loc = trim(substr($h, 9));
        }
        $this->lastBody = is_string($body) ? $body : '';
        if ($err) $this->lastBody = 'CURL ERROR: ' . $err;

        return ['status' => $code, 'ms' => $ms, 'body' => $this->lastBody, 'loc' => $loc,
                'ctype' => (string) curl_getinfo($this->ch, CURLINFO_CONTENT_TYPE)];
    }

    public function follow(array $resp): array
    {
        if ($resp['loc'] === null) return $resp;
        $loc = $resp['loc'];
        if (!str_starts_with($loc, 'http')) $loc = BASE . $loc;
        return $this->req('GET', $loc);
    }

    public function token(): ?string
    {
        $body = $this->lastBody ?? '';
        if (preg_match('/name="_token"\s+value="([^"]+)"/', $body, $m)) return $m[1];
        if (preg_match('/name=\'_token\'\s+value=\'([^\']+)\'/', $body, $m)) return $m[1];
        return null;
    }

    public function __destruct()
    {
        @curl_close($this->ch);
        @unlink($this->jar);
    }
}

/** GET a page and return the response (also refreshes the cached CSRF token). */
function page(Http $h, string $uri): array
{
    $r = $h->req('GET', $uri);
    return $r;
}

/** Run one recorded test: GET/POST with expectations. */
function test(Http $h, string $group, string $method, string $uri, array $opt = []): array
{
    global $verbose;
    $expect = $opt['x'] ?? [200];
    $data = $opt['data'] ?? null;
    $r = $h->req($method, $uri, $data);
    $detail = '';

    if (!in_array($r['status'], $expect, true)) {
        $detail = 'expected ' . implode('/', $expect);
        $mk = scanMarkers($r['body']);
        if ($mk) $detail .= '; markers: ' . $mk;
        elseif ($r['status'] >= 300 && $r['status'] < 400) $detail .= '; redirected to ' . ($r['loc'] ?? '?');
        else $detail .= '; ' . substr(preg_replace('/\s+/', ' ', strip_tags($r['body'])), 0, 160);
    } else {
        $mk = scanMarkers($r['body']);
        if ($mk) $detail = 'error markers: ' . $mk;
        if (($opt['contains'] ?? null) !== null && stripos($r['body'], $opt['contains']) === false) {
            $detail = ($detail ? $detail . '; ' : '') . 'missing "' . $opt['contains'] . '"';
        }
        if (($opt['not_contains'] ?? null) !== null && stripos($r['body'], $opt['not_contains']) !== false) {
            $detail = ($detail ? $detail . '; ' : '') . 'unexpected "' . $opt['not_contains'] . '"';
        }
        if (($opt['ctype_contains'] ?? null) !== null && stripos($r['ctype'], $opt['ctype_contains']) === false) {
            $detail = ($detail ? $detail . '; ' : '') . 'ctype ' . $r['ctype'] . ' lacks ' . $opt['ctype_contains'];
        }
    }
    $ok = rec($group, $method . ' ' . $uri, $r['status'], $expect, $r['ms'], $detail);
    if ($verbose && !$ok) out('        body: ' . substr(preg_replace('/\s+/', ' ', strip_tags($r['body'])), 0, 400));
    return $r;
}

/** Follow a redirect response and record the landing page. */
function testFollow(Http $h, string $group, string $label, array $r, array $opt = []): array
{
    $expect = $opt['x'] ?? [200];
    $f = $h->follow($r);
    $detail = '';
    if (!in_array($f['status'], $expect, true)) $detail = 'landed ' . $f['status'] . ' expected ' . implode('/', $expect);
    $mk = scanMarkers($f['body']);
    if (!$detail && $mk) $detail = 'error markers: ' . $mk;
    if (($opt['contains'] ?? null) !== null && stripos($f['body'], $opt['contains']) === false) {
        $detail = ($detail ? $detail . '; ' : '') . 'missing "' . $opt['contains'] . '"';
    }
    rec($group, $label, $f['status'], $expect, $f['ms'], $detail);
    return $f;
}

function extractIdNear(string $body, string $needle, string $pattern): ?int
{
    // ajax responses are JSON — unescape \" and \/ so href patterns match
    $flat = str_replace(['\\/', '\\"'], ['/', '"'], $body);
    $pos = stripos($flat, $needle);
    if ($pos === false) return null;
    // look forward from the needle first (row: label ... action link), then around it
    $forward = substr($flat, $pos, 2500);
    if (preg_match($pattern, $forward, $m)) return (int) $m[1];
    $window = substr($flat, max(0, $pos - 800), 3300);
    if (preg_match($pattern, $window, $m)) return (int) $m[1];
    return null;
}

/** Real browser-style login. Returns [status, location]. */
function login(Http $h, string $email, string $pass): array
{
    $r = $h->req('GET', '/login');
    $tok = $h->token();
    if (!$tok) return [$r['status'], 'no-csrf'];
    $r2 = $h->req('POST', '/login', ['email' => $email, 'password' => $pass, '_token' => $tok]);
    return [$r2['status'], $r2['loc'] ?? ''];
}

function apiToken(string $email, string $pass, string $device): ?string
{
    $h = new Http();
    $r = $h->req('POST', '/api/auth/token', ['email' => $email, 'password' => $pass, 'device_name' => $device]);
    $j = json_decode($r['body'], true);
    return $j['data']['token'] ?? $j['token'] ?? null;
}

out('== Agnus Dei ERP live smoke test ==  base: ' . BASE . '  started: ' . date('c'));

/* ────────────────────────────── A. PUBLIC ─────────────────────────────── */
$public = new Http();
if (mark('public')) {
    out('-- Public site --');
    foreach (['/', '/vision', '/mission', '/academics', '/admissions', '/identity',
              '/educational-philosophy', '/institutional-background', '/contact-information',
              '/program-offerings', '/requirements-procedures', '/discounts-privileges',
              '/inquiry', '/login', '/forgot-password'] as $u) {
        test($public, 'Public', 'GET', $u);
    }
    // Inquiry write (creates pre-admission user + student + email; cleaned up)
    $r = $public->req('GET', '/inquiry');
    $tok = $public->token();
    $r = $public->req('POST', '/inquiry', [
        'first_name' => 'Smoke', 'last_name' => 'Tester',
        'personal_email' => 'smoke.tester@gmail.com', '_token' => $tok,
    ]);
    $f = testFollow($public, 'Public', 'POST /inquiry (create pre-admission account)', $r,
        ['x' => [200], 'contains' => 'success-modal']);
    rec('Public', 'inquiry 302 redirect', $r['status'], [302], $r['ms'],
        $r['status'] === 302 ? '' : 'expected 302');
    if (stripos($f['body'], 'Error:') !== false) {
        rec('Public', 'inquiry no-error flash', 500, [200], 0, 'flash error shown: ' . substr(strip_tags($f['body']), 0, 200));
    } else {
        rec('Public', 'inquiry no-error flash', 200, [200], 0);
    }
}

/* ──────────────────────────── B. LOGINS (8) ───────────────────────────── */
$accounts = [
    'admin'      => $acc['staff']['admin'],
    'registrar'  => $acc['staff']['registrar'],
    'cashier'    => $acc['staff']['cashier'],
    'teacher'    => $acc['teacher']['email'],
    'librarian'  => $acc['staff']['librarian'],
    'nurse'      => $acc['staff']['nurse'],
    'student'    => $acc['student']['email'],
    'directress' => $acc['staff']['directress'],
    'principal'  => $acc['staff']['principal'],
];
$pass = $acc['password'];
$clients = [];
if (mark('login')) {
    out('-- Logins --');
    foreach ($accounts as $role => $email) {
        $h = new Http();
        [$st, $loc] = login($h, $email, $pass);
        $ok = ($st === 302 && str_contains((string) $loc, '/dashboard'));
        rec('Login', $role . ' (' . $email . ')', $ok ? 302 : $st, [302], 0,
            $ok ? '' : 'login response ' . $st . ' -> ' . $loc);
        if ($ok) {
            $clients[$role] = $h;
            $r = $h->req('GET', '/dashboard');
            rec('Login', $role . ' /dashboard role-redirect', $r['status'], [302], $r['ms'],
                $r['status'] === 302 ? '' : 'expected 302');
        }
    }
}
$skip = fn(string $role) => !isset($clients[$role]);

/* ───────────────────────────── C. ADMIN ──────────────────────────────── */
if (!$skip('admin') && mark('admin')) {
    out('-- Admin --');
    $h = $clients['admin'];
    test($h, 'Admin', 'GET', '/admin/dashboard');
    // Role reform: IT no longer touches money or academic decisions — assert gone.
    test($h, 'Admin', 'GET', '/admin/pending-accounts', ['x' => [404]]);
    test($h, 'Admin', 'GET', '/admin/promotion', ['x' => [404]]);
    test($h, 'Admin', 'GET', '/admin/subjects', ['x' => [404]]);
    test($h, 'Admin', 'GET', '/admin/users');
    test($h, 'Admin', 'GET', '/admin/users/create');
    if ($acc['admin_edit_user_id']) test($h, 'Admin', 'GET', '/admin/users/' . $acc['admin_edit_user_id'] . '/edit');
    test($h, 'Admin', 'GET', '/admin/student-accounts');
    test($h, 'Admin', 'GET', '/admin/settings');
    test($h, 'Admin', 'GET', '/admin/audit-logs');
    foreach (['enrollments', 'grades', 'collections'] as $ex) {
        test($h, 'Admin', 'GET', '/admin/exports/' . $ex,
            ['ctype_contains' => 'text/csv', 'not_contains' => '<html']);
    }
    test($h, 'Admin', 'GET', '/profile');
}

/* ─────────────────────────── D. REGISTRAR ────────────────────────────── */
if (!$skip('registrar') && mark('registrar')) {
    out('-- Registrar --');
    $h = $clients['registrar'];
    test($h, 'Registrar', 'GET', '/registrar/dashboard');
    test($h, 'Registrar', 'GET', '/registrar/admissions');
    if ($acc['admission_id']) test($h, 'Registrar', 'GET', '/registrar/admissions/' . $acc['admission_id']);
    test($h, 'Registrar', 'GET', '/registrar/withdrawals');
    test($h, 'Registrar', 'GET', '/registrar/report-cards');
    if ($acc['student']['enrollment_id']) {
        test($h, 'Registrar', 'GET', '/registrar/report-cards/' . $acc['student']['enrollment_id']);
        test($h, 'Registrar', 'GET', '/registrar/report-cards/' . $acc['student']['enrollment_id'] . '/print');
    }
    test($h, 'Registrar', 'GET', '/registrar/sections');
    test($h, 'Registrar', 'GET', '/registrar/sections/create');
    if ($acc['section_id']) test($h, 'Registrar', 'GET', '/registrar/sections/' . $acc['section_id'] . '/edit');
    // Role reform: Registrar owns subjects + prepares promotion proposals.
    test($h, 'Registrar', 'GET', '/registrar/subjects');
    test($h, 'Registrar', 'GET', '/registrar/subjects/create');
    if ($acc['subject_id']) test($h, 'Registrar', 'GET', '/registrar/subjects/' . $acc['subject_id'] . '/edit');
    test($h, 'Registrar', 'GET', '/registrar/promotion');
    test($h, 'Registrar', 'GET', '/registrar/grade-unlocks');
    test($h, 'Registrar', 'GET', '/registrar/fee-assignment');
    test($h, 'Registrar', 'GET', '/discount-requests');
    if ($acc['requirement_with_content_id']) {
        test($h, 'Registrar', 'GET', '/registrar/requirements/' . $acc['requirement_with_content_id'] . '/view');
    } else {
        rec('Registrar', 'GET requirements/{id}/view (skipped: no seeded file_content)', 200, [200], 0);
    }

    // write: temp section create -> duplicate guard -> edit -> delete
    $h->req('GET', '/registrar/sections/create');
    $tok = $h->token();
    $r = $h->req('POST', '/registrar/sections', [
        'grade_level' => 'Grade 1', 'section_name' => 'SMOKEZZ', '_token' => $tok,
    ]);
    $f = testFollow($h, 'Registrar', 'POST sections create SMOKEZZ', $r, ['x' => [200], 'contains' => 'created for']);
    $r = $h->req('POST', '/registrar/sections', [
        'grade_level' => 'Grade 1', 'section_name' => 'SMOKEZZ', '_token' => $tok,
    ]);
    $f2 = testFollow($h, 'Registrar', 'POST sections duplicate rejected', $r, ['x' => [200], 'contains' => 'already exists']);
    $secPage = $h->req('GET', '/registrar/sections?ajax=1');
    $secId = extractIdNear($secPage['body'], 'SMOKEZZ', '#\/sections\/(\d+)\/edit#');
    if ($secId) {
        $h->req('GET', '/registrar/sections/' . $secId . '/edit');
        $tok = $h->token();
        $r = $h->req('POST', '/registrar/sections/' . $secId, [
            'grade_level' => 'Grade 1', 'section_name' => 'SMOKEZZ', '_token' => $tok, '_method' => 'PATCH',
        ]);
        testFollow($h, 'Registrar', 'PATCH section SMOKEZZ', $r, ['x' => [200], 'contains' => 'updated']);
        $r = $h->req('POST', '/registrar/sections/' . $secId, ['_token' => $tok, '_method' => 'DELETE']);
        testFollow($h, 'Registrar', 'DELETE section SMOKEZZ', $r, ['x' => [200], 'contains' => 'deleted']);
    } else {
        rec('Registrar', 'locate SMOKEZZ for edit/delete', 404, [200], 0, 'id not found on sections page');
    }
}

/* ──────────────────────────── E. CASHIER ─────────────────────────────── */
if (!$skip('cashier') && mark('cashier')) {
    out('-- Cashier --');
    $h = $clients['cashier'];
    $sid = $acc['student']['id'];
    test($h, 'Cashier', 'GET', '/cashier/dashboard');
    test($h, 'Cashier', 'GET', '/cashier/payments');
    test($h, 'Cashier', 'GET', '/cashier/search?q=Juan');
    if ($sid) {
        test($h, 'Cashier', 'GET', '/cashier/payment/' . $sid);
        test($h, 'Cashier', 'GET', '/cashier/financial/' . $sid);
    }
    if ($acc['payment_id']) test($h, 'Cashier', 'GET', '/cashier/receipt/' . $acc['payment_id']);
    test($h, 'Cashier', 'GET', '/cashier/collections');
    test($h, 'Cashier', 'GET', '/cashier/collections/export', ['not_contains' => '<html']);
    test($h, 'Cashier', 'GET', '/cashier/reports');
    test($h, 'Cashier', 'GET', '/cashier/reports/receivables');
    test($h, 'Cashier', 'GET', '/cashier/reports/receivables/export', ['not_contains' => '<html']);
    test($h, 'Cashier', 'GET', '/cashier/discounts');
    test($h, 'Cashier', 'GET', '/discount-requests');
    test($h, 'Cashier', 'GET', '/cashier/refunds');
    // Role reform: Cashier can no longer view admission documents.
    if ($acc['requirement_with_content_id']) {
        test($h, 'Cashier', 'GET', '/registrar/requirements/' . $acc['requirement_with_content_id'] . '/view', ['x' => [403]]);
    }

    // write: process a real 100 payment (reverted by cleanup.php from snapshot)
    if ($sid) {
        $pre = $h->req('GET', '/cashier/payment/' . $sid);
        $tok = $h->token();
        $r = $h->req('POST', '/cashier/payment/' . $sid . '/process', [
            'payment_plan' => 'installment', 'amount_paid' => '100', '_token' => $tok,
        ]);
        $f = testFollow($h, 'Cashier', 'POST process payment 100 (reverted later)', $r, ['x' => [200]]);
        if (preg_match('/RCP-\d{8}-\d{4}/', $f['body'], $m)) {
            $state['receipt_number'] = $m[0];
            rec('Cashier', 'payment created with receipt ' . $m[0], 200, [200], $f['ms']);
        } else {
            rec('Cashier', 'payment receipt visible after process', 0, [200], 0, 'no RCP- receipt in redirect page');
        }
    }
}

/* ──────────────────────────── F. TEACHER ─────────────────────────────── */
if (!$skip('teacher') && mark('teacher')) {
    out('-- Teacher --');
    $h = $clients['teacher'];
    $cid = $acc['teacher_class_id'];
    test($h, 'Teacher', 'GET', '/teacher/dashboard');
    test($h, 'Teacher', 'GET', '/teacher/classes');
    if ($cid) {
        test($h, 'Teacher', 'GET', '/teacher/classes/' . $cid);
        test($h, 'Teacher', 'GET', '/teacher/classes/' . $cid . '/assessments');
        test($h, 'Teacher', 'GET', '/teacher/class-list/' . $cid . '/students');
        if ($acc['teacher_class_enrollment_id']) {
            test($h, 'Teacher', 'GET', '/teacher/grade-assessment/' . $cid . '/student/' . $acc['teacher_class_enrollment_id']);
        }
    }
    test($h, 'Teacher', 'GET', '/teacher/schedule');
    test($h, 'Teacher', 'GET', '/teacher/class-list');
    test($h, 'Teacher', 'GET', '/teacher/grade-assessment');
    test($h, 'Teacher', 'GET', '/teacher/computed-grades');
    test($h, 'Teacher', 'GET', '/teacher/grade-unlocks');
    if ($cid) {
        test($h, 'Teacher', 'GET', '/teacher/classes/' . $cid . '/attendance');
    }
    test($h, 'Teacher', 'GET', '/teacher/dashboard?ajax=1', ['contains' => '"html"']);
    test($h, 'Teacher', 'GET', '/teacher/classes?ajax=1', ['contains' => '"html"']);
    test($h, 'Teacher', 'GET', '/teacher/class-list?ajax=1', ['contains' => '"html"']);
}

/* ─────────────────────────── G. LIBRARIAN ────────────────────────────── */
if (!$skip('librarian') && mark('librarian')) {
    out('-- Librarian --');
    $h = $clients['librarian'];
    test($h, 'Librarian', 'GET', '/librarian/dashboard');
    test($h, 'Librarian', 'GET', '/librarian/books');
    test($h, 'Librarian', 'GET', '/librarian/books/create');
    if ($acc['book_edit_id']) test($h, 'Librarian', 'GET', '/librarian/books/' . $acc['book_edit_id'] . '/edit');
    test($h, 'Librarian', 'GET', '/librarian/inactive-logs');
    test($h, 'Librarian', 'GET', '/librarian/loans');
    test($h, 'Librarian', 'GET', '/librarian/loans/borrow');
    if ($acc['txn_id']) test($h, 'Librarian', 'GET', '/librarian/loans/' . $acc['txn_id'] . '/return');
    test($h, 'Librarian', 'GET', '/librarian/students/search?q=Juan');
    test($h, 'Librarian', 'GET', '/librarian/books/search?q=a');
    test($h, 'Librarian', 'GET', '/librarian/loans/search?q=a');
    test($h, 'Librarian', 'GET', '/librarian/visits');
    test($h, 'Librarian', 'GET', '/librarian/history');

    // write: book CRUD
    $h->req('GET', '/librarian/books/create');
    $tok = $h->token();
    $r = $h->req('POST', '/librarian/books', [
        'title' => 'SMOKE TEST BOOK', 'author' => 'QA Bot', 'isbn' => '9999-SMOKE-01',
        'quantity' => '2', 'price' => '10', '_token' => $tok,
    ]);
    testFollow($h, 'Librarian', 'POST books create SMOKE TEST BOOK', $r, ['x' => [200], 'contains' => 'added successfully']);
    $bp = $h->req('GET', '/librarian/books/search?search=SMOKE');
    $bookId = null;
    $bj = json_decode($bp['body'], true);
    foreach (($bj['data'] ?? []) as $row) {
        if (($row['title'] ?? '') === 'SMOKE TEST BOOK') { $bookId = $row['id']; break; }
    }
    if ($bookId) {
        $h->req('GET', '/librarian/books/' . $bookId . '/edit');
        $tok = $h->token();
        $r = $h->req('POST', '/librarian/books/' . $bookId, [
            'title' => 'SMOKE TEST BOOK', 'author' => 'QA Bot II', 'isbn' => '9999-SMOKE-01',
            'quantity' => '2', 'price' => '10', '_token' => $tok, '_method' => 'PATCH',
        ]);
        testFollow($h, 'Librarian', 'PATCH book', $r, ['x' => [200], 'contains' => 'updated']);

        // write: borrow -> verify listed -> return
        $borrowStudent = $acc['borrow_student_id'] ?? $acc['student']['id'];
        $h->req('GET', '/librarian/loans/borrow');
        $tok = $h->token();
        $r = $h->req('POST', '/librarian/loans/borrow', [
            'student_id' => $borrowStudent, 'book_id' => $bookId,
            'borrow_date' => date('Y-m-d'), 'return_date' => date('Y-m-d', strtotime('+7 days')),
            'condition_at_borrow' => 'Good', '_token' => $tok,
        ]);
        $f = testFollow($h, 'Librarian', 'POST borrow SMOKE TEST BOOK', $r, ['x' => [200]]);
        $loans = $h->req('GET', '/librarian/loans/search?search=SMOKE');
        $txnId = null;
        $lj = json_decode($loans['body'], true);
        foreach (($lj['data'] ?? []) as $row) {
            if (($row['book_title'] ?? '') === 'SMOKE TEST BOOK') { $txnId = $row['id']; break; }
        }
        rec('Librarian', 'borrowed book listed via loans search',
            $txnId ? 200 : 404, [200], $loans['ms'],
            $txnId ? '' : 'SMOKE TEST BOOK not in /librarian/loans/search?search=SMOKE');
        if ($txnId) {
            $h->req('GET', '/librarian/loans/' . $txnId . '/return');
            $tok = $h->token();
            $r = $h->req('POST', '/librarian/loans/' . $txnId . '/return', [
                'condition_at_return' => 'Good', '_token' => $tok, '_method' => 'PATCH',
            ]);
            testFollow($h, 'Librarian', 'PATCH return book', $r, ['x' => [200]]);
        } else {
            rec('Librarian', 'locate new transaction for return', 404, [200], 0, 'txn id not found');
        }
    } else {
        rec('Librarian', 'locate SMOKE TEST BOOK for edit/borrow', 404, [200], 0, 'book id not found');
    }
}

/* ───────────────────────────── H. NURSE ──────────────────────────────── */
if (!$skip('nurse') && mark('nurse')) {
    out('-- Nurse --');
    $h = $clients['nurse'];
    test($h, 'Nurse', 'GET', '/nurse/dashboard');
    test($h, 'Nurse', 'GET', '/nurse/logs');
    test($h, 'Nurse', 'GET', '/nurse/logs/create');
    if ($acc['student']['id']) {
        $h->req('GET', '/nurse/logs/create');
        $tok = $h->token();
        $r = $h->req('POST', '/nurse/logs', [
            'student_id' => $acc['student']['id'],
            'incident_date' => date('Y-m-d') . ' 14:00',
            'complaint' => 'SMOKE-TEST-COMPLAINT', 'treatment' => 'None (smoke test)',
            '_token' => $tok,
        ]);
        testFollow($h, 'Nurse', 'POST clinic log SMOKE-TEST-COMPLAINT', $r, ['x' => [200]]);
    }
}

/* ──────────────────────────── I. STUDENT ─────────────────────────────── */
if (!$skip('student') && mark('student')) {
    out('-- Student --');
    $h = $clients['student'];
    test($h, 'Student', 'GET', '/student/dashboard');
    // designed guards: student already has a student number / active enrollment
    $r = $h->req('GET', '/student/admission/apply');
    rec('Student', 'GET /student/admission/apply (guard redirect)', $r['status'], [302], $r['ms'],
        $r['status'] === 302 && str_contains((string) $r['loc'], '/student/')
            ? '' : 'expected designed 302 to student area, got ' . $r['status']);
    $r = $h->req('GET', '/student/enrollment/apply');
    rec('Student', 'GET /student/enrollment/apply (guard redirect)', $r['status'], [302], $r['ms'],
        $r['status'] === 302 && str_contains((string) $r['loc'], '/student/')
            ? '' : 'expected designed 302 to student area, got ' . $r['status']);
    test($h, 'Student', 'GET', '/student/admission/status');
    test($h, 'Student', 'GET', '/student/withdrawal');
    // Role reform: holds block report cards. Juan currently carries an overdue
    // library hold, so expect the block + hold reasons on the dashboard. If his
    // holds ever clear, the plain 200 path below covers the open view.
    $r = $h->req('GET', '/student/report-card');
    if ($r['status'] === 302) {
        rec('Student', 'GET /student/report-card blocked by holds', $r['status'], [302], $r['ms']);
        testFollow($h, 'Student', 'report-card block explains holds on dashboard', $r, ['x' => [200], 'contains' => 'Report card withheld']);
    } else {
        $detail = scanMarkers($r['body']);
        rec('Student', 'GET /student/report-card (no holds)', $r['status'], [200], $r['ms'], $detail);
    }
    test($h, 'Student', 'GET', '/student/cor');
    test($h, 'Student', 'GET', '/student/schedule');
    test($h, 'Student', 'GET', '/student/ledger');
    if ($acc['student_requirement_with_content_id']) {
        test($h, 'Student', 'GET', '/student/admission/requirements/' . $acc['student_requirement_with_content_id'] . '/view');
    } else {
        rec('Student', 'GET requirements/{id}/view (skipped: no seeded file_content)', 200, [200], 0);
    }
}

/* ────────────────────────── J. DIRECTRESS ────────────────────────────── */
if (!$skip('directress') && mark('directress')) {
    out('-- Directress --');
    $h = $clients['directress'];
    test($h, 'Directress', 'GET', '/directress/dashboard');
    test($h, 'Directress', 'GET', '/directress/demographics');
    test($h, 'Directress', 'GET', '/directress/school-years');
    test($h, 'Directress', 'GET', '/directress/fees');
    test($h, 'Directress', 'GET', '/directress/fees/create');
    if ($acc['fee_id']) test($h, 'Directress', 'GET', '/directress/fees/' . $acc['fee_id'] . '/edit');
    test($h, 'Directress', 'GET', '/directress/graduation-fees');
    test($h, 'Directress', 'GET', '/directress/graduation-fees/create');
    if ($acc['grad_fee_id']) {
        test($h, 'Directress', 'GET', '/directress/graduation-fees/' . $acc['grad_fee_id'] . '/edit');
        test($h, 'Directress', 'GET', '/directress/graduation-fees/' . $acc['grad_fee_id'] . '/assigned');
        // Role reform: one-by-one assignment removed (Registrar assigns in bulk).
        test($h, 'Directress', 'GET', '/directress/graduation-fees/' . $acc['grad_fee_id'] . '/assign', ['x' => [404]]);
    }
    // Role reform: Directress approves discounts, signs off promotion, acknowledges announcements.
    test($h, 'Directress', 'GET', '/directress/discount-requests');
    test($h, 'Directress', 'GET', '/directress/promotion');
    test($h, 'Directress', 'GET', '/directress/announcements');
    test($h, 'Directress', 'GET', '/directress/reports');
    foreach (['collections', 'receivables', 'clinic', 'library', 'students'] as $tab) {
        test($h, 'Directress', 'GET', '/directress/reports?tab=' . $tab);
    }
    test($h, 'Directress', 'GET', '/directress/reports/receivables/export', ['not_contains' => '<html']);
    test($h, 'Directress', 'GET', '/directress/reports/clinic/export', ['not_contains' => '<html']);
    test($h, 'Directress', 'GET', '/directress/reports/students/export', ['not_contains' => '<html']);
    test($h, 'Directress', 'GET', '/directress/library-reports/export', ['not_contains' => '<html']);
    test($h, 'Directress', 'GET', '/directress/cashier-reports/export', ['not_contains' => '<html']);
    $r = $h->req('GET', '/directress/library-reports');
    rec('Directress', 'GET /directress/library-reports legacy redirect', $r['status'], [302], $r['ms'],
        $r['status'] === 302 ? '' : 'expected 302');
    $r = $h->req('GET', '/directress/cashier-reports');
    rec('Directress', 'GET /directress/cashier-reports legacy redirect', $r['status'], [302], $r['ms'],
        $r['status'] === 302 ? '' : 'expected 302');
}

/* ────────────────────────── K. PRINCIPAL ─────────────────────────────── */
$principalToken = null;
if (!$skip('principal') && mark('principal')) {
    out('-- Principal --');
    $h = $clients['principal'];
    test($h, 'Principal', 'GET', '/principal/dashboard');
    test($h, 'Principal', 'GET', '/principal/schedules');
    test($h, 'Principal', 'GET', '/principal/schedules/manage');
    if ($acc['schedule_id']) test($h, 'Principal', 'GET', '/principal/schedules/' . $acc['schedule_id'] . '/edit');
    test($h, 'Principal', 'GET', '/principal/schedules/template', ['not_contains' => '<html']);
    test($h, 'Principal', 'GET', '/principal/announcements');
    test($h, 'Principal', 'GET', '/principal/announcements/create');
    // Role reform: Principal approves promotion, oversees subjects, reviews grade unlocks.
    test($h, 'Principal', 'GET', '/principal/promotion');
    test($h, 'Principal', 'GET', '/principal/subjects');
    test($h, 'Principal', 'GET', '/principal/subject-approvals');
    test($h, 'Principal', 'GET', '/principal/teacher-assignments');
    test($h, 'Principal', 'GET', '/registrar/grade-unlocks');
    if ($acc['announcement_id']) {
        test($h, 'Principal', 'GET', '/principal/announcements/' . $acc['announcement_id'] . '/edit');
    }

    // regression: every grade tab renders (filter persistence / no crash)
    foreach (['Kinder', 'Grade 1', 'Grade 5', 'Grade 7', 'Grade 10', 'Grade 11', 'Grade 12'] as $g) {
        test($h, 'Principal', 'GET', '/principal/grades?grade_level=' . urlencode($g));
    }
    $r = test($h, 'Principal', 'GET', '/principal/schedules?grade_level=Grade%2012&ajax=1', ['contains' => '"html"']);
    rec('Principal', 'ajax schedules Grade 12 rows include STEM-A',
        stripos($r['body'], 'STEM-A') !== false ? 200 : 404, [200], $r['ms'],
        stripos($r['body'], 'STEM-A') !== false ? '' : 'STEM-A missing from Grade 12 ajax results');
    $r = test($h, 'Principal', 'GET', '/principal/grades?grade_level=Grade%2012&ajax=1', ['contains' => '"html"']);

    // writes: announcements CRUD
    $h->req('GET', '/principal/announcements/create');
    $tok = $h->token();
    $r = $h->req('POST', '/principal/announcements', [
        'title' => 'SMOKE TEST ANNOUNCEMENT', 'content' => 'Automated live smoke test announcement.',
        'type' => 'announcement', 'date' => date('Y-m-d H:i'), 'is_published' => '1', '_token' => $tok,
    ]);
    testFollow($h, 'Principal', 'POST announcement create', $r, ['x' => [200], 'contains' => 'created successfully']);
    $ap = $h->req('GET', '/principal/announcements?ajax=1');
    $annId = extractIdNear($ap['body'], 'SMOKE TEST ANNOUNCEMENT', '#\/announcements\/(\d+)\/edit#');
    if ($annId) {
        $h->req('GET', '/principal/announcements/' . $annId . '/edit');
        $tok = $h->token();
        $r = $h->req('POST', '/principal/announcements/' . $annId, [
            'title' => 'SMOKE TEST ANNOUNCEMENT', 'content' => 'Updated by smoke test.',
            'type' => 'announcement', 'date' => date('Y-m-d H:i'), 'is_published' => '1',
            '_token' => $tok, '_method' => 'PATCH',
        ]);
        testFollow($h, 'Principal', 'PATCH announcement', $r, ['x' => [200], 'contains' => 'updated']);
        $r = $h->req('POST', '/principal/announcements/' . $annId, ['_token' => $tok, '_method' => 'DELETE']);
        testFollow($h, 'Principal', 'DELETE announcement', $r, ['x' => [200]]);
    } else {
        rec('Principal', 'locate announcement for edit/delete', 404, [200], 0, 'announcement id not found');
    }

    // writes: schedule conflict matrix (session-53 section-level fix)
    $ex = $acc['existing_schedule'];
    if ($ex) {
        $h->req('GET', '/principal/schedules'); // set back() target
        $tok = $h->token();
        // 1) same class duplicate
        $r = $h->req('POST', '/principal/schedules', [
            'class_id' => $ex['class_id'], 'day_of_week' => $ex['day'],
            'start_time' => $ex['start'], 'end_time' => $ex['end'], 'room' => $ex['room'],
            '_token' => $tok,
        ]);
        testFollow($h, 'Principal', 'schedule CONFLICT same-class rejected', $r,
            ['x' => [200], 'contains' => 'conflicts with an existing schedule for this class']);
        // 2) section-level overlap (different class, same grade+section) - session-53 fix
        if ($acc['section_conflict_class_id']) {
            $r = $h->req('POST', '/principal/schedules', [
                'class_id' => $acc['section_conflict_class_id'], 'day_of_week' => $ex['day'],
                'start_time' => $ex['start'], 'end_time' => $ex['end'], 'room' => 'SMOKE-R9',
                '_token' => $tok,
            ]);
            testFollow($h, 'Principal', 'schedule CONFLICT section-level rejected (s53 fix)', $r,
                ['x' => [200], 'contains' => 'already has a class at this time']);
        }
        // 3) room conflict
        if ($acc['any_active_class_id']) {
            $r = $h->req('POST', '/principal/schedules', [
                'class_id' => $acc['any_active_class_id'], 'day_of_week' => $ex['day'],
                'start_time' => $ex['start'], 'end_time' => $ex['end'], 'room' => $ex['room'],
                '_token' => $tok,
            ]);
            $f = $h->follow($r);
            $okConflict = stripos($f['body'], 'already booked at this time') !== false
                || stripos($f['body'], 'conflicts with an existing schedule') !== false
                || stripos($f['body'], 'already has a class at this time') !== false
                || stripos($f['body'], 'Teacher is already booked') !== false;
            rec('Principal', 'schedule CONFLICT room/other rejected', $okConflict ? 200 : 0, [200], $f['ms'],
                $okConflict ? '' : 'no conflict message: ' . substr(strip_tags($f['body']), 0, 200));
        }
        // 4) valid create at a free Friday evening slot
        $r = $h->req('POST', '/principal/schedules', [
            'class_id' => $acc['any_active_class_id'], 'day_of_week' => 'Friday',
            'start_time' => '19:00', 'end_time' => '19:30', 'room' => 'SMOKE-99',
            '_token' => $tok,
        ]);
        $f = testFollow($h, 'Principal', 'schedule VALID create Friday 19:00 SMOKE-99', $r,
            ['x' => [200], 'contains' => 'Schedule added.']);

        // find its id via API (principal token), then edit-page + PATCH + DELETE over HTTP
        $principalToken = apiToken($acc['staff']['principal'], $pass, 'smoke-test-principal');
        if ($principalToken) {
            $api = new Http();
            $smokeId = null;
            for ($pg = 1; $pg <= 15; $pg++) {
                $rj = $api->req('GET', '/api/principal/schedules?page=' . $pg, null,
                    ['Authorization: Bearer ' . $principalToken, 'Accept: application/json']);
                $j = json_decode($rj['body'], true);
                $rows = $j['data']['data'] ?? null;
                if (!$rows) break;
                foreach ($rows as $row) {
                    if (($row['room'] ?? null) === 'SMOKE-99') { $smokeId = $row['id'] ?? null; break 2; }
                }
                $last = $j['data']['last_page'] ?? null;
                if ($last && $pg >= (int) $last) break;
            }
            if ($smokeId) {
                $h->req('GET', '/principal/schedules/' . $smokeId . '/edit');
                $tok = $h->token();
                $r = $h->req('POST', '/principal/schedules/' . $smokeId, [
                    'day_of_week' => 'Friday', 'start_time' => '19:00', 'end_time' => '19:45',
                    'room' => 'SMOKE-99', '_token' => $tok, '_method' => 'PATCH',
                ]);
                $f = testFollow($h, 'Principal', 'PATCH schedule (smoke row)', $r, ['x' => [200]]);
                $r = $h->req('POST', '/principal/schedules/' . $smokeId, ['_token' => $tok, '_method' => 'DELETE']);
                testFollow($h, 'Principal', 'DELETE schedule (smoke row)', $r, ['x' => [200]]);
            } else {
                rec('Principal', 'locate smoke schedule via API', 404, [200], 0, 'SMOKE-99 row not in /api/principal/schedules');
            }
        } else {
            rec('Principal', 'issue API token for schedule id lookup', 401, [200], 0, 'no token');
        }
    } else {
        rec('Principal', 'existing schedule data', 404, [200], 0, 'no existing_schedule in accounts.json');
    }
}

/* ───────────────────────────── L. API ────────────────────────────────── */
if (mark('api')) {
    out('-- API (Sanctum tokens) --');
    $apiEndpoints = [
        'admin'      => ['/api/me', '/api/admin/users', '/api/admin/users/{A}', '/api/admin/activity-logs'],
        'registrar'  => ['/api/me', '/api/registrar/admissions', '/api/registrar/admissions/{A}', '/api/registrar/students'],
        'cashier'    => ['/api/me', '/api/cashier/payments', '/api/cashier/ledgers'],
        'teacher'    => ['/api/me', '/api/teacher/classes', '/api/teacher/classes/{TC}', '/api/teacher/classes/{TC}/grades'],
        'librarian'  => ['/api/me', '/api/librarian/books', '/api/librarian/loans'],
        'nurse'      => ['/api/me', '/api/nurse/clinic-logs'],
        'student'    => ['/api/me', '/api/student/me', '/api/student/admission', '/api/student/enrollments', '/api/student/grades', '/api/student/ledger'],
        'directress' => ['/api/me', '/api/directress/fees', '/api/directress/collections'],
        'principal'  => ['/api/me', '/api/principal/schedules', '/api/principal/grades', '/api/principal/announcements'],
    ];
    foreach ($apiEndpoints as $role => $endpoints) {
        if (!isset($accounts[$role])) continue;
        $token = $role === 'principal' && $principalToken ? $principalToken
            : apiToken($accounts[$role], $pass, 'smoke-test-' . $role);
        if (!$token) {
            rec('API', $role . ' token issue', 401, [200], 0, 'no token from /api/auth/token');
            continue;
        }
        rec('API', $role . ' token issue', 200, [200], 0);
        $hh = new Http();
        foreach ($endpoints as $ep) {
            $ep = str_replace(['{A}', '{TC}'], [(string) $acc['admin_edit_user_id'], (string) $acc['teacher_class_id']], $ep);
            $r = $hh->req('GET', $ep, null, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
            $detail = '';
            if (!in_array($r['status'], [200], true)) {
                $detail = 'got ' . $r['status'] . ' ' . substr(preg_replace('/\s+/', ' ', $r['body']), 0, 140);
            } elseif (stripos($r['ctype'], 'json') === false) {
                $detail = 'non-json content-type ' . $r['ctype'];
            } elseif (stripos($r['body'], 'Unauthenticated') !== false) {
                $detail = 'unauthenticated';
            }
            rec('API', strtoupper($role) . ' GET ' . $ep, $r['status'], [200], $r['ms'], $detail);
        }
    }
}

/* ──────────────────────────── SUMMARY ────────────────────────────────── */
$total = count($results);
$failed = array_filter($results, fn($r) => !$r['pass']);
$state['finished_at'] = date('c');
$state['payment_max_id_before'] = $acc['payment_max_id_before'] ?? null;
file_put_contents(__DIR__ . '/state.json', json_encode($state, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/results.json', json_encode([
    'total' => $total, 'failed' => count($failed), 'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

out('');
out(sprintf('== %d tests, %d passed, %d failed ==', $total, $total - count($failed), count($failed)));
foreach ($failed as $f) {
    out(sprintf('  FAIL %s | %s | got %s expected %s | %s', $f['group'], $f['label'],
        $f['status'], implode('/', $f['expect']), $f['detail']));
}
exit(count($failed) ? 1 : 0);
