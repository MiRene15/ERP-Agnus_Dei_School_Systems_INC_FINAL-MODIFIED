<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ClinicLog;
use App\Models\LibraryTransaction;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LibraryAndClinicSeeder extends Seeder
{
    public function run(): void
    {
        // Seed books — varied genres, publishers, and year ranges
        $books = [
            ['title' => 'Mathematics for the Modern World', 'author' => 'Maria Santos', 'isbn' => '978-621-001-001-1', 'serial_number' => 'SN-2022-0001', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 20, 'available_quantity' => 17, 'price' => 450],
            ['title' => 'Filipino Heritage and Culture', 'author' => 'Jose Reyes', 'isbn' => '978-621-001-002-8', 'serial_number' => 'SN-2021-0002', 'publisher' => 'Vibal Group', 'year_published' => 2021, 'quantity' => 15, 'available_quantity' => 13, 'price' => 380],
            ['title' => 'Science and Technology Today', 'author' => 'Ana Cruz', 'isbn' => '978-621-001-003-5', 'serial_number' => 'SN-2023-0003', 'publisher' => 'C&E Publishing', 'year_published' => 2023, 'quantity' => 25, 'available_quantity' => 22, 'price' => 520],
            ['title' => 'Understanding Philippine History', 'author' => 'Paolo Garcia', 'isbn' => '978-621-001-004-2', 'serial_number' => 'SN-2020-0004', 'publisher' => 'Rex Book Store', 'year_published' => 2020, 'quantity' => 10, 'available_quantity' => 9, 'price' => 350],
            ['title' => 'English Communication Arts', 'author' => 'Liza Mendoza', 'isbn' => '978-621-001-005-9', 'serial_number' => 'SN-2022-0005', 'publisher' => 'Sib Publishing', 'year_published' => 2022, 'quantity' => 18, 'available_quantity' => 15, 'price' => 410],
            ['title' => 'Values Education for Youth', 'author' => 'Pedro Aquino', 'isbn' => '978-621-001-006-6', 'serial_number' => 'SN-2023-0006', 'publisher' => 'Lorimar Publishing', 'year_published' => 2023, 'quantity' => 12, 'available_quantity' => 11, 'price' => 290],
            ['title' => 'Komunikasyon sa Wikang Filipino', 'author' => 'Bienvenido Ramos', 'isbn' => '978-621-001-007-3', 'serial_number' => 'SN-2021-0007', 'publisher' => 'Phoenix Publishing', 'year_published' => 2021, 'quantity' => 14, 'available_quantity' => 14, 'price' => 360],
            ['title' => 'Earth and Life Science', 'author' => 'Carlo Dela Cruz', 'isbn' => '978-621-001-008-0', 'serial_number' => 'SN-2022-0008', 'publisher' => 'C&E Publishing', 'year_published' => 2022, 'quantity' => 16, 'available_quantity' => 14, 'price' => 480],
            ['title' => '21st Century Literature', 'author' => 'Angelica Torres', 'isbn' => '978-621-001-009-7', 'serial_number' => 'SN-2023-0009', 'publisher' => 'Vibal Group', 'year_published' => 2023, 'quantity' => 20, 'available_quantity' => 18, 'price' => 395],
            ['title' => 'Statistics and Probability', 'author' => 'Rafael Lim', 'isbn' => '978-621-001-010-3', 'serial_number' => 'SN-2022-0010', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 12, 'available_quantity' => 10, 'price' => 430],
            ['title' => 'Physical Science', 'author' => 'Diana Reyes', 'isbn' => '978-621-001-011-0', 'serial_number' => 'SN-2021-0011', 'publisher' => 'C&E Publishing', 'year_published' => 2021, 'quantity' => 18, 'available_quantity' => 16, 'price' => 510],
            ['title' => 'Introduction to World Religions', 'author' => 'Fr. Antonio Diaz', 'isbn' => '978-621-001-012-7', 'serial_number' => 'SN-2020-0012', 'publisher' => 'Rex Book Store', 'year_published' => 2020, 'quantity' => 10, 'available_quantity' => 8, 'price' => 320],
            ['title' => 'Creative Writing in Filipino', 'author' => 'Grace Aquino', 'isbn' => '978-621-001-013-4', 'serial_number' => 'SN-2023-0013', 'publisher' => 'Sib Publishing', 'year_published' => 2023, 'quantity' => 15, 'available_quantity' => 15, 'price' => 340],
            ['title' => 'General Mathematics', 'author' => 'Engr. Marco Villanueva', 'isbn' => '978-621-001-014-1', 'serial_number' => 'SN-2022-0014', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 22, 'available_quantity' => 20, 'price' => 460],
            ['title' => 'Philippine Politics and Governance', 'author' => 'Prof. Elena Pascual', 'isbn' => '978-621-001-015-8', 'serial_number' => 'SN-2021-0015', 'publisher' => 'Lorimar Publishing', 'year_published' => 2021, 'quantity' => 14, 'available_quantity' => 12, 'price' => 375],
            ['title' => 'Organic Chemistry', 'author' => 'Dr. Samuel Garcia', 'isbn' => '978-621-001-016-5', 'serial_number' => 'SN-2023-0016', 'publisher' => 'C&E Publishing', 'year_published' => 2023, 'quantity' => 10, 'available_quantity' => 8, 'price' => 580],
            ['title' => 'Media and Information Literacy', 'author' => 'Jasmine Ramos', 'isbn' => '978-621-001-017-2', 'serial_number' => 'SN-2022-0017', 'publisher' => 'Vibal Group', 'year_published' => 2022, 'quantity' => 16, 'available_quantity' => 14, 'price' => 400],
            ['title' => 'Creative Nonfiction', 'author' => 'Benedict Cruz', 'isbn' => '978-621-001-018-9', 'serial_number' => 'SN-2021-0018', 'publisher' => 'Rex Book Store', 'year_published' => 2021, 'quantity' => 12, 'available_quantity' => 11, 'price' => 355],
            ['title' => 'Empowerment Technologies', 'author' => 'Engr. Paulo Lim', 'isbn' => '978-621-001-019-6', 'serial_number' => 'SN-2023-0019', 'publisher' => 'Phoenix Publishing', 'year_published' => 2023, 'quantity' => 18, 'available_quantity' => 16, 'price' => 445],
            ['title' => 'Ethics', 'author' => 'Dr. Rose Aquino', 'isbn' => '978-621-001-020-2', 'serial_number' => 'SN-2022-0020', 'publisher' => 'Sib Publishing', 'year_published' => 2022, 'quantity' => 10, 'available_quantity' => 9, 'price' => 310],
            // Additional books (21-35)
            ['title' => 'Araling Panlipunan', 'author' => 'Dante Mendoza', 'isbn' => '978-621-001-021-9', 'serial_number' => 'SN-2022-0021', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 14, 'available_quantity' => 12, 'price' => 365],
            ['title' => 'Tanging Yaman', 'author' => 'Fr. Luis Navarro', 'isbn' => '978-621-001-022-6', 'serial_number' => 'SN-2021-0022', 'publisher' => 'Vibal Group', 'year_published' => 2021, 'quantity' => 8, 'available_quantity' => 8, 'price' => 280],
            ['title' => 'Paaralang Filipino', 'author' => 'Bienvenido Ramos', 'isbn' => '978-621-001-023-3', 'serial_number' => 'SN-2020-0023', 'publisher' => 'Rex Book Store', 'year_published' => 2020, 'quantity' => 20, 'available_quantity' => 18, 'price' => 420],
            ['title' => 'Reading and Writing Skills', 'author' => 'Sofia Garcia', 'isbn' => '978-621-001-024-0', 'serial_number' => 'SN-2023-0024', 'publisher' => 'C&E Publishing', 'year_published' => 2023, 'quantity' => 25, 'available_quantity' => 23, 'price' => 390],
            ['title' => 'Understanding Culture, Society, and Politics', 'author' => 'Prof. Elena Pascual', 'isbn' => '978-621-001-025-7', 'serial_number' => 'SN-2022-0025', 'publisher' => 'Lorimar Publishing', 'year_published' => 2022, 'quantity' => 16, 'available_quantity' => 14, 'price' => 440],
            ['title' => 'Introduction to the Philosophy of the Human Person', 'author' => 'Dr. Rose Aquino', 'isbn' => '978-621-001-026-4', 'serial_number' => 'SN-2023-0026', 'publisher' => 'Sib Publishing', 'year_published' => 2023, 'quantity' => 12, 'available_quantity' => 10, 'price' => 315],
            ['title' => 'Komunikasyon at Pananaliksik', 'author' => 'Grace Aquino', 'isbn' => '978-621-001-027-1', 'serial_number' => 'SN-2022-0027', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 18, 'available_quantity' => 16, 'price' => 405],
            ['title' => 'Pagbasa at Pagsusuri', 'author' => 'Engr. Marco Villanueva', 'isbn' => '978-621-001-028-8', 'serial_number' => 'SN-2021-0028', 'publisher' => 'Vibal Group', 'year_published' => 2021, 'quantity' => 14, 'available_quantity' => 12, 'price' => 370],
            ['title' => 'Disaster Readiness and Risk Reduction', 'author' => 'Dante Mendoza', 'isbn' => '978-621-001-029-5', 'serial_number' => 'SN-2023-0029', 'publisher' => 'C&E Publishing', 'year_published' => 2023, 'quantity' => 10, 'available_quantity' => 9, 'price' => 350],
            ['title' => 'Creative Writing Malikhaing Pagsulat', 'author' => 'Angelica Torres', 'isbn' => '978-621-001-030-1', 'serial_number' => 'SN-2022-0030', 'publisher' => 'Rex Book Store', 'year_published' => 2022, 'quantity' => 12, 'available_quantity' => 11, 'price' => 335],
            ['title' => 'Research in Daily Life 1', 'author' => 'Rafael Lim', 'isbn' => '978-621-001-031-8', 'serial_number' => 'SN-2023-0031', 'publisher' => 'Lorimar Publishing', 'year_published' => 2023, 'quantity' => 15, 'available_quantity' => 13, 'price' => 395],
            ['title' => 'Physical Education and Health', 'author' => 'Coach Mark Dela Cruz', 'isbn' => '978-621-001-032-5', 'serial_number' => 'SN-2022-0032', 'publisher' => 'Sib Publishing', 'year_published' => 2022, 'quantity' => 20, 'available_quantity' => 19, 'price' => 295],
            ['title' => 'Work Immersion Learning Guide', 'author' => 'Prof. Elena Pascual', 'isbn' => '978-621-001-033-2', 'serial_number' => 'SN-2023-0033', 'publisher' => 'Phoenix Publishing', 'year_published' => 2023, 'quantity' => 10, 'available_quantity' => 10, 'price' => 385],
            ['title' => 'Inquiry and Immersion in Science', 'author' => 'Dr. Samuel Garcia', 'isbn' => '978-621-001-034-9', 'serial_number' => 'SN-2022-0034', 'publisher' => 'C&E Publishing', 'year_published' => 2022, 'quantity' => 16, 'available_quantity' => 14, 'price' => 475],
            ['title' => 'Fundamentals of Accountancy', 'author' => 'Liza Mendoza', 'isbn' => '978-621-001-035-6', 'serial_number' => 'SN-2021-0035', 'publisher' => 'Vibal Group', 'year_published' => 2021, 'quantity' => 12, 'available_quantity' => 10, 'price' => 415],
        ];

        foreach ($books as $book) {
            Book::updateOrCreate(['isbn' => $book['isbn']], $book);
        }

        // Ensure all existing books have serial_number and price
        Book::whereNull('serial_number')->orWhere('serial_number', '')->each(function ($book, $index) {
            $book->update([
                'serial_number' => 'SN-' . ($book->year_published ?? 2022) . '-' . str_pad($book->id, 4, '0', STR_PAD_LEFT),
                'price' => $book->price ?? random_int(290, 580),
            ]);
        });

        // Fix: role_id=5 is Librarian, role_id=6 is Nurse (was swapped before)
        $librarianId = \App\Models\User::where('role_id', 5)->first()?->id;
        $nurseId = \App\Models\User::where('role_id', 6)->first()?->id;
        $booksById = Book::orderBy('id')->get();

        if (!$librarianId || $booksById->isEmpty()) {
            return;
        }

        $activeYear = active_school_year();
        $years = array_values(array_unique(['2024-2025', '2025-2026', $activeYear]));

        $txnCount = 0;
        $visitCount = 0;

        foreach ($years as $yearIndex => $year) {
            $isCurrent = $year === $activeYear;

            DB::transaction(function () use (
                $year, $yearIndex, $isCurrent, $booksById, $librarianId, $nurseId,
                &$txnCount, &$visitCount
            ): void {
                // Same-year borrowers only: students with an Active enrollment that year.
                $studentIds = DB::table('enrollments')
                    ->where('status', 'Active')
                    ->where('school_year', $year)
                    ->orderBy('student_id')
                    ->distinct()
                    ->pluck('student_id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();

                if (empty($studentIds)) {
                    return;
                }

                $weekdays = $this->weekdaysInWindow($year, $isCurrent);
                if (empty($weekdays)) {
                    return;
                }
                $dayCount = count($weekdays);
                $bookCount = $booksById->count();

                // Library: ~30% of students borrow once this year; ~15% of those
                // return late with a per-day fine. Current year keeps ~10% open.
                foreach ($studentIds as $studentId) {
                    if ((($studentId * 7 + $yearIndex * 13) % 10) >= 3) {
                        continue;
                    }

                    $book = $booksById[($studentId * 7 + $yearIndex * 17) % $bookCount];

                    // Skip when this student+book row already exists: keeps re-runs
                    // (on any later day) from churning dates and never touches rows
                    // created by the librarian UI.
                    $alreadySeeded = LibraryTransaction::where('student_id', $studentId)
                        ->where('book_title', $book->title)
                        ->exists();
                    if ($alreadySeeded) {
                        continue;
                    }

                    $isLate = (($studentId * 3 + $yearIndex * 5) % 20) < 3;
                    $isOpen = $isCurrent && (($studentId * 5 + $yearIndex) % 10) === 0;

                    if ($isOpen) {
                        // Recent borrow so the due date reads live (future or just overdue).
                        $recent = array_values(array_filter(
                            $weekdays,
                            fn (string $d): bool => $d >= Carbon::today()->subDays(14)->toDateString()
                        ));
                        $pool = !empty($recent) ? $recent : [$weekdays[$dayCount - 1]];
                        $borrowDay = $pool[$studentId % count($pool)];
                        $borrowDate = Carbon::parse($borrowDay);
                        $dueDate = $borrowDate->copy()->addDays(7);

                        LibraryTransaction::create([
                            'student_id' => $studentId,
                            'book_id' => $book->id,
                            'book_title' => $book->title,
                            'librarian_id' => $librarianId,
                            'borrow_date' => $borrowDate,
                            'return_date' => $dueDate,
                            'status' => 'Borrowed',
                            'condition_at_borrow' => 'Good',
                            'condition_at_return' => null,
                            'late_fee' => 0,
                            'total_fees' => 0,
                            'fees_assessed' => false,
                        ]);
                        $txnCount++;
                        continue;
                    }

                    $borrowDay = $weekdays[($studentId * 11 + $yearIndex * 23) % $dayCount];
                    $borrowDate = Carbon::parse($borrowDay);
                    $dueDate = $borrowDate->copy()->addDays(7);

                    if ($isLate) {
                        $lateDays = 1 + ($studentId % 3);
                        $actualReturn = $dueDate->copy()->addDays($lateDays);
                        $fees = $lateDays * 5.00;
                    } else {
                        // Returned on time (or early): no fine.
                        $actualReturn = $dueDate->copy()->subDays($studentId % 3);
                        if ($actualReturn->lt($borrowDate)) {
                            $actualReturn = $dueDate->copy();
                        }
                        $fees = 0.0;
                    }

                    LibraryTransaction::create([
                        'student_id' => $studentId,
                        'book_id' => $book->id,
                        'book_title' => $book->title,
                        'librarian_id' => $librarianId,
                        'borrow_date' => $borrowDate,
                        'return_date' => $dueDate,
                        'returned_at' => $actualReturn,
                        'status' => 'Returned',
                        'condition_at_borrow' => 'Good',
                        'condition_at_return' => 'Good',
                        'late_fee' => $fees,
                        'total_fees' => $fees,
                        'fees_assessed' => $fees > 0,
                    ]);
                    $txnCount++;
                }

                // Clinic: seasonal trend, not flat. Flu-peak months (Jun-Jul, Sep-Jan)
                // run ~15 visits; off months (Aug, Feb-Mar) run ~6; 1-2 referrals
                // per term. Dates are scattered weekdays inside each month.
                if ($nurseId === null) {
                    return;
                }

                $complaints = $this->complaints();
                $fluPool = [0, 6, 8, 19];
                $referralPool = [3, 4, 13];
                $peakMonths = [6, 7, 9, 10, 11, 12, 1];

                $months = $this->yearMonths($year, $isCurrent);
                $candidates = [];
                $termReferrals = ['T1' => 0, 'T2' => 0, 'T3' => 0];

                foreach ($months as $monthIndex => $month) {
                    $monthNum = (int) $month['date']->format('n');
                    $isPeak = in_array($monthNum, $peakMonths, true);
                    $target = $isPeak ? 15 : 6;
                    $monthWeekdays = $month['weekdays'];
                    if (empty($monthWeekdays)) {
                        continue;
                    }
                    $monthDayCount = count($monthWeekdays);
                    $studentCount = count($studentIds);

                    for ($i = 0; $i < $target; $i++) {
                        $studentId = $studentIds[($monthIndex * 15 + $yearIndex * 37 + $i * 7) % $studentCount];
                        $seasonalPick = ($studentId + $monthIndex + $i) % 5;

                        if ($isPeak) {
                            $complaintIdx = $seasonalPick < 4
                                ? $fluPool[($studentId + $i) % count($fluPool)]
                                : ($i * 3 + $monthIndex) % count($complaints);
                        } else {
                            $complaintIdx = $seasonalPick < 4
                                ? ($i * 5 + $monthIndex + 2) % count($complaints)
                                : $fluPool[($studentId + $i) % count($fluPool)];
                        }

                        $day = $monthWeekdays[($studentId * 13 + $i * 11 + $monthIndex * 7) % $monthDayCount];
                        $incident = Carbon::parse($day)->setTime(8 + (($studentId + $i) % 7), ($studentId * 13 + $i * 17) % 60);
                        $term = $monthNum >= 6 && $monthNum <= 8 ? 'T1' : (($monthNum >= 9 && $monthNum <= 11) ? 'T2' : 'T3');
                        if (in_array($complaintIdx, $referralPool, true)) {
                            $termReferrals[$term]++;
                        }

                        $candidates[] = [
                            'student_id' => $studentId,
                            'complaint_idx' => $complaintIdx,
                            'incident' => $incident,
                            'term' => $term,
                        ];
                    }
                }

                // Guarantee 1-2 referrals per term: swap the first non-referral
                // candidate of any term that drew none.
                foreach (['T1', 'T2', 'T3'] as $term) {
                    if (($termReferrals[$term] ?? 0) > 0) {
                        continue;
                    }
                    foreach ($candidates as $idx => $candidate) {
                        if ($candidate['term'] === $term) {
                            $candidates[$idx]['complaint_idx'] = $referralPool[$yearIndex % count($referralPool)];
                            break;
                        }
                    }
                }

                $startYear = (int) substr($year, 0, 4);
                foreach ($candidates as $candidate) {
                    $complaint = $complaints[$candidate['complaint_idx']];

                    // Backfill-only: the same student+complaint in the same school
                    // year is never duplicated; other months/years are untouched.
                    $alreadySeeded = ClinicLog::where('student_id', $candidate['student_id'])
                        ->where('complaint', $complaint['complaint'])
                        ->whereYear('incident_date', '>=', $startYear)
                        ->whereYear('incident_date', '<=', $startYear + 1)
                        ->exists();
                    if ($alreadySeeded) {
                        continue;
                    }

                    ClinicLog::create(array_merge($complaint, [
                        'student_id' => $candidate['student_id'],
                        'nurse_id' => $nurseId,
                        'symptoms' => $complaint['complaint'],
                        'incident_date' => $candidate['incident'],
                        'visit_date' => $candidate['incident']->toDateString(),
                        'notes' => null,
                    ]));
                    $visitCount++;
                }
            });
        }

        // Keep book counts consistent with open loans: a copy that is out on
        // loan is not also shown as available.
        $openByBook = LibraryTransaction::where('status', 'Borrowed')
            ->select('book_id', DB::raw('count(*) as open_count'))
            ->groupBy('book_id')
            ->pluck('open_count', 'book_id');

        foreach ($openByBook as $bookId => $openCount) {
            $book = Book::find($bookId);
            if ($book === null) {
                continue;
            }
            $book->available_quantity = max(0, (int) $book->quantity - (int) $openCount);
            $book->save();
        }

        // Library visits (manual time-in/out log) — every 4th student, across the last 5 days
        $students = Student::where('status', 'enrolled')->orderBy('id')->get();
        foreach ($students as $student) {
            if ($student->id % 4 !== 0) {
                continue;
            }
            // Already seeded (or logged by the librarian UI) → skip: time_in is now()-derived,
            // so re-running on a later day would otherwise duplicate every visit
            if (DB::table('library_visits')->where('student_id', $student->id)->exists()) {
                continue;
            }
            $timeIn = now()->startOfDay()
                ->subDays($student->id % 5)
                ->addHours(8 + ($student->id % 8))
                ->addMinutes(($student->id * 7) % 60);
            $hasOut = $student->id % 3 !== 0;

            DB::table('library_visits')->insert([
                'student_id' => $student->id,
                'librarian_id' => $librarianId,
                'time_in' => $timeIn,
                'time_out' => $hasOut ? $timeIn->copy()->addMinutes(30 + ($student->id % 5) * 10) : null,
                'created_at' => $timeIn,
                'updated_at' => $timeIn,
            ]);
        }

        $this->command->info('Library and clinic seeded: ' . $txnCount . ' transactions, ' . $visitCount . ' clinic visits.');
    }

    /**
     * @return list<string> Weekday dates (Y-m-d) inside the Jun–Mar window.
     */
    private function weekdaysInWindow(string $year, bool $isCurrent): array
    {
        $startYear = (int) substr($year, 0, 4);
        $start = Carbon::create($startYear, 6, 1)?->startOfDay();
        $end = Carbon::create($startYear + 1, 3, 31)?->startOfDay();

        if ($start === null || $end === null) {
            return [];
        }

        if ($isCurrent && $end->isFuture()) {
            $end = Carbon::today();
        }

        if ($end->lt($start)) {
            return [];
        }

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return list<array{date: Carbon, weekdays: list<string>}>
     */
    private function yearMonths(string $year, bool $isCurrent): array
    {
        $startYear = (int) substr($year, 0, 4);
        $sequence = [
            [$startYear, 6], [$startYear, 7], [$startYear, 8],
            [$startYear, 9], [$startYear, 10], [$startYear, 11],
            [$startYear, 12], [$startYear + 1, 1], [$startYear + 1, 2], [$startYear + 1, 3],
        ];

        $today = Carbon::today();
        $months = [];
        foreach ($sequence as [$y, $m]) {
            $first = Carbon::create($y, $m, 1)?->startOfDay();
            if ($first === null) {
                continue;
            }
            if ($isCurrent && $first->greaterThan($today)) {
                continue;
            }
            $last = $first->copy()->endOfMonth()->startOfDay();
            if ($isCurrent && $last->greaterThan($today)) {
                $last = $today->copy()->startOfDay();
            }

            $weekdays = [];
            $cursor = $first->copy();
            while ($cursor->lte($last)) {
                if ($cursor->isWeekday()) {
                    $weekdays[] = $cursor->toDateString();
                }
                $cursor->addDay();
            }

            $months[] = ['date' => $first, 'weekdays' => $weekdays];
        }

        return $months;
    }

    /**
     * @return list<array{complaint: string, diagnosis: ?string, treatment: ?string, referred_to: ?string}>
     */
    private function complaints(): array
    {
        return [
            ['complaint' => 'Headache and mild fever', 'diagnosis' => 'Tension headache with mild fever', 'treatment' => 'Rest, hydration, paracetamol administered', 'referred_to' => null],
            ['complaint' => 'Abdominal pain after lunch', 'diagnosis' => 'Mild indigestion', 'treatment' => 'Antacid given, advised rest in clinic', 'referred_to' => null],
            ['complaint' => 'Scraped knee during PE class', 'diagnosis' => 'Minor abrasion on left knee', 'treatment' => 'Cleaned and bandaged wound', 'referred_to' => null],
            ['complaint' => 'Allergic reaction, skin rash', 'diagnosis' => 'Contact dermatitis', 'treatment' => 'Antihistamine administered', 'referred_to' => 'Dr. Reyes (Barangay Health Center)'],
            ['complaint' => 'Toothache, difficulty eating', 'diagnosis' => 'Dental caries', 'treatment' => 'Pain relief given', 'referred_to' => 'School Dentist'],
            ['complaint' => 'Sprained ankle during basketball', 'diagnosis' => 'Grade 1 ankle sprain', 'treatment' => 'Ice pack applied, bandaged', 'referred_to' => null],
            ['complaint' => 'Persistent cough for 3 days', 'diagnosis' => 'Upper respiratory infection', 'treatment' => 'Cough syrup prescribed', 'referred_to' => null],
            ['complaint' => 'Eye irritation from chemicals in lab', 'diagnosis' => 'Chemical irritation, mild', 'treatment' => 'Eye wash administered', 'referred_to' => null],
            ['complaint' => 'Dizziness during morning assembly', 'diagnosis' => 'Mild dehydration', 'treatment' => 'Oral rehydration salts, rest', 'referred_to' => null],
            ['complaint' => 'Nosebleed, warm weather', 'diagnosis' => 'Epistaxis, mild', 'treatment' => 'Cold compress on nose, pinched bridge', 'referred_to' => null],
            ['complaint' => 'Back pain from carrying heavy bag', 'diagnosis' => 'Muscle strain, lower back', 'treatment' => 'Pain relief cream, stretching exercises advised', 'referred_to' => null],
            ['complaint' => 'Cut finger in arts and crafts', 'diagnosis' => 'Minor laceration on right index finger', 'treatment' => 'Wound cleaned, antiseptic applied, bandaged', 'referred_to' => null],
            ['complaint' => 'Feeling faint during recess', 'diagnosis' => 'Low blood sugar', 'treatment' => 'Given juice and crackers, rested 30 minutes', 'referred_to' => null],
            ['complaint' => 'Ear pain after swimming class', 'diagnosis' => "Swimmer's ear (otitis externa)", 'treatment' => 'Ear drops administered, referred for follow-up', 'referred_to' => 'ENT Specialist Dr. Cruz'],
            ['complaint' => 'Bee sting during outdoor activity', 'diagnosis' => 'Bee sting, mild allergic reaction', 'treatment' => 'Stinger removed, ice pack, antihistamine given', 'referred_to' => null],
            ['complaint' => 'Vomiting after eating cafeteria food', 'diagnosis' => 'Acute gastroenteritis', 'treatment' => 'Oral rehydration, rest, monitored for 2 hours', 'referred_to' => null],
            ['complaint' => 'Wrist pain from writing too much', 'diagnosis' => 'Repetitive strain injury', 'treatment' => 'Wrist brace applied, advised rest and stretching', 'referred_to' => null],
            ['complaint' => 'Sunburn during field trip', 'diagnosis' => 'First-degree sunburn on arms and neck', 'treatment' => 'Aloe vera gel applied, advised sunscreen use', 'referred_to' => null],
            ['complaint' => 'Difficulty breathing, mild asthma', 'diagnosis' => 'Mild asthma attack', 'treatment' => 'Inhaler administered, monitored until stable', 'referred_to' => null],
            ['complaint' => 'Chills and body aches', 'diagnosis' => 'Early signs of flu', 'treatment' => 'Paracetamol given, advised rest at home', 'referred_to' => null],
        ];
    }
}
