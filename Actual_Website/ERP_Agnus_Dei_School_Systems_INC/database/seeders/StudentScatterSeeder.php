<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Spec: seed-data-students-promotions.md (Child 2 of seed-data-connected-history.md).
 *
 * Builds believable students per year with promotion carry-forward:
 *  - 2024-2025: full cohorts, 12 per active section, weekday-scattered dates Jun-Aug 2024.
 *  - 2025-2026: promotion +1 grade + fresh Kinder/Grade 11 + transferee mix, 12 per section.
 *  - Current year: new + promoted mix, 15 per section, scattered to today.
 *  - 2027-2028: early-bird Kinder/Grade 11 only (applied, unbilled, ungraded).
 *
 * Idempotent: one enrollment per (student, year) — re-runs keep the earliest and
 * change nothing; registrar-corrected genders are never overwritten (blanks only
 * filled 47/47/3/3 per student-gender-data-quality.md §6). Ledger shells are keyed
 * by (student_id, school_year); money detail itself belongs to Child 3, so only
 * the current year writes Payment rows — history/early-bird get assessed shells
 * with zero paid. Locked-year seeds are console backfill; the HTTP runtime lock
 * (school_year_locked + controllers) is untouched and still refuses user edits.
 */
class StudentScatterSeeder extends Seeder
{
    public const TARGET_PER_SECTION = 15;

    public const PRIOR_TARGET_PER_SECTION = 12;

    public const EARLY_BIRD_PER_SECTION = 5;

    /** students.max(id) before this batch first ran — resume-safe name-slot offset */
    public const BASELINE_STUDENT_ID = 167;

    protected const GRADE_ORDER = [
        'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5',
        'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
    ];

    protected const EARLY_BIRD_GRADES = ['Kinder', 'Grade 11'];

    protected function resolveEmail(string $firstName, string $lastName): string
    {
        $base = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
        $baseEmail = $base . '@agnusdei.edu.ph';
        if (!User::where('email', $baseEmail)->exists()) return $baseEmail;

        $counter = 1;
        $email = $base . $counter . '@agnusdei.edu.ph';
        while (User::where('email', $email)->exists()) {
            $counter++;
            $email = $base . $counter . '@agnusdei.edu.ph';
        }
        return $email;
    }

    protected function previousGrade(string $grade): ?string
    {
        $idx = array_search($grade, self::GRADE_ORDER, true);
        if ($idx === false || $idx === 0) return null;
        /** @var string $prev */
        $prev = self::GRADE_ORDER[$idx - 1];
        return $prev;
    }

    /**
     * Admission-date window per school year. History windows sit inside
     * June–August of the start year; the current year runs June→today;
     * 2027-2028 early birds applied from Aug 2026→today (enrollment not yet open).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function windowFor(string $schoolYear, string $activeYear, Carbon $today): array
    {
        $startYear = (int) explode('-', $schoolYear)[0];
        if ($schoolYear === '2027-2028') {
            return [Carbon::create(2026, 8, 1, 8), $today->copy()];
        }
        if ($schoolYear === $activeYear) {
            return [Carbon::create($startYear, 6, 1, 8), $today->copy()];
        }
        return [Carbon::create($startYear, 6, 1, 8), Carbon::create($startYear, 8, 31, 15)];
    }

    protected function randomWeekday(Carbon $start, Carbon $end): Carbon
    {
        if ($end->lt($start)) $end = $start->copy();
        $span = (int) $start->diffInDays($end);
        $tries = 0;
        do {
            $date = $start->copy()->addDays(mt_rand(0, max(0, $span)));
            $tries++;
        } while ($date->isWeekend() && $tries < 12);
        if ($date->isWeekend()) {
            $date = $start->copy();
            while ($date->isWeekend() && $date->lte($end)) $date->addDay();
            if ($date->gt($end)) $date = $end->copy();
        }
        return $date->setTime(mt_rand(8, 15), mt_rand(0, 59), 0);
    }

    protected function generateLrn(): string
    {
        do {
            $lrn = (string) mt_rand(1, 9);
            for ($i = 0; $i < 11; $i++) $lrn .= (string) mt_rand(0, 9);
        } while (Student::where('legacy_lrn', $lrn)->exists());
        return $lrn;
    }

    /**
     * 47/47/3/3 split with a small per-section skew (±6) so section charts
     * read mixed instead of flat, while the whole-year mix stays ~47/47.
     */
    protected function scatterGender(string $sectionName): string
    {
        $skew = (int) (crc32($sectionName) % 13) - 6;
        $maleUpTo = 47 + $skew;
        $roll = mt_rand(1, 100);
        if ($roll <= $maleUpTo) return 'Male';
        if ($roll <= 94) return 'Female';
        if ($roll <= 97) return 'Non-binary';
        return 'Prefer not to say';
    }

