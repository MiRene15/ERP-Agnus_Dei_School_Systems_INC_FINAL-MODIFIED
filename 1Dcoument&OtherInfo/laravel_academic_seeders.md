# Comprehensive System Seeders — Agnus Dei School Systems, Inc.

> Last updated: Aug 14, 2026

---

## Seeder Execution Order

Defined in `database/seeders/DatabaseSeeder.php`:

```php
$this->call([
    SettingsSeeder::class,               // 1. Settings (must be first — active_school_year() depends on this)
    SystemRolesAndStaffSeeder::class,     // 2. Roles, admin/staff, teachers with teacher profiles
    SubjectsAndSectionsSeeder::class,     // 3. K-12 + SHS subjects, sections per grade
    TeachersClassesSchedulesSeeder::class, // 4. Classes, schedules, teacher assignments
    FeeSchedulesSeeder::class,            // 5. Fee schedules per grade/term
    StudentsAndFeesSeeder::class,         // 6. Students, admissions, enrollments, ledgers, payments
    GradesAssessmentsSeeder::class,       // 7. Assessments + computed grades
    LibraryAndClinicSeeder::class,        // 8. Books, library transactions, clinic logs
    AnnouncementsTableSeeder::class,      // 9. Announcements & events
    FixArNumbersSeeder::class,            // 10. AR numbers for payments
]);
```

---

## 1. SettingsSeeder

Seeds core system settings into the `settings` table.

| Key | Value |
|-----|-------|
| `school_name` | Agnus Dei School Systems Inc. |
| `school_address` | Brgy. Catmon, Pandan, Antique |
| `school_year` | 2026-2027 |
| `current_term` | 1st Term |
| `contact_email` | info@agnusdei.edu.ph |
| `contact_phone` | (036) 279-0000 |

---

## 2. SystemRolesAndStaffSeeder

### Roles
| ID | Name |
|----|------|
| 1 | Admin |
| 2 | Registrar |
| 3 | Cashier |
| 4 | Teacher |
| 5 | Librarian |
| 6 | Nurse |
| 7 | Student |
| 8 | Principal |
| 9 | Directress |

### Accounts Created
| Email | Name | Role |
|-------|------|------|
| admin@agnusdei.local | System Admin | Admin (1) |
| registrar@agnusdei.local | Head Registrar | Registrar (2) |
| cashier1@agnusdei.local | Cashier Window 1 | Cashier (3) |
| cashier2@agnusdei.local | Cashier Window 2 | Cashier (3) |
| library@agnusdei.local | Head Librarian | Librarian (5) |
| clinic@agnusdei.local | School Nurse | Nurse (6) |
| principal@agnusdei.local | School Principal | Principal (8) |
| directress@agnusdei.local | School Directress | Directress (9) |

### Teachers (20)
| Teacher # | Name | Department | Email |
|-----------|------|------------|-------|
| TCH-0001 | Maria Santos | Elementary | maria.santos@agnusdei.local |
| TCH-0002 | Jose Reyes | Elementary | jose.reyes@agnusdei.local |
| TCH-0003 | Ana Cruz | Elementary | ana.cruz@agnusdei.local |
| TCH-0004 | Paolo Garcia | Elementary | paolo.garcia@agnusdei.local |
| TCH-0005 | Rosa Villanueva | Elementary | rosa.villanueva@agnusdei.local |
| TCH-0006 | Daniel Mercado | Elementary | daniel.mercado@agnusdei.local |
| TCH-0007 | Liza Mendoza | JHS | liza.mendoza@agnusdei.local |
| TCH-0008 | Mark Torres | JHS | mark.torres@agnusdei.local |
| TCH-0009 | Rina Flores | JHS | rina.flores@agnusdei.local |
| TCH-0010 | Dennis Aquino | JHS | dennis.aquino@agnusdei.local |
| TCH-0011 | Grace Domingo | JHS | grace.domingo@agnusdei.local |
| TCH-0012 | Carlo Bautista | JHS | carlo.bautista@agnusdei.local |
| TCH-0013 | Carla Navarro | SHS | carla.navarro@agnusdei.local |
| TCH-0014 | Vincent Luna | SHS | vincent.luna@agnusdei.local |
| TCH-0015 | Sheila Ramos | SHS | sheila.ramos@agnusdei.local |
| TCH-0016 | Adrian Castro | SHS | adrian.castro@agnusdei.local |
| TCH-0017 | Elaine Sy | SHS | elaine.sy@agnusdei.local |
| TCH-0018 | Patrick Lopez | SHS | patrick.lopez@agnusdei.local |
| TCH-0019 | Teresa Natividad | Elementary | teresa.natividad@agnusdei.local |
| TCH-0020 | Nico Salazar | Elementary | nico.salazar@agnusdei.local |

**Password for all accounts:** `Agnus2026!`

---

## 3. SubjectsAndSectionsSeeder

