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
        $books = [
            ['title' => 'Mathematics for the Modern World', 'author' => 'Maria Santos', 'isbn' => '978-621-001-001-1', 'publisher' => 'Phoenix Publishing', 'year_published' => 2022, 'quantity' => 20, 'available_quantity' => 17],
            ['title' => 'Filipino Heritage and Culture', 'author' => 'Jose Reyes', 'isbn' => '978-621-001-002-8', 'publisher' => 'Vibal Group', 'year_published' => 2021, 'quantity' => 15, 'available_quantity' => 13],
            ['title' => 'Science and Technology Today', 'author' => 'Ana Cruz', 'isbn' => '978-621-001-003-5', 'publisher' => 'C&E Publishing', 'year_published' => 2023, 'quantity' => 25, 'available_quantity' => 22],
            ['title' => 'Understanding Philippine History', 'author' => 'Paolo Garcia', 'isbn' => '978-621-001-004-2', 'publisher' => 'Rex Book Store', 'year_published' => 2020, 'quantity' => 10, 'available_quantity' => 9],
            ['title' => 'English Communication Arts', 'author' => 'Liza Mendoza', 'isbn' => '978-621-001-005-9', 'publisher' => 'Sib Publishing', 'year_published' => 2022, 'quantity' => 18, 'available_quantity' => 15],
            ['title' => 'Values Education for Youth', 'author' => 'Pedro Aquino', 'isbn' => '978-621-001-006-6', 'publisher' => 'Lorimar Publishing', 'year_published' => 2023, 'quantity' => 12, 'available_quantity' => 11],
            ['title' => 'Komunikasyon sa Wikang Filipino', 'author' => 'Bienvenido Ramos', 'isbn' => '978-621-001-007-3', 'publisher' => 'Phoenix Publishing', 'year_published' => 2021, 'quantity' => 14, 'available_quantity' => 14],
            ['title' => 'Earth and Life Science', 'author' => 'Carlo Dela Cruz', 'isbn' => '978-621-001-008-0', 'publisher' => 'C&E Publishing', 'year_published' => 2022, 'quantity' => 16, 'available_quantity' => 14],
        ];

        foreach ($books as $book) {
            Book::updateOrCreate(['isbn' => $book['isbn']], $book);
        }

        $students = Student::where('status', 'enrolled')->get();
        $librarianId = \App\Models\User::where('role_id', 6)->first()?->id;
        $nurseId = \App\Models\User::where('role_id', 7)->first()?->id;

        if ($students->isEmpty() || !$librarianId) {
            return;
        }

        $borrowers = $students->random(min(8, $students->count()));

        foreach ($borrowers as $idx => $student) {
            $book = Book::inRandomOrder()->first();
            if (!$book) continue;

            $isReturned = $idx % 3 !== 0;
            $borrowDate = now()->subDays(rand(1, 30));
            $returnDate = $isReturned ? $borrowDate->copy()->addDays(rand(3, 14)) : null;
            $status = $isReturned ? 'Returned' : 'Borrowed';

            LibraryTransaction::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'book_title' => $book->title,
                ],
                [
                    'librarian_id' => $librarianId,
                    'borrow_date' => $borrowDate,
                    'return_date' => $returnDate,
                    'status' => $status,
                ]
            );
        }

        if (!$nurseId) return;

        $complaints = [
            ['complaint' => 'Headache and mild fever', 'diagnosis' => 'Tension headache with mild fever', 'treatment' => 'Rest, hydration, paracetamol administered', 'referred_to' => null],
            ['complaint' => 'Abdominal pain after lunch', 'diagnosis' => 'Mild indigestion', 'treatment' => 'Antacid given, advised rest in clinic', 'referred_to' => null],
            ['complaint' => 'Scraped knee during PE class', 'diagnosis' => 'Minor abrasion on left knee', 'treatment' => 'Cleaned and bandaged wound', 'referred_to' => null],
            ['complaint' => 'Allergic reaction, skin rash', 'diagnosis' => 'Contact dermatitis', 'treatment' => 'Antihistamine administered', 'referred_to' => 'Dr. Reyes (Barangay Health Center)'],
            ['complaint' => 'Toothache, difficulty eating', 'diagnosis' => 'Dental caries', 'treatment' => 'Pain relief given', 'referred_to' => 'School Dentist'],
            ['complaint' => 'Sprained ankle during basketball', 'diagnosis' => 'Grade 1 ankle sprain', 'treatment' => 'Ice pack applied, bandaged', 'referred_to' => null],
            ['complaint' => 'Persistent cough for 3 days', 'diagnosis' => 'Upper respiratory infection', 'treatment' => 'Cough syrup prescribed', 'referred_to' => null],
            ['complaint' => 'Eye irritation from chemicals in lab', 'diagnosis' => 'Chemical irritation, mild', 'treatment' => 'Eye wash administered', 'referred_to' => null],
        ];

        $clinicStudents = $students->random(min(6, $students->count()));
        foreach ($clinicStudents as $idx => $student) {
            $complaint = $complaints[$idx % count($complaints)];
            $incidentDate = now()->subDays(rand(0, 7));

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