    protected function stampRow(string $table, int $id, Carbon $date): void
    {
        DB::table($table)->where('id', $id)->update([
            'created_at' => $date->toDateTimeString(),
            'updated_at' => $date->toDateTimeString(),
        ]);
    }

    protected function paymentRatioFor(int $n): float
    {
        return match (true) {
            $n % 14 === 3 => 0.0,
            $n % 14 === 6 => 0.5,
            $n % 14 === 10 => 1.0,
            $n % 14 === 1 => 0.75,
            default => mt_rand(25, 65) / 100,
        };
    }

    /**
     * Least-loaded active section of the grade that year (strand-prefix aware
     * for SHS). Sections carry no school_year — load is counted per year.
     */
    protected function leastLoadedSection(string $grade, ?string $strand, string $schoolYear): ?Section
    {
        $candidates = Section::where('grade_level', $grade)
            ->where('is_active', true)
            ->when($strand !== null && $strand !== '', fn($q) => $q->where('section_name', 'LIKE', "{$strand}%"))
            ->orderBy('section_name')
            ->get();
        if ($candidates->isEmpty()) {
            $candidates = Section::where('grade_level', $grade)
                ->where('is_active', true)
                ->orderBy('section_name')
                ->get();
        }
        if ($candidates->isEmpty()) return null;
        $best = null;
        $bestLoad = PHP_INT_MAX;
        foreach ($candidates as $candidate) {
            $load = Enrollment::where('section_id', $candidate->id)
                ->where('school_year', $schoolYear)
                ->count();
            if ($load < $bestLoad) {
                $bestLoad = $load;
                $best = $candidate;
            }
        }
        return $best;
    }

    protected function attachClasses(Enrollment $enrollment, string $grade, string $sectionName, string $schoolYear): void
    {
        $classes = Classes::where('grade_level', $grade)
            ->where('section', $sectionName)
            ->where('school_year', $schoolYear)
            ->where(function ($q) {
                $q->whereNull('term')->orWhere('term', '');
            })
            ->get();
        if ($classes->isEmpty()) {
            $classes = Classes::where('grade_level', $grade)
                ->where('section', $sectionName)
                ->where('school_year', $schoolYear)
                ->get();
        }
        $pivotRows = [];
        foreach ($classes as $class) {
            $pivotRows[] = [
                'enrollment_id' => $enrollment->id,
                'class_id' => $class->id,
            ];
        }
        if (!empty($pivotRows)) {
            DB::table('enrollment_subject')->upsert($pivotRows, ['enrollment_id', 'class_id']);
        }
    }