### Subjects
- **Kinder**: K-ENG, K-MAT, K-SCI, K-READ, K-MAPEH, K-ESP
- **Grade 1–6**: Core subjects (ENG, FIL, MAT, SCI, AP, ESP, MAPEH, EPP)
- **Grade 7–10**: Core subjects (ENG, FIL, MAT, SCI, AP, ESP, MAPEH, TLE)
- **Grade 11–12 (SHS)**: Specialized strands (STEM, ABM, HUMSS, GAS) + Core (OC, RW, EAPP, etc.)

### Sections per Grade Level
- **Kinder–Grade 10**: 1 section each (e.g., "A")
- **Grade 11–12**: Strand sections (STEM-A, ABM-A, HUMSS-A, GAS-A)

---

## 4. TeachersClassesSchedulesSeeder

### Algorithm
1. For each grade level + section combination, create a `classes` record for each subject
2. Assign teachers from the appropriate department pool (Elementary/JHS/SHS)
3. Prevent double-booking via `resolveAssignment()` — checks teacher availability across time slots and days
4. Create `schedules` records (2 days per class, 1-hour blocks from 7AM–4PM)

### Output
- ~500+ class records
- ~1000+ schedule records
- No teacher double-bookings

---

## 5. FeeSchedulesSeeder

| Grade Level | Tuition/Term | Misc/Term |
|-------------|-------------|-----------|
| Kinder | ₱5,000 | ₱1,667 |
| Grade 1–2 | ₱5,333 | ₱1,733 |
| Grade 3–4 | ₱5,667 | ₱1,800 |
| Grade 5–6 | ₱6,000 | ₱1,867 |
| Grade 7–8 | ₱6,667 | ₱2,000 |
| Grade 9–10 | ₱7,000 | ₱2,067 |
| Grade 11–12 | ₱8,333 | ₱2,333 |

**School Year:** 2026-2027, **Terms:** 1st Term, 2nd Term, 3rd Term

---

## 6. StudentsAndFeesSeeder

### Students (13 total — one per grade level)
| Name | Grade | Strand | Scholarship |
|------|-------|--------|-------------|
| Juan dela Cruz | Kinder | — | No |
| Maria Santos | Grade 1 | — | No |
| Jose Reyes | Grade 2 | — | No |
| Ana Gonzales | Grade 3 | — | Honor (10%) |
| Pedro Fernandez | Grade 4 | — | No |
| Luisa Villanueva | Grade 5 | — | Sibling (5%) |
| Carlos Mendoza | Grade 6 | — | No |
| Sofia Garcia | Grade 7 | — | No |
| Miguel Lopez | Grade 8 | — | No |
| Isabella Martinez | Grade 9 | — | No |
| Rafael Torres | Grade 10 | — | No |
| Angela Ramirez | Grade 11 | STEM | ESC (100%) |
| Dante Cruz | Grade 12 | ABM | No |

### Per Student
- User account (role 7 — Student)
- Student profile with personal info
- Admission record (status: Approved By Registrar)
- Enrollment (status: Active)
- enrollment_subject pivot (all classes for grade level)
- Student ledger with fee schedule
- 1–2 payments (30–60% partial + optional 10–20% extra for even-indexed students)

---

## 7. GradesAssessmentsSeeder

### Assessment Types & Weights
| Type | Max Score | Weight |
|------|-----------|--------|
| Written Work | 50 | 20% |
| Quiz | 30 | 20% |
| Seatwork | 50 | 20% |
| Exam | 100 | 40% |

### Grading Periods
1st Term, 2nd Term, 3rd Term

### Output
- 4 assessments per enrollment/class/period = ~7,572 assessment records
- 1 grade per enrollment/class/period = ~1,893 grade records
- Final grade = weighted average, clamped to 40–100

---

## 8. LibraryAndClinicSeeder

### Books (20)
Filipino textbooks, fiction, and SHS specialized books with serial numbers and prices (₱290–₱580).

### Library Transactions (~15)
Mix of `Borrowed` and `Returned` statuses, varied conditions (Good, Minor Damage).

### Clinic Logs (~10)
Common student complaints: headache, abdominal pain, scraped knee, allergic reaction, toothache, sprained ankle, cough, eye irritation, dizziness, nosebleed.

---

## 9. AnnouncementsTableSeeder

| Title | Type | Date |
|-------|------|------|
| Enrollment for SY 2026-2027 Opens | announcement | 2 days ago |
| New Robotics Lab Facility | announcement | 5 days ago |
| Parent-Teacher Orientation | event | +10 days |
| Intramurals Opening Ceremony | event | +20 days |

---

## 10. FixArNumbersSeeder

Assigns AR numbers to all payments that lack them.

Format: `AR-YYYY-NNNN` (e.g., AR-2026-0500)

Starts from AR-2026-0500 to avoid collision with manually entered AR numbers.

---

## Database Migrations (Aug 14, 2026)

| Migration | Description |
|-----------|-------------|
| `update_assessment_type_to_new_categories` | Drops old enum check, changes `type` to varchar(30), adds new check: `Written Work | Quiz | Seatwork | Exam` |
| `add_grading_period_to_assessments_table` | Adds `grading_period` column + unique constraint on `(enrollment_id, class_id, type, grading_period)` |
| `add_unique_constraint_to_grades_table` | Adds unique constraint on `(enrollment_id, class_id, grading_period)` |
