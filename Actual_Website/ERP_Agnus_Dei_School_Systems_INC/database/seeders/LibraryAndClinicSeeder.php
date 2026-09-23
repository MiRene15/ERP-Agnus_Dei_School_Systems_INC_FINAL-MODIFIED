<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ClinicLog;
use App\Models\LibraryTransaction;
use App\Models\Student;
use Illuminate\Database\Seeder;

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
        $students = Student::where('status', 'enrolled')->get();
        $librarianId = \App\Models\User::where('role_id', 5)->first()?->id;
        $nurseId = \App\Models\User::where('role_id', 6)->first()?->id;

        if ($students->isEmpty() || !$librarianId) {
            return;
        }

        // Create varied library transactions — 35 total with mix of statuses and conditions
        $borrowers = $students->random(min(35, $students->count()));
        $statuses = ['Borrowed', 'Returned', 'Returned', 'Returned', 'Returned'];

        foreach ($borrowers as $idx => $student) {
            $book = Book::inRandomOrder()->first();
            if (!$book) continue;

            $status = $statuses[$idx % count($statuses)];
            $borrowDate = now()->subDays(rand(1, 60));

            if ($status === 'Returned') {
                // Some returned on time, some returned late
                $returnDate = $borrowDate->copy()->addDays(rand(3, 14));
                $actualReturnDate = $returnDate->copy()->addDays(rand(0, 7));
                $lateDays = max(0, $actualReturnDate->diffInDays($returnDate) * -1);
            } else {
                // Currently borrowed — some overdue, some not
                $returnDate = $idx % 4 === 0
                    ? now()->copy()->subDays(rand(1, 10)) // overdue
                    : now()->copy()->addDays(rand(1, 14)); // not yet due
                $actualReturnDate = null;
                $lateDays = 0;
            }

            $conditionAtBorrow = ['Good', 'Good', 'Good', 'Minor Damage'][rand(0, 3)];
            $conditionAtReturn = $status === 'Returned' ? ['Good', 'Good', 'Minor Damage'][rand(0, 2)] : null;

            $totalFees = 0;
            if ($status === 'Returned' && $lateDays > 0) {
                $totalFees = $lateDays * 5.00; // late fee
            }

            LibraryTransaction::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'book_title' => $book->title,
                ],
                [
                    'book_id' => $book->id,
                    'librarian_id' => $librarianId,
                    'borrow_date' => $borrowDate,
                    'return_date' => $returnDate,
                    'status' => $status,
                    'condition_at_borrow' => $conditionAtBorrow,
                    'condition_at_return' => $conditionAtReturn,
                    'actual_return_date' => $actualReturnDate,
                    'late_days' => $lateDays,
                    'total_fees' => $totalFees,
                ]
            );
        }

        if (!$nurseId) return;

        // Create varied clinic logs — 20 total with varied complaints and treatments
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
            // Additional complaints (11-20)
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

        $clinicStudents = $students->random(min(20, $students->count()));
        foreach ($clinicStudents as $idx => $student) {
            $complaint = $complaints[$idx % count($complaints)];
            $incidentDate = now()->subDays(rand(0, 30));

            ClinicLog::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'incident_date' => $incidentDate,
                    'complaint' => $complaint['complaint'],
                ],
                array_merge($complaint, [
                    'nurse_id' => $nurseId,
                    'symptoms' => $complaint['complaint'],
                    'visit_date' => $incidentDate,
                    'notes' => null,
                ])
            );
        }
    }
}