    /**
     * Ledger shell per (student, year). Current-year shells carry the varied
     * payment mix + receipt rows; history/early-bird shells assess with zero
     * paid and NO payment rows (money detail is Child 3).
     */
    protected function ensureLedger(
        int $studentId,
        bool $isScholar,
        int $seedNumber,
        string $grade,
        string $schoolYear,
        Carbon $date,
        bool $isCurrentYear,
        array $cashierIds,
    ): StudentLedger {
        $schedules = FeeSchedule::where('grade_level', $grade)
            ->where('school_year', $schoolYear)
            ->get();
        $totalAssessed = $schedules->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

        $discountType = null;
        $discountAmount = 0;
        if ($isScholar) {
            $discountType = 'esc';
            $discountAmount = $schedules->sum('tuition_fee');
        } elseif ($seedNumber % 13 === 2) {
            $discountType = 'honor';
            $discountAmount = round($totalAssessed * 0.10, 2);
        } elseif ($seedNumber % 13 === 7) {
            $discountType = 'sibling';
            $discountAmount = round($totalAssessed * 0.05, 2);
        }

        $effectiveAssessed = $totalAssessed - $discountAmount;
        $partialPayment = 0.0;
        if ($isCurrentYear) {
            $partialPayment = round($effectiveAssessed * $this->paymentRatioFor($seedNumber), 2);
        }

        $ledger = StudentLedger::firstOrCreate(
            ['student_id' => $studentId, 'school_year' => $schoolYear],
            [
                'payment_plan' => $seedNumber % 5 === 0 ? 'full' : 'installment',
                'total_assessed' => $totalAssessed,
                'discount_type' => $discountType,
                'discount_applied' => $discountAmount,
                'total_paid' => $partialPayment,
                'balance' => max(0, $effectiveAssessed - $partialPayment),
                'clearance_status' => $partialPayment >= $effectiveAssessed ? 'Cleared' : 'Uncleared',
            ]
        );
        if (!$ledger->wasRecentlyCreated) return $ledger;
        $this->stampRow('student_ledgers', $ledger->id, $date);

        if ($isCurrentYear && $partialPayment > 0) {
            $student = Student::find($studentId);
            $receiptBase = $student ? $student->student_number : (string) $studentId;
            Payment::updateOrCreate(
                ['receipt_number' => 'RCP-' . $receiptBase . '-001'],
                [
                    'ledger_id' => $ledger->id,
                    'cashier_id' => !empty($cashierIds) ? $cashierIds[array_rand($cashierIds)] : null,
                    'amount_paid' => $partialPayment,
                    'payment_date' => $date->copy()->addDays(mt_rand(0, 14))->toDateString(),
                ]
            );
            if ($partialPayment < $effectiveAssessed && $seedNumber % 3 === 0) {
                $extraPayment = round($effectiveAssessed * (mt_rand(10, 25) / 100), 2);
                Payment::updateOrCreate(
                    ['receipt_number' => 'RCP-' . $receiptBase . '-002'],
                    [
                        'ledger_id' => $ledger->id,
                        'cashier_id' => !empty($cashierIds) ? $cashierIds[array_rand($cashierIds)] : null,
                        'amount_paid' => $extraPayment,
                        'payment_date' => $date->copy()->addDays(mt_rand(0, 14))->toDateString(),
                    ]
                );
                $newPaid = $partialPayment + $extraPayment;
                StudentLedger::where('id', $ledger->id)->update([
                    'total_paid' => $newPaid,
                    'balance' => max(0, $effectiveAssessed - $newPaid),
                    'clearance_status' => ($ledger->payment_plan === 'full' && $newPaid >= $effectiveAssessed) ? 'Cleared' : 'Uncleared',
                ]);
            }
        }
        return $ledger;
    }

