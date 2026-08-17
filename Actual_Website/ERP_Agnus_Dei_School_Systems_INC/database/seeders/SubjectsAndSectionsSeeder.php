<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Section;

class SubjectsAndSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            // Kinder
            ['subject_code' => 'K-ENG', 'name' => 'English', 'grade_level' => 'Kinder', 'category' => 'Core'],
            ['subject_code' => 'K-MAT', 'name' => 'Mathematics', 'grade_level' => 'Kinder', 'category' => 'Core'],
            ['subject_code' => 'K-SCI', 'name' => 'Science', 'grade_level' => 'Kinder', 'category' => 'Core'],
            ['subject_code' => 'K-READ', 'name' => 'Reading', 'grade_level' => 'Kinder', 'category' => 'Core'],
            ['subject_code' => 'K-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Kinder', 'category' => 'Core'],
            ['subject_code' => 'K-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Kinder', 'category' => 'Core'],

            // Grade 1-6 (Elementary)
            ['subject_code' => 'G1-ENG', 'name' => 'English', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            ['subject_code' => 'G1-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            ['subject_code' => 'G1-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            ['subject_code' => 'G1-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            ['subject_code' => 'G1-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            ['subject_code' => 'G1-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 1', 'category' => 'Core'],

            ['subject_code' => 'G2-ENG', 'name' => 'English', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            ['subject_code' => 'G2-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            ['subject_code' => 'G2-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            ['subject_code' => 'G2-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            ['subject_code' => 'G2-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            ['subject_code' => 'G2-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 2', 'category' => 'Core'],

            ['subject_code' => 'G3-ENG', 'name' => 'English', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-SCI', 'name' => 'Science', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            ['subject_code' => 'G3-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 3', 'category' => 'Core'],

            ['subject_code' => 'G4-ENG', 'name' => 'English', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-SCI', 'name' => 'Science', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            ['subject_code' => 'G4-EPP', 'name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 4', 'category' => 'Core'],

            ['subject_code' => 'G5-ENG', 'name' => 'English', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-SCI', 'name' => 'Science', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            ['subject_code' => 'G5-EPP', 'name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 5', 'category' => 'Core'],

            ['subject_code' => 'G6-ENG', 'name' => 'English', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-SCI', 'name' => 'Science', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            ['subject_code' => 'G6-EPP', 'name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 6', 'category' => 'Core'],

            // Grade 7-10 (Junior High)
            ['subject_code' => 'G7-ENG', 'name' => 'English', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-SCI', 'name' => 'Science', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            ['subject_code' => 'G7-TLE', 'name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 7', 'category' => 'Core'],

            ['subject_code' => 'G8-ENG', 'name' => 'English', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-SCI', 'name' => 'Science', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            ['subject_code' => 'G8-TLE', 'name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 8', 'category' => 'Core'],

            ['subject_code' => 'G9-ENG', 'name' => 'English', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-SCI', 'name' => 'Science', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            ['subject_code' => 'G9-TLE', 'name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 9', 'category' => 'Core'],

            ['subject_code' => 'G10-ENG', 'name' => 'English', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-FIL', 'name' => 'Filipino', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-MAT', 'name' => 'Mathematics', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-SCI', 'name' => 'Science', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-AP', 'name' => 'Araling Panlipunan', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-ESP', 'name' => 'Edukasyon sa Pagkatao', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-MAPEH', 'name' => 'MAPEH', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            ['subject_code' => 'G10-TLE', 'name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 10', 'category' => 'Core'],

            // Senior High - Common Core
            ['subject_code' => 'SHS-OC', 'name' => 'Oral Communication', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-RW', 'name' => 'Reading and Writing Skills', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-GMATH', 'name' => 'General Mathematics', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-ELS', 'name' => 'Earth and Life Science', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-PD', 'name' => 'Personal Development', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-PEH', 'name' => 'Physical Education and Health', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-EAPP', 'name' => 'Empowerment Technologies', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-PR2', 'name' => 'Research 2', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-EMTECH', 'name' => 'Ethics', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-III', 'name' => 'Inquiry, Immersion, and Integration', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-ENTREP', 'name' => 'Entrepreneurship', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-FPL', 'name' => 'Filipino sa Piling Larangan', 'grade_level' => 'Grade 12', 'category' => 'Core'],
            ['subject_code' => 'SHS-MIL', 'name' => 'Media and Information Literacy', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-UCSP', 'name' => 'Understanding Culture, Society, and Politics', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-21CL', 'name' => '21st Century Literature', 'grade_level' => 'Grade 11', 'category' => 'Core'],
            ['subject_code' => 'SHS-PR1', 'name' => 'Research 1', 'grade_level' => 'Grade 11', 'category' => 'Core'],

            // STEM Specialized
            ['subject_code' => 'STEM-PCAL', 'name' => 'Pre-Calculus', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'STEM-BCAL', 'name' => 'Basic Calculus', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'STEM-BIO1', 'name' => 'Biology 1', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],
            ['subject_code' => 'STEM-CHEM1', 'name' => 'Chemistry 1', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],
            ['subject_code' => 'STEM-PHY1', 'name' => 'Physics 1', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],

            // ABM Specialized
            ['subject_code' => 'ABM-BMATH', 'name' => 'Business Math', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'ABM-OAM', 'name' => 'Organization and Management', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'ABM-FABM1', 'name' => 'Fundamentals of Accountancy, Business, and Management 1', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'ABM-FABM2', 'name' => 'Fundamentals of Accountancy, Business, and Management 2', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],

            // HUMSS Specialized
            ['subject_code' => 'HUMSS-DISS', 'name' => 'Disciplines and Ideas in the Social Sciences', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'HUMSS-DIASS', 'name' => 'Disciplines and Ideas in the Applied Social Sciences', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'HUMSS-CREW', 'name' => 'Creative Writing', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],
            ['subject_code' => 'HUMSS-TNCT', 'name' => 'Trends, Networks, and Critical Thinking', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],

            // GAS Specialized
            ['subject_code' => 'GAS-HGP', 'name' => 'Human Geography', 'grade_level' => 'Grade 11', 'category' => 'Specialized'],
            ['subject_code' => 'GAS-ORG', 'name' => 'Organizational Management', 'grade_level' => 'Grade 12', 'category' => 'Specialized'],
        ];

        foreach ($subjects as $s) {
            Subject::updateOrCreate(['subject_code' => $s['subject_code']], $s);
        }

        $sectionData = [
            'Kinder' => ['A', 'B'],
            'Grade 1' => ['A', 'B'],
            'Grade 2' => ['A', 'B'],
            'Grade 3' => ['A', 'B'],
            'Grade 4' => ['A', 'B'],
            'Grade 5' => ['A', 'B'],
            'Grade 6' => ['A', 'B'],
            'Grade 7' => ['A', 'B'],
            'Grade 8' => ['A', 'B'],
            'Grade 9' => ['A', 'B'],
            'Grade 10' => ['A', 'B'],
            'Grade 11' => ['STEM-A', 'ABM-A', 'HUMSS-A', 'GAS-A'],
            'Grade 12' => ['STEM-A', 'ABM-A', 'HUMSS-A', 'GAS-A'],
        ];

        foreach ($sectionData as $gradeLevel => $sections) {
            foreach ($sections as $sectionName) {
                Section::updateOrCreate(
                    ['grade_level' => $gradeLevel, 'section_name' => $sectionName],
                    ['is_active' => true]
                );
            }
        }
    }
}
