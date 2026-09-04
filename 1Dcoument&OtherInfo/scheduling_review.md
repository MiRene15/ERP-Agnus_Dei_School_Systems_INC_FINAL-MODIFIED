# Scheduling Review — Principal Module

## Current Implementation (Updated Aug 24)
- **Owner:** Principal (role 9)
- **Routes:** `GET /principal/schedules` (list), `POST /principal/schedules` (manual add), `DELETE /principal/schedules/{schedule}` (remove), `GET /principal/schedules/template` + `POST /principal/schedules/import` (hybrid CSV — now friendly)
- **Controller:** `PrincipalController@schedules` (filter 7-12, year, day, search), `schedulesStore` (validate `class_id|exists:classes`, `day_of_week` Mon-Fri, `start_time`/`end_time` `date_format:H:i` + `after:start_time`, `room`, conflict checks: per-class + cross-class **teacher** (`whereHas('schoolClass', teacher_id)`) + **room** (`where('room', ...)`) ), `schedulesTemplate` (now `grade_level,section,subject_code,day_of_week,start_time,end_time,room` with 2 examples, legacy `class_id` still supported), `schedulesImport` (detects legacy vs friendly header, resolves `grade/section/subject_code` → `class_id` via `active_school_year` lookup, strict time, teacher/room skip)
- **View:** `principal/schedules.blade.php` (grade tabs, search/year/day, `ajaxTable`, hybrid `<details>` panel now explains `grade_level, section, subject_code` e.g. `Grade 7, A, ENG7, Monday, 08:00, 09:00, J-101`, notes `subject_code` can be code or name)
- **Model:** `Schedule` (`class_id`, `day_of_week`, `start_time`, `end_time`, `room`), `Classes` (`grade_level`, `school_year`, `section`, `subject`, `teacher`)

## Strengths
- Conflict detection per class/day/time overlap (prevents double-booking a class)
- Grade + year + day filters and search by subject/teacher
- Hybrid CSV keeps manual as primary, bulk import optional with validation and skip reporting — good for defense

## Gaps / Risks
- **No teacher-overlap check:** Current conflict is per `class_id` only. A teacher could be double-booked across two different classes at same time/day. Need cross-class teacher conflict.
- **No room clash check:** Two different classes could be booked same room/time.
- **Time format tolerance:** CSV accepts any `start_time` string that passes `required` but not strict `H:i` — could store `8am` vs `08:00` inconsistently.
- **UX:** `class_id` in CSV is not user-friendly; Principal must look up IDs from table. Better to allow `grade_level+section+subject_code` lookup.
- **No bulk delete / no edit:** Only add + delete single.

## Recommendations (Low-Effort)
1. **Add teacher conflict check** in `schedulesStore` + `schedulesImport`: `whereHas('class', teacher_id)` overlap. Same for room if `room` not null.
2. **Strict time validation:** `date_format:H:i` for `start_time`/`end_time` in both manual and CSV.
3. **CSV friendlier key:** Accept either `class_id` OR `grade_level,section,subject_code` triple; resolve to `class_id` server-side, report unresolvable rows.
4. **Template helper:** Show a small table of available `class_id` → `Grade — Section — Subject (Teacher)` on `schedules` page to help CSV.
5. **Edit instead of delete-only:** Add `PATCH /principal/schedules/{schedule}` for time/room changes (optional).

## Verdict — Implemented Aug 20
- **1. Teacher/Room conflict** ✅ Done — `PrincipalController@schedulesStore` and `schedulesImport` now check cross-class teacher overlap (`whereHas('schoolClass', teacher_id)`) and room clash (same room/day/time), with distinct error messages.
- **2. Strict time** ✅ Done — `start_time`/`end_time` now `date_format:H:i` in both manual and CSV paths.
- Keep hybrid as is — now fully defensible.

## Remaining Optional (Not Blocking)
- 3. CSV friendlier key — ✅ Done (friendly header now primary, legacy still supported)
- 4. Template helper table — updated to "Find Grade — Section — Subject in the table below"
- 5. Edit — delete-only is acceptable for defense

