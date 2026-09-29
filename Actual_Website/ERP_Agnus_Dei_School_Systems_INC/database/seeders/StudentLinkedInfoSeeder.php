<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Additive follow-up for StudentScatterSeeder: gives every enrolled student the
 * linked activity data the original roster has (library borrows/visits, clinic
 * visits, graduation-fee assignments, admission requirements) plus a few Pending
 * withdrawal requests. Built in memory + chunk-inserted (Supabase round-trips are
 * ~0.8s; the per-row equivalent of this seeder would take ~25 minutes).
 *
 * Idempotent: natural-key existence sets are loaded first; re-runs insert nothing.
 */
class StudentLinkedInfoSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::where('status', 'enrolled')->orderBy('id')->get();
        $librarianId = User::where('role_id', 5)->first()?->id;
        $nurseId = User::where('role_id', 6)->first()?->id;
        $books = Book::orderBy('id')->get();
        $schoolYear = active_school_year();
        $now = now()->toDateTimeString();

        $counts = ['lib_txns' => 0, 'lib_visits' => 0, 'clinic' => 0, 'grad_fees' => 0, 'requirements' => 0, 'withdrawals' => 0];

        // ---------- 1. Library transactions (formulas copied from LibraryAndClinicSeeder) ----------
        if ($librarianId && $books->isNotEmpty()) {
            $existingPairs = [];
            foreach (DB::table('library_transactions')->get(['student_id', 'book_title']) as $t) {
                $existingPairs[$t->student_id . '|' . $t->book_title] = true;
            }

            $txnRows = [];
            $txn = 0;
            foreach ($students as $student) {
                $copies = $student->id % 3 === 0 ? 2 : 1;
                for ($n = 0; $n < $copies; $n++) {
                    $idx = $txn++;
                    $book = $books[($student->id * 7 + $n * 11) % $books->count()];
                    if (isset($existingPairs[$student->id . '|' . $book->title])) continue;

                    $isBorrowed = $idx % 5 === 0;
                    $borrowDate = now()->subDays(1 + (($student->id * 5 + $n * 3) % 60));

                    if ($isBorrowed) {
                        if ($idx % 4 === 0) {
                            $borrowDate = now()->subDays(3 + (($student->id * 5 + $n * 3) % 58));
                            $returnDate = now()->subDays(1 + ($student->id % 10))->max($borrowDate->copy()->addDay());
                        } else {
                            $returnDate = now()->copy()->addDays(1 + ($student->id % 14));
                        }
                        $conditionAtReturn = null;
                        $lateDays = 0;
                    } else {
                        $returnDate = $borrowDate->copy()->addDays(7);
                        $actualReturnDate = $returnDate->copy()->addDays(($student->id + $n) % 5 - 2);
                        $lateDays = max(0, (int) $returnDate->diffInDays($actualReturnDate));
                        $conditionAtReturn = ['Good', 'Good', 'Minor Damage'][($student->id + $n) % 3];
                    }

                    $totalFees = !$isBorrowed && $lateDays > 0 ? $lateDays * 5.00 : 0;

                    $txnRows[] = [
                        'student_id' => $student->id,
                        'book_id' => $book->id,
                        'librarian_id' => $librarianId,
                        'book_title' => $book->title,
                        'borrow_date' => $borrowDate->toDateString(),
                        'return_date' => $returnDate->toDateString(),
                        'status' => $isBorrowed ? 'Borrowed' : 'Returned',
                        'condition_at_borrow' => ['Good', 'Good', 'Good', 'Minor Damage'][($student->id + $n) % 4],
                        'condition_at_return' => $conditionAtReturn,
                        'total_fees' => $totalFees,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($txnRows, 500) as $chunk) DB::table('library_transactions')->insert($chunk);
            $counts['lib_txns'] = count($txnRows);

            // ---------- 2. Library visits (every 4th student) ----------
            $visited = [];
            foreach (DB::table('library_visits')->pluck('student_id') as $id) $visited[$id] = true;

            $visitRows = [];
            foreach ($students as $student) {
                if ($student->id % 4 !== 0 || isset($visited[$student->id])) continue;
                $timeIn = now()->startOfDay()
                    ->subDays($student->id % 5)
                    ->addHours(8 + ($student->id % 8))
                    ->addMinutes(($student->id * 7) % 60);
                $hasOut = $student->id % 3 !== 0;
                $visitRows[] = [
                    'student_id' => $student->id,
                    'librarian_id' => $librarianId,
                    'time_in' => $timeIn->toDateTimeString(),
                    'time_out' => $hasOut ? $timeIn->copy()->addMinutes(30 + ($student->id % 5) * 10)->toDateTimeString() : null,
                    'created_at' => $timeIn->toDateTimeString(),
                    'updated_at' => $timeIn->toDateTimeString(),
                ];
            }
            foreach (array_chunk($visitRows, 500) as $chunk) DB::table('library_visits')->insert($chunk);
            $counts['lib_visits'] = count($visitRows);
        }

        // ---------- 3. Clinic logs (20-condition pool, skip the never-visit group) ----------
        if ($nurseId) {
            $complaints = [
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
                ['complaint' => 'Ear pain after swimming class', 'diagnosis' => 'Swimmer\'s ear (otitis externa)', 'treatment' => 'Ear drops administered, referred for follow-up', 'referred_to' => 'ENT Specialist Dr. Cruz'],
                ['complaint' => 'Bee sting during outdoor activity', 'diagnosis' => 'Bee sting, mild allergic reaction', 'treatment' => 'Stinger removed, ice pack, antihistamine given', 'referred_to' => null],
                ['complaint' => 'Vomiting after eating cafeteria food', 'diagnosis' => 'Acute gastroenteritis', 'treatment' => 'Oral rehydration, rest, monitored for 2 hours', 'referred_to' => null],
                ['complaint' => 'Wrist pain from writing too much', 'diagnosis' => 'Repetitive strain injury', 'treatment' => 'Wrist brace applied, advised rest and stretching', 'referred_to' => null],
                ['complaint' => 'Sunburn during field trip', 'diagnosis' => 'First-degree sunburn on arms and neck', 'treatment' => 'Aloe vera gel applied, advised sunscreen use', 'referred_to' => null],
                ['complaint' => 'Difficulty breathing, mild asthma', 'diagnosis' => 'Mild asthma attack', 'treatment' => 'Inhaler administered, monitored until stable', 'referred_to' => null],
                ['complaint' => 'Chills and body aches', 'diagnosis' => 'Early signs of flu', 'treatment' => 'Paracetamol given, advised rest at home', 'referred_to' => null],
            ];

            $existingClinic = [];
            foreach (DB::table('clinic_logs')->get(['student_id', 'complaint']) as $c) {
                $existingClinic[$c->student_id . '|' . $c->complaint] = true;
            }

            $clinicRows = [];
            foreach ($students as $student) {
                if ($student->id % 7 === 3) continue;
                $visitCount = $student->id % 4 === 1 ? 2 : 1;
                for ($visitNo = 0; $visitNo < $visitCount; $visitNo++) {
                    $complaint = $complaints[($student->id + $visitNo * 7) % count($complaints)];
                    if (isset($existingClinic[$student->id . '|' . $complaint['complaint']])) continue;
                    $incidentDate = now()->startOfDay()
                        ->subDays((($student->id * 2) % 55) + $visitNo * 5)
                        ->addHours(8 + (($student->id + $visitNo) % 6))
                        ->addMinutes(($student->id * 13 + $visitNo * 17) % 60);
                    $clinicRows[] = [
                        'student_id' => $student->id,
                        'nurse_id' => $nurseId,
                        'symptoms' => $complaint['complaint'],
                        'complaint' => $complaint['complaint'],
                        'diagnosis' => $complaint['diagnosis'],
                        'treatment' => $complaint['treatment'],
                        'referred_to' => $complaint['referred_to'],
                        'incident_date' => $incidentDate->toDateTimeString(),
                        'visit_date' => $incidentDate->toDateString(),
                        'notes' => $visitNo > 0 ? 'Follow-up visit.' : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($clinicRows, 500) as $chunk) DB::table('clinic_logs')->insert($chunk);
            $counts['clinic'] = count($clinicRows);
        }

        // ---------- 4. Graduation fees: every Active Grade 10 / Grade 12 enrollment ----------
        foreach ([['Grade 10', 1500.00], ['Grade 12', 2500.00]] as [$grade, $fee]) {
            $graduationFee = DB::table('graduation_fees')
                ->where('grade_level', $grade)
                ->where('school_year', $schoolYear)
                ->first();
            if (!$graduationFee) continue;

            $assigned = [];
            foreach (DB::table('student_graduation_fees')->where('graduation_fee_id', $graduationFee->id)->pluck('student_id') as $id) {
                $assigned[$id] = true;
            }

            $enrollments = DB::select(
                "select e.id, e.student_id from enrollments e
                 join sections s on s.id = e.section_id
                 where e.status = 'Active' and s.grade_level = ? and s.is_active = true",
                [$grade]
            );

            $gradRows = [];
            foreach ($enrollments as $enrollment) {
                if (isset($assigned[$enrollment->student_id])) continue;
                $gradRows[] = [
                    'student_id' => $enrollment->student_id,
                    'enrollment_id' => $enrollment->id,
                    'graduation_fee_id' => $graduationFee->id,
                    'amount' => $fee,
                    'paid' => $enrollment->student_id % 3 === 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            foreach (array_chunk($gradRows, 500) as $chunk) DB::table('student_graduation_fees')->insert($chunk);
            $counts['grad_fees'] += count($gradRows);
        }

        // ---------- 5. Admission requirements: 3 document rows per admission ----------
        $documentTypes = ['PSA Birth Certificate', 'Form 138', 'Good Moral'];
        $statuses = ['Verified', 'Verified', 'Under Review'];

        $existingReqs = [];
        foreach (DB::table('requirements')->get(['admission_id', 'document_type']) as $r) {
            $existingReqs[$r->admission_id . '|' . $r->document_type] = true;
        }

        $reqRows = [];
        $admissions = DB::table('admissions')->orderBy('id')->get();
        foreach ($admissions as $admission) {
            foreach ($documentTypes as $docIndex => $documentType) {
                if (isset($existingReqs[$admission->id . '|' . $documentType])) continue;
                $reqRows[] = [
                    'admission_id' => $admission->id,
                    'document_type' => $documentType,
                    'original_filename' => strtolower(str_replace(' ', '_', $documentType)) . '.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 96_000 + ($admission->id * 137) + ($docIndex * 41),
                    'status' => $statuses[($admission->id + $docIndex) % count($statuses)],
                    'created_at' => now()->subDays(10 - min($docIndex, 9))->toDateTimeString(),
                    'updated_at' => now()->subDays(10 - min($docIndex, 9))->toDateTimeString(),
                ];
            }
        }
        foreach (array_chunk($reqRows, 500) as $chunk) DB::table('requirements')->insert($chunk);
        $counts['requirements'] = count($reqRows);

        // ---------- 6. Five Pending withdrawal requests (registrar queue variety) ----------
        $pendingCount = DB::table('withdrawals')->where('status', 'Pending')->count();
        $needed = max(0, 5 - $pendingCount);
        $candidates = $needed > 0 ? DB::select(
            "select e.id, e.student_id from enrollments e
             where e.status = 'Active'
               and e.student_id > ?
               and not exists (select 1 from withdrawals w where w.enrollment_id = e.id)
             order by e.id limit {$needed}",
            [StudentScatterSeeder::BASELINE_STUDENT_ID]
        ) : [];
        $reasons = [
            'Relocation — family transferring to another city, records being processed.',
            'Family Reasons — pending exit interview with the registrar.',
            'Health Reasons — medical leave being evaluated by the school clinic.',
            'Financial Issues — discussing payment options before final decision.',
            'Change of Mind — student reconsidered, awaiting registrar feedback.',
        ];
        foreach ($candidates as $i => $enrollment) {
            DB::table('withdrawals')->insert([
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'reason' => $reasons[$i] ?? 'Pending request.',
                'status' => 'Pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $counts['withdrawals']++;
        }

        $this->command->info(sprintf(
            'Linked info seeded: %d library txns, %d library visits, %d clinic logs, %d grad-fee assignments, %d requirement rows, %d pending withdrawals.',
            $counts['lib_txns'], $counts['lib_visits'], $counts['clinic'],
            $counts['grad_fees'], $counts['requirements'], $counts['withdrawals']
        ));
    }
}
