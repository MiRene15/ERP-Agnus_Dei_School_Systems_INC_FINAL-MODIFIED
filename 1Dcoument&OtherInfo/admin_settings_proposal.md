# Admin Technical Settings — Proposal

> **Status: PROPOSAL — awaiting go signal. No code has been changed.**
> Current settings page only has: `active_school_year`, `directress_name`, `principal_name` (via `settings` KV table + `Setting::getValue`).
> This doc proposes the **actual technical settings** the ERP should expose to Admin, grouped by domain, with storage + validation.

## 1. Existing (Keep)
| Key | Type | Notes |
|---|---|---|
| `active_school_year` | `YYYY-YYYY` select | Controls data scope (already used). Keep as is; options = distinct enrollments school_year + `Y/Y+1` new. |
| `directress_name` | string 100 | Appears on Report Card / COR. |
| `principal_name` | string 100 | Appears on Report Card / COR. |

## 2. Proposed New Settings

### A. Academic & Grading
| Key | Default | UI | Notes |
|---|---|---|---|
| `passing_grade` | `75` | number 0-100 | Used by report cards, promotion qualification, and `promotion-index-results` "Qualified" badge. Today hardcoded at 75. |
| `grading_weights` | `20,20,20,40` (WW,Quiz,Seatwork,Exam) | 4 numbers sum 100% | Today hardcoded in `TeacherController`. Making it configurable requires migration of computed grades. Recommend **keep hardcoded** for defense unless panel asks for flexibility — show as read-only info box instead. |

### B. Financial
| Key | Default | UI | Notes |
|---|---|---|---|
| `late_enrollment_fee` | `0` | ₱ amount | Optional surcharge if asked. |

### C. Library (today partially in `settings` but via DB defaults)
| Key | Default | UI | Notes |
|---|---|---|---|
| `library_late_fee_per_day` | `5.00` | ₱ 0-100 | Already configurable via `Setting` but not exposed in UI — add field. |
| `library_damage_minor` | `50.00` | ₱ | Add field. |
| `library_damage_major` | `200.00` | ₱ | Add field. |
| `library_loan_duration_days` | `7` | int 1-30 | Add field. |
| `library_max_books_per_student` | `3` | int 1-10 | Add field. |

### D. System & Session
| Key | Default | UI | Notes |
|---|---|---|---|
| `session_idle_timeout_minutes` | `30` | select 15/30/60 | Informs `config/session.php` lifetime display; actual enforcement via middleware. |
| `maintenance_mode` | `off` | toggle | Puts site in maintenance (Laravel `down`). Restrict to Admin only. |
| `school_name` / `school_address` / `contact_email` / `contact_phone` | seeded `SettingsSeeder` | text | Already in `settings` table but not editable — expose them. |

### E. Enrollment Toggle
| Key | Default | UI | Notes |
|---|---|---|---|
| `enrollment_open` | `1` (open) | toggle | When off, `student/admission` form shows "Enrollment is closed" and blocks submit. |

## 3. Storage Design (no schema change)
- Reuse `settings` table (`key PK`, `value` text) + `Setting::getValue / setValue` (already cached 3600s). Just add keys above.
- All values stored as string; cast on read (`int`/`float`/`bool`).

## 4. Validation
- `passing_grade`: `required|integer|min:50|max:100`
- Fees: `numeric|min:0|max:100000`
- Loan duration: `integer|min:1|max:60`
- School year: `regex:^\d{4}-\d{4}$`
- Names: `nullable|string|max:100`

## 5. UI Layout (admin/settings.blade.php)
```
[School Year] - existing select
[School Identity] - school_name, address, email, phone, directress, principal
[Academic] - passing_grade (read-only info box for grading weights)
[Library] - 5 fields (late fee, damages, duration, max books)
[System] - session timeout, enrollment toggle, maintenance toggle
[Save Settings]
```

## 6. What to Decide (Go / No-Go)
- [ ] Approve adding Library + System + Enrollment settings (section D/E)
- [ ] Approve exposing `school_name/address/email/phone` for edit
- [ ] Confirm `grading_weights` stays hardcoded (recommended) or make editable
- [ ] Confirm `maintenance_mode` toggle is wanted

— End proposal —
