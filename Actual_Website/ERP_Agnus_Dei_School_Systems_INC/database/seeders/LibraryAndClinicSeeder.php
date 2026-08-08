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

        // Create varied library transactions — different statuses and conditions
        $borrowers = $students->random(min(15, $students->count()));
        $statuses = ['Borrowed', 'Returned', 'Returned', 'Returned'];

        foreach ($borrowers as $idx => $student) {
            $book = Book::inRandomOrder()->first();
            if (!$book) continue;

            $status = $statuses[$idx % count($statuses)];
            $borrowDate = now()->subDays(rand(1, 45));
            $returnDate = $status === 'Returned' ? $borrowDate->copy()->addDays(rand(3, 14)) : null;
            $conditionAtBorrow = ['Good', 'Good', 'Good', 'Minor Damage'][rand(0, 3)];
            $conditionAtReturn = $status === 'Returned' ? ['Good', 'Good', 'Minor Damage'][rand(0, 2)] : null;

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
                ]
            );
        }

        if (!$nurseId) return;

        // Create varied clinic logs
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
        ];

        $clinicStudents = $students->random(min(10, $students->count()));
        foreach ($clinicStudents as $idx => $student) {
            $complaint = $complaints[$idx % count($complaints)];
            $incidentDate = now()->subDays(rand(0, 14));

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