    public function run(): void
    {
        $activeYear = active_school_year();
        $today = now();
        $password = Hash::make('Agnus2026!');
        $cashierIds = User::where('role_id', 3)->pluck('id')->toArray();

        $beforeUsers = (int) DB::table('users')->max('id');
        $beforeStudents = (int) DB::table('students')->max('id');
        $this->command->info("Baseline id ranges — users <= {$beforeUsers}, students <= {$beforeStudents}");

        $schoolYears = array_values(array_unique([
            '2024-2025',
            '2025-2026',
            $activeYear,
            '2027-2028',
        ]));

        $firstNames = [
            'Aiden', 'Althea', 'Amber', 'Anton', 'Bea', 'Benjamin', 'Bless', 'Carlo', 'Catherine', 'Cedric',
            'Dianne', 'Dominic', 'Elena', 'Emman', 'Erika', 'Francis', 'Gabriel', 'Gemma', 'Hannah', 'Hazel',
            'Ivan', 'Jasmine', 'Jomar', 'Joyce', 'Kevin', 'Lara', 'Lester', 'Maica', 'Nico', 'Olivia',
        ];
        $lastNames = [
            'Aguilar', 'Bartolome', 'Castillo', 'Dimaculangan', 'Espinosa', 'Fabian', 'Gatchalian', 'Hernandez', 'Ignacio', 'Jimenez',
            'Lardizabal', 'Manalo', 'Natividad', 'Ocampo', 'Panganiban', 'Quizon', 'Rosales', 'Salvador', 'Tolentino', 'Ubaldo',
            'Vergara', 'Whitmore', 'Yamamoto', 'Zabala',
        ];
        $middlePool = ['Bautista', 'Reyes', 'Aquino', 'Villanueva', 'Cruz', 'Garcia', 'Ramos', 'Torres', 'Navarro', 'Santiago'];
        $religions = ['Catholic', 'Christian', 'Iglesia ni Cristo', 'Islam', 'Buddhist', 'None'];
        $citizenships = ['Filipino', 'Filipino', 'Filipino', 'Dual Filipino-American', 'Dual Filipino-Chinese'];
        $occupations = ['Teacher', 'Engineer', 'Nurse', 'Accountant', 'Business Owner', 'OFW', 'Government Employee', 'Housewife', 'Driver', 'Farmer', 'Mechanic', 'Architect'];
        $barangays = ['San Antonio', 'Santa Maria', 'San Jose', 'Santo Niño', 'San Isidro', 'Santiago', 'San Miguel', 'Santo Tomas', 'San Francisco', 'Santa Cruz'];
        $cities = ['Quezon City', 'Manila', 'Makati', 'Pasig', 'Mandaluyong', 'Caloocan', 'Pasay', 'Parañaque', 'Las Piñas', 'Taguig'];
        $relationships = ['Mother', 'Father', 'Aunt', 'Uncle', 'Grandmother', 'Grandfather', 'Sibling'];
        $previousSchools = ['Sample Elementary School', 'St. Mary\'s Academy', 'Holy Child School', 'Sacred Heart Academy', 'Don Bosco School', 'La Salle Greenhills', 'Ateneo de Manila', 'St. Scholastica\'s College', 'Immaculate Conception Academy'];
        $entryAdmissionTypes = ['New', 'New', 'New', 'Honor', 'Sibling', 'Transferee'];

        // resume-safe: continue the name sequence where a previous (possibly killed) run stopped.
        // Promotions reuse existing students and never consume name slots.
        $slot = (int) Student::where('id', '>', self::BASELINE_STUDENT_ID)->count();

        $totalCreated = 0;
        $totalPromoted = 0;

        foreach ($schoolYears as $yearIndex => $schoolYear) {
            $isEarlyBirdYear = $schoolYear === '2027-2028';
            $isCurrentYear = $schoolYear === $activeYear;
            $target = $isEarlyBirdYear
                ? self::EARLY_BIRD_PER_SECTION
                : ($isCurrentYear ? self::TARGET_PER_SECTION : self::PRIOR_TARGET_PER_SECTION);
            [$windowStart, $windowEnd] = $this->windowFor($schoolYear, $activeYear, $today);

            $sections = Section::where('is_active', true)
                ->when($isEarlyBirdYear, fn($q) => $q->whereIn('grade_level', self::EARLY_BIRD_GRADES))
                ->orderBy('grade_level')
                ->orderBy('section_name')
                ->get();

            // Students already enrolled this year (one enrollment per student per year).
            $enrolledSet = Enrollment::where('school_year', $schoolYear)->pluck('student_id')->flip()->toArray();

            // Promotion pools from the previous built year: prev-grade Active
            // enrollments whose student has no enrollment yet this year.
            // Grade 12 pools group by strand; Grade 12 has no next grade (graduates).
            /** @var array<string, mixed> $pools */
            $pools = [];
            $prevYear = $yearIndex > 0 ? $schoolYears[$yearIndex - 1] : null;
            if (!$isEarlyBirdYear && $prevYear !== null) {
                foreach (self::GRADE_ORDER as $grade) {
                    $prevGrade = $this->previousGrade($grade);
                    if ($prevGrade === null) continue;
                    $rows = Enrollment::with('student')
                        ->where('school_year', $prevYear)
                        ->where('status', 'Active')
                        ->whereHas('section', fn($q) => $q->where('grade_level', $prevGrade))
                        ->orderBy('id')
                        ->get()
                        ->filter(fn($e) => $e->student !== null && !isset($enrolledSet[$e->student_id]))
                        ->values();
                    if ($grade === 'Grade 12') {
                        $grouped = [];
                        foreach ($rows as $row) {
                            $strandKey = trim((string) $row->strand);
                            $grouped[$strandKey][] = $row;
                        }
                        $pools[$grade] = $grouped;
                    } else {
                        $pools[$grade] = $rows;
                    }
                }
            }

            // Fill least-loaded sections first so promotions spread evenly.
            $loads = [];
            foreach ($sections as $section) {
                $loads[$section->id] = Enrollment::where('section_id', $section->id)
                    ->where('school_year', $schoolYear)
                    ->where('status', 'Active')
                    ->count();
            }
            $ordered = $sections->sortBy(fn($s) => $loads[$s->id])->values();

            $yearCreated = 0;
            $yearPromoted = 0;
            $summary = [];

            foreach ($ordered as $section) {
                $grade = $section->grade_level;
                $strand = null;
                if (in_array($grade, ['Grade 11', 'Grade 12'], true)) {
                    // Trim the prefix so "STEM - ..." never saves as "STEM ".
                    $strand = trim(explode('-', $section->section_name)[0]) ?: null;
                }
                $current = $loads[$section->id] ?? 0;
                $deficit = $target - $current;
                if ($deficit <= 0) {
                    $summary[] = sprintf('  %-14s %-28s %2d -> %2d', $grade, $section->section_name, $current, $current);
                    continue;
                }

                $made = 0;
                $promoted = 0;

                // 1) Promotion carry-forward, one grade up.
                if (!$isEarlyBirdYear && isset($pools[$grade])) {
                    if ($grade === 'Grade 12') {
                        $strandKey = trim((string) $strand);
                        while ($made < $deficit && !empty($pools[$grade][$strandKey])) {
                            /** @var Enrollment $prior */
                            $prior = array_shift($pools[$grade][$strandKey]);
                            if (isset($enrolledSet[$prior->student_id])) continue;
                            $this->promoteStudent($prior, $section, $grade, $strand, $schoolYear, $windowStart, $windowEnd, $isCurrentYear, $cashierIds);
                            $enrolledSet[$prior->student_id] = true;
                            $made++;
                            $promoted++;
                        }
                    } else {
                        while ($made < $deficit && $pools[$grade]->isNotEmpty()) {
                            /** @var Enrollment $prior */
                            $prior = $pools[$grade]->shift();
                            if (isset($enrolledSet[$prior->student_id])) continue;
                            $this->promoteStudent($prior, $section, $grade, $strand, $schoolYear, $windowStart, $windowEnd, $isCurrentYear, $cashierIds);
                            $enrolledSet[$prior->student_id] = true;
                            $made++;
                            $promoted++;
                        }
                    }
                }

                // 2) Fresh top-up: entry grades (Kinder/Grade 11) are all new;
                // other grades take a small transferee mix.
                while ($made < $deficit) {
                    $isEntryGrade = in_array($grade, ['Kinder', 'Grade 11'], true);
                    $applicationType = $isEntryGrade
                        ? $entryAdmissionTypes[array_rand($entryAdmissionTypes)]
                        : 'Transferee';
                    $admissionStatus = $isEarlyBirdYear
                        ? 'Pending'
                        : ($isCurrentYear ? $this->mixedAdmissionStatus() : 'Approved By Registrar');
                    $this->createFreshStudent(
                        $section, $grade, $strand, $schoolYear,
                        $windowStart, $windowEnd, $applicationType, $admissionStatus,
                        $isCurrentYear, $cashierIds, $password,
                        $firstNames, $lastNames, $middlePool, $religions, $citizenships,
                        $occupations, $barangays, $cities, $relationships, $previousSchools,
                        $slot, $enrolledSet
                    );
                    $slot++;
                    $made++;
                }

                $loads[$section->id] = $current + $made;
                $yearCreated += $made;
                $yearPromoted += $promoted;
                $summary[] = sprintf(
                    '  %-14s %-28s %2d -> %2d (+%d%s)',
                    $grade, $section->section_name, $current, $current + $made, $made,
                    $promoted ? ", {$promoted} promoted" : ''
                );
            }

            $this->command->info("Year {$schoolYear} (target {$target}/section):");
            foreach ($summary as $line) $this->command->info($line);
            $this->command->info("Year {$schoolYear}: filled {$yearCreated} seats ({$yearPromoted} promoted).");
            $totalCreated += $yearCreated;
            $totalPromoted += $yearPromoted;
        }

        $afterUsers = (int) DB::table('users')->max('id');
        $afterStudents = (int) DB::table('students')->max('id');
        $active = Enrollment::where('status', 'Active')->count();
        $this->command->info("Filled {$totalCreated} seats ({$totalPromoted} promotions). Active enrollments now: {$active}");
        $this->command->info("Cleanup id ranges — users ({$beforeUsers}, {$afterUsers}], students ({$beforeStudents}, {$afterStudents}]");
    }

