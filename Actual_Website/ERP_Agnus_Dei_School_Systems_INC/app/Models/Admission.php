<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'application_number',
        'application_type',
        'grade_level',
        'strand',
        'school_year',
        'status',
        'review_note',
        'downpayment_receipt',
        'downpayment_paid_at',
        'draft_data',
    ];

    protected function casts(): array
    {
        return [
            'draft_data' => 'array',
        ];
    }

    /**
     * Advisory-lock key serializing number generation per year (pgsql).
     */
    protected static function applicationNumberLockKey(): int
    {
        return intval(hash('crc32', 'application_number_' . date('Y')));
    }

    /**
     * Next candidate number (ADM-YYYY-XXXXX): highest existing suffix
     * for the year plus one — never count plus one, because discarded
     * drafts are deleted and leave gaps that count-based numbering
     * reissues onto surviving rows (23505). Call only inside a
     * transaction holding the advisory lock (pgsql), so the read and
     * the insert stay atomic across concurrent creates.
     */
    protected static function nextApplicationNumber(): string
    {
        $year = date('Y');
        $max = 0;
        foreach (static::where('application_number', 'like', "ADM-{$year}-%")->pluck('application_number') as $number) {
            if (is_string($number) && preg_match('/-(\d+)$/', $number, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'ADM-' . $year . '-' . str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Insert path: hold the advisory lock across BOTH the candidate
     * computation and the insert, with up to 3 attempts on a unique
     * violation as belt-and-suspenders. The old creating-hook locked
     * only around the count and released before inserting, so parallel
     * creates (e.g. double-clicked Step 7 uploads each opening a
     * draft home) read the same count and collided on 23505.
     * This ID tracks the application process only.
     */
    public function save(array $options = [])
    {
        if ($this->exists || ! empty($this->application_number)) {
            return parent::save($options);
        }

        $attempts = 0;
        do {
            try {
                return DB::transaction(function () use ($options) {
                    if (DB::getDriverName() === 'pgsql') {
                        DB::statement(
                            'SELECT pg_advisory_xact_lock(?)',
                            [static::applicationNumberLockKey()]
                        );
                    }
                    $this->application_number = static::nextApplicationNumber();

                    return parent::save($options);
                });
            } catch (QueryException $e) {
                $attempts++;
                $isNumberCollision = $e->getCode() === '23505'
                    || str_contains($e->getMessage(), 'admissions_application_number_unique');
                if (! $isNumberCollision || $attempts >= 3 || $this->exists) {
                    throw $e;
                }
                // Stale read — recompute from the now-current max and retry.
                $this->application_number = null;
            }
        } while (true);
    }

    protected static function boot()
    {
        parent::boot();

        // Fallback only: normal creates flow through save() above, which
        // assigns the number under lock. Skipped whenever save() already
        // assigned one.
        static::creating(function ($admission) {
            if (! empty($admission->application_number)) {
                return;
            }
            $admission->application_number = static::nextApplicationNumber();
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requirements()
    {
        return $this->hasMany(Requirement::class);
    }
}

