# Scheduling Review — Principal Module

## Current Implementation
- **Owner:** Principal (role 9)
- **Routes:** `GET /principal/schedules` (list), `POST /principal/schedules` (manual add), `DELETE /principal/schedules/{schedule}` (remove), `GET /principal/schedules/template` + `POST /principal/schedules/import` (hybrid CSV)
- **Controller:** `PrincipalController@schedules` (filter by grade_level 7-12, school_year, day, search), `schedulesStore` (validate `class_id|exists:classes`, `day_of_week` Monday-Friday, `start_time < end_time`, `room`, conflict check per `class_id+day`), `schedulesDestroy`, `schedulesTemplate`/`Import` (CSV: `class_id,day_of_week,start_time,end_time,room`, header validation, per-row validation, conflict skip)
- **View:** `principal/schedules.blade.php` (grade tabs 7-12, search + year + day filters via `ajaxTable`, skeleton loading, results via `schedules-results` partial), plus hybrid CSV `<details>` panel (template download + file input, error/skip display)
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
- 3. CSV friendlier key (grade+section+subject_code) — can add if Principal requests
- 4. Template helper table — current note "Find class_id in the table below" already helps
- 5. Edit — delete-only is acceptable for defense