    protected function mixedAdmissionStatus(): string
    {
        $statusRoll = mt_rand(0, 99);
        return match (true) {
            $statusRoll < 60 => 'Approved By Registrar',
            $statusRoll < 85 => 'Pending',
            default => 'Rejected',
        };
    }

    /**
     * Carry one student one grade up into a new-year enrollment. Keeps the
     * earliest enrollment when one already exists (never double-enrolls) and
     * links the promotion chain for the G7→G8→G9 trace.
     */
    protected function promoteStudent(
        Enrollment $prior,
        Section $section,
        string $grade,
        ?string $sectionStrand,
        string $schoolYear,
        Carbon $windowStart,
        Carbon $windowEnd,
        bool $isCurrentYear,
        array $cashierIds,
    ): void {
        DB::transaction(function () use (
            $prior, $section, $grade, $sectionStrand,
            $schoolYear, $windowStart, $windowEnd, $isCurrentYear, $cashierIds
        ) {
            $student = $prior->student;
            if ($student === null) return;
            // Registrar-corrected genders are kept; blanks get a fresh value only.
            if (empty($student->gender)) {
                Student::where('id', $student->id)->update(['gender' => $this->scatterGender($section->section_name)]);
                $student->refresh();
            }
            // SHS strand follows the target section; JHS-and-below stay null.
            $strand = $sectionStrand;
            if ($grade === 'Grade 12' && ($strand === null || $strand === '')) {
                $strand = trim((string) $prior->strand) ?: null;
            }
            $date = $this->randomWeekday($windowStart, $windowEnd);

            $admission = Admission::firstOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'application_type' => 'New',
                    'grade_level' => $grade,
                    'strand' => $strand,
                    'status' => $isCurrentYear ? $this->mixedAdmissionStatus() : 'Approved By Registrar',
                ]
            );
            if ($admission->wasRecentlyCreated) $this->stampRow('admissions', $admission->id, $date);

            $enrollment = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'section_id' => $section->id,
                    'strand' => $strand,
                    'status' => 'Active',
                ]
            );
            if (!$enrollment->wasRecentlyCreated) return;
            $this->stampRow('enrollments', $enrollment->id, $date);

            $this->attachClasses($enrollment, $grade, $section->section_name, $schoolYear);
            $this->ensureLedger(
                $student->id, (bool) $student->scholarship, (int) $student->id,
                $grade, $schoolYear, $date, $isCurrentYear, $cashierIds
            );

            if (empty($prior->promoted_to_enrollment_id)) {
                Enrollment::where('id', $prior->id)->update(['promoted_to_enrollment_id' => $enrollment->id]);
            }
        });
    }

    /**
     * Create one brand-new student + user + admission + enrollment + ledger
     * shell for a seat that promotions did not fill. Consumes one name slot.
     */
    protected function createFreshStudent(
        Section $section,
        string $grade,
        ?string $strand,
        string $schoolYear,
        Carbon $windowStart,
        Carbon $windowEnd,
        string $applicationType,
        string $admissionStatus,
        bool $isCurrentYear,
        array $cashierIds,
        string $password,
        array $firstNames,
        array $lastNames,
        array $middlePool,
        array $religions,
        array $citizenships,
        array $occupations,
        array $barangays,
        array $cities,
        array $relationships,
        array $previousSchools,
        int $slot,
        array &$enrolledSet,
    ): void {
        DB::transaction(function () use (
            $section, $grade, $strand, $schoolYear,
            $windowStart, $windowEnd, $applicationType, $admissionStatus,
            $isCurrentYear, $cashierIds, $password,
            $firstNames, $lastNames, $middlePool, $religions, $citizenships,
            $occupations, $barangays, $cities, $relationships, $previousSchools,
            $slot, &$enrolledSet
        ) {
            $firstName = $firstNames[$slot % count($firstNames)];
            $lastName = $lastNames[intdiv($slot, count($firstNames)) % count($lastNames)];
            $middleName = $middlePool[$slot % count($middlePool)];
            $email = $this->resolveEmail($firstName, $lastName);
            $scholarship = $slot % 10 === 4;
            $date = $this->randomWeekday($windowStart, $windowEnd);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $firstName . ' ' . $lastName,
                    'password' => $password,
                    'role_id' => 7,
                ]
            );
            if ($user->wasRecentlyCreated) $this->stampRow('users', $user->id, $date);

            $existingStudent = Student::where('user_id', $user->id)->first();
            if ($existingStudent !== null) {
                // Email collision with a pre-existing login: enroll that
                // student instead of forking a duplicate identity.
                if (isset($enrolledSet[$existingStudent->id])) return;
                $student = $existingStudent;
                if (empty($student->gender)) {
                    Student::where('id', $student->id)->update(['gender' => $this->scatterGender($section->section_name)]);
                    $student->refresh();
                }
            } else {
                $barangay = $barangays[array_rand($barangays)];
                $city = $cities[array_rand($cities)];
                $student = Student::create([
                    'user_id' => $user->id,
                    'student_number' => Student::generateStudentNumber(),
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'gender' => $this->scatterGender($section->section_name),
                    'personal_email' => $email,
                    'date_of_birth' => now()->subYears(match ($grade) {
                        'Kinder' => 5, 'Grade 1' => 6, 'Grade 2' => 7, 'Grade 3' => 8,
                        'Grade 4' => 9, 'Grade 5' => 10, 'Grade 6' => 11, 'Grade 7' => 12,
                        'Grade 8' => 13, 'Grade 9' => 14, 'Grade 10' => 15,
                        'Grade 11' => 16, 'Grade 12' => 17, default => 10,
                    })->subDays(mt_rand(100, 300)),
                    'place_of_birth' => $cities[array_rand($cities)] . ', Philippines',
                    'citizenship' => $citizenships[array_rand($citizenships)],
                    'religion' => $religions[array_rand($religions)],
                    'contact_number' => '+63917' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'permanent_address' => 'Blk ' . mt_rand(1, 20) . ' Lot ' . mt_rand(1, 30) . ', ' . $barangay . ', ' . $city,
                    'current_address' => 'Blk ' . mt_rand(1, 20) . ' Lot ' . mt_rand(1, 30) . ', ' . $barangay . ', ' . $city,
                    'legacy_lrn' => $this->generateLrn(),
                    'father_name' => 'Mr. ' . $firstName . ' ' . $middleName . ' ' . $lastName . ' Sr.',
                    'father_occupation' => $occupations[array_rand($occupations)],
                    'mother_name' => 'Mrs. ' . $middlePool[array_rand($middlePool)] . ' ' . $lastName,
                    'mother_occupation' => $occupations[array_rand($occupations)],
                    'guardian_name' => 'Mr./Mrs. ' . $firstName . ' ' . $lastName . '\'s Guardian',
                    'guardian_contact' => '+63918' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => $firstName . ' ' . $middleName . ' ' . $lastName . '\'s Emergency Contact',
                    'emergency_contact_number' => '+63919' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                    'previous_school' => $previousSchools[array_rand($previousSchools)],
                    'previous_school_address' => $cities[array_rand($cities)] . ', Philippines',
                    'status' => 'enrolled',
                    'scholarship' => $scholarship,
                    'archived_at' => null,
                ]);
                $this->stampRow('students', $student->id, $date);
            }

            $admission = Admission::firstOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'application_type' => $applicationType,
                    'grade_level' => $grade,
                    'strand' => $strand,
                    'status' => $admissionStatus,
                ]
            );
            if ($admission->wasRecentlyCreated) $this->stampRow('admissions', $admission->id, $date);

            $enrollment = Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'section_id' => $section->id,
                    'strand' => $strand,
                    'status' => 'Active',
                ]
            );
            if (!$enrollment->wasRecentlyCreated) return;
            $this->stampRow('enrollments', $enrollment->id, $date);
            $enrolledSet[$student->id] = true;

            $this->attachClasses($enrollment, $grade, $section->section_name, $schoolYear);
            $this->ensureLedger(
                $student->id, (bool) $student->scholarship, $slot,
                $grade, $schoolYear, $date, $isCurrentYear, $cashierIds
            );
        });
    }
}
