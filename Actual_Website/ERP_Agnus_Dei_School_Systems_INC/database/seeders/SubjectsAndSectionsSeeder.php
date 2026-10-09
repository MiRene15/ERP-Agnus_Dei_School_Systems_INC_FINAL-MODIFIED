<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Admission;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeUnlockRequest;
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
            'Kinder' => ['St. Agnes', 'St. Clare'],
            'Grade 1' => ['St. Francis', 'St. Dominic'],
            'Grade 2' => ['St. Catherine', 'St. Therese'],
            'Grade 3' => ['St. Augustine', 'St. Benedict'],
            'Grade 4' => ['St. Joseph', 'St. Michael'],
            'Grade 5' => ['St. John', 'St. Paul'],
            'Grade 6' => ['St. Peter', 'St. Andrew'],
            'Grade 7' => ['Charity', 'Hope'],
            'Grade 8' => ['Faith', 'Love'],
            'Grade 9' => ['Wisdom', 'Courage'],
            'Grade 10' => ['Justice', 'Temperance'],
            'Grade 11' => ['STEM - St. Thomas Aquinas', 'ABM - St. Matthew', 'HUMSS - St. Augustine', 'GAS - St. Scholastica'],
            'Grade 12' => ['STEM - St. Albert', 'ABM - St. Luke', 'HUMSS - St. Jerome', 'GAS - St. Benedict'],
        ];

        // Canonical saint-name list is the single source of truth. All matching
        // below is name-keyed (never positional) so re-runs cannot scramble
        // e.g. Justice <-> Temperance or St. Francis <-> St. Dominic.
        $legacyNamesByGrade = [
            'Kinder' => ['A' => 'St. Agnes', 'B' => 'St. Clare'],
            'Grade 1' => ['A' => 'St. Francis', 'B' => 'St. Dominic'],
            'Grade 2' => ['A' => 'St. Catherine', 'B' => 'St. Therese'],
            'Grade 3' => ['A' => 'St. Augustine', 'B' => 'St. Benedict'],
            'Grade 4' => ['A' => 'St. Joseph', 'B' => 'St. Michael'],
            'Grade 5' => ['A' => 'St. John', 'B' => 'St. Paul'],
            'Grade 6' => ['A' => 'St. Peter', 'B' => 'St. Andrew'],
            'Grade 7' => ['A' => 'Charity', 'B' => 'Hope'],
            'Grade 8' => ['A' => 'Faith', 'B' => 'Love'],
            'Grade 9' => ['A' => 'Wisdom', 'B' => 'Courage'],
            'Grade 10' => ['A' => 'Justice', 'B' => 'Temperance'],
            'Grade 11' => ['STEM-A' => 'STEM - St. Thomas Aquinas', 'ABM-A' => 'ABM - St. Matthew', 'HUMSS-A' => 'HUMSS - St. Augustine', 'GAS-A' => 'GAS - St. Scholastica'],
            'Grade 12' => ['STEM-A' => 'STEM - St. Albert', 'ABM-A' => 'ABM - St. Luke', 'HUMSS-A' => 'HUMSS - St. Jerome', 'GAS-A' => 'GAS - St. Benedict'],
        ];

        foreach ($sectionData as $gradeLevel => $sections) {
            // 1) Migrate legacy generic names to saint names by explicit map.
            // Only renames when the saint name does not already exist, so a
            // re-run never duplicates or swaps two existing saint names.
            foreach (($legacyNamesByGrade[$gradeLevel] ?? []) as $oldName => $newName) {
                $old = Section::where('grade_level', $gradeLevel)->where('section_name', $oldName)->first();
                if ($old === null) continue;
                $newExists = Section::where('grade_level', $gradeLevel)->where('section_name', $newName)->exists();
                if (!$newExists) {
                    $old->update(['section_name' => $newName, 'is_active' => true]);
                }
            }

            // 2) Ensure every canonical saint name exists (idempotent, name-keyed).
            foreach ($sections as $sectionName) {
                Section::updateOrCreate(
                    ['grade_level' => $gradeLevel, 'section_name' => $sectionName],
                    ['is_active' => true]
                );
            }

            // 3) Retire anything not on the canonical list. Delete only when
            // nothing links to it (no enrollments, no classes carrying its
            // grade+section text); otherwise switch off from new picks so no
            // student, grade, or class history is orphaned.
            $extras = Section::where('grade_level', $gradeLevel)->whereNotIn('section_name', $sections)->get();
            foreach ($extras as $extra) {
                $hasEnrollments = Enrollment::where('section_id', $extra->id)->exists();
                $hasClasses = Classes::where('grade_level', $gradeLevel)->where('section', $extra->section_name)->exists();
                if (!$hasEnrollments && !$hasClasses) {
                    $extra->delete();
                } else {
                    $extra->update(['is_active' => false]);
                }
            }
        }

        // SHS unscramble-heal: the old positional rename scrambled section names
        // underneath existing enrollments (ABM students reading under STEM sections,
        // etc.). Move each mismatched enrollment to the least-loaded active section
        // matching its strand, carrying history to same-subject twin classes.
        // Already-correct rows are untouched so this is a no-op on healthy data.
        DB::transaction(function () use ($sectionData) {
            $shsGrades = ['Grade 11', 'Grade 12'];
            $sectionLoad = [];
            foreach (Section::where('is_active', true)->whereIn('grade_level', $shsGrades)->get() as $sec) {
                $sectionLoad[$sec->id] = Enrollment::where('section_id', $sec->id)->count();
            }

            $mismatched = Enrollment::with('section')
                ->whereHas('section', fn($q) => $q->whereIn('grade_level', $shsGrades))
                ->get()
                ->filter(function ($e) {
                    if (!$e->section) return false;
                    $strand = trim((string) $e->strand);
                    if ($strand === '') return false;
                    $prefix = trim(explode('-', $e->section->section_name)[0]);
                    return $strand !== $prefix;
                });

            $moved = 0;
            foreach ($mismatched as $enrollment) {
                $oldSection = $enrollment->section;
                $grade = $oldSection->grade_level;
                $strand = trim((string) $enrollment->strand);

                $candidates = Section::where('grade_level', $grade)
                    ->where('is_active', true)
                    ->where('section_name', 'LIKE', "{$strand}%")
                    ->get();

                if ($candidates->isEmpty()) continue;

                $target = $candidates
                    ->map(fn($s) => ['section' => $s, 'load' => $sectionLoad[$s->id] ?? 0])
                    ->sortBy('load')
                    ->first()['section'];

                $oldSectionId = $oldSection->id;
                $enrollment->update(['section_id' => $target->id]);
                $sectionLoad[$oldSectionId]--;
                $sectionLoad[$target->id]++;
                $moved++;

                // Carry class links to twin classes in the new section
                $oldClasses = Classes::where('grade_level', $grade)
                    ->where('section', $oldSection->section_name)
                    ->get();
                $newClasses = Classes::where('grade_level', $grade)
                    ->where('section', $target->section_name)
                    ->get();

                foreach ($oldClasses as $oldClass) {
                    $twin = $newClasses->firstWhere('subject_id', $oldClass->subject_id);
                    if (!$twin) continue;

                    DB::table('enrollment_subject')
                        ->where('enrollment_id', $enrollment->id)
                        ->where('class_id', $oldClass->id)
                        ->update(['class_id' => $twin->id]);

                    Grade::where('enrollment_id', $enrollment->id)
                        ->where('class_id', $oldClass->id)
                        ->update(['class_id' => $twin->id]);

                    Assessment::where('enrollment_id', $enrollment->id)
                        ->where('class_id', $oldClass->id)
                        ->update(['class_id' => $twin->id]);

                    Attendance::where('enrollment_id', $enrollment->id)
                        ->where('class_id', $oldClass->id)
                        ->update(['class_id' => $twin->id]);

                    GradeUnlockRequest::where('class_id', $oldClass->id)
                        ->update(['class_id' => $twin->id]);
                }
            }

            if ($moved > 0) {
                $this->info("SHS unscramble-heal: moved {$moved} enrollment(s) to strand-matching sections.");
            }

            // Trim trailing-space strands where they sit
            $trimmedEnrollments = Enrollment::where('strand', 'like', '% ')->count();
            Enrollment::where('strand', 'like', '% ')->update(['strand' => DB::raw('TRIM(strand)')]);
            $trimmedAdmissions = Admission::where('strand', 'like', '% ')->count();
            Admission::where('strand', 'like', '% ')->update(['strand' => DB::raw('TRIM(strand)')]);
            if ($trimmedEnrollments > 0 || $trimmedAdmissions > 0) {
                $this->info("Trimmed trailing-space strands: {$trimmedEnrollments} enrollment(s), {$trimmedAdmissions} admission(s).");
            }
        });

        // Ensure no leftover generic-named active sections (A,B,STEM-A etc) remain after rename
        $allNewNames = collect($sectionData)->flatten()->toArray();
        Section::where('is_active', true)->whereNotIn('section_name', $allNewNames)->update(['is_active' => false]);

        // Assign advisers round-robin from active teachers (role 4)
        $teacherIds = \App\Models\User::where('role_id', 4)->where('status', 'active')->pluck('id')->toArray();
        if (!empty($teacherIds)) {
            $sectionsNeeding = Section::where('is_active', true)->whereNull('adviser_id')->orderBy('grade_level')->orderBy('section_name')->get();
            // Also rebalance if some have null due to rename, assign in order
            foreach ($sectionsNeeding as $idx => $section) {
                $section->update(['adviser_id' => $teacherIds[$idx % count($teacherIds)]]);
            }
            // Ensure every active section has an adviser (even if already set, verify valid)
            $allActive = Section::where('is_active', true)->get();
            foreach ($allActive as $idx => $sec) {
                if (!$sec->adviser_id || !in_array($sec->adviser_id, $teacherIds, true)) {
                    $sec->update(['adviser_id' => $teacherIds[$idx % count($teacherIds)]]);
                }
            }
        }
    }

    private function warn(string $message): void
    {
        if ($this->command) $this->command->warn($message);
    }

    private function info(string $message): void
    {
        if ($this->command) $this->command->info($message);
    }
}
