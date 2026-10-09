# Spec: Library Seeder Book Title Fix

- **Status**: Implemented
- **Created**: 2026-10-09
- **Approved by**: user on 2026-10-09
- **Implemented**: 2026-10-09 — added `'book_title' => $book->title` to `LibraryTransaction::create()` in `LibraryAndClinicSeeder.php`; all 3 success checks confirmed.

## 1. Why We Need This
The full database seed crashes when loading library borrow/return records. The seeder creates each transaction without including the book's title, but the database requires it. The seed run stops at the library step, blocking IT Admin from loading a fresh database and blocking the librarian from getting their borrow/return history.

## 2. Who Is Affected
- **Librarian (primary)** — their borrow/return history fails to load; they see no transaction records after a fresh seed.
- **IT Admin** — the full seed suite crashes, so they cannot load a fresh database.

## 3. Business Flow: Today vs After
- **As-is**: IT Admin runs the full seed → library step creates a transaction without a book title → database rejects it → seed crashes.
- **To-be**: IT Admin runs the full seed → library step creates each transaction with the book title → seed completes → librarian sees full borrow/return history.

## 4. How It Should Work
1. IT Admin runs the full seed suite.
2. Library step creates each borrow/return record with the book's title included.
3. Seed completes without errors.
4. Librarian opens the library screen → sees all borrow/return records with book titles.

## 5. Look & Feel (UX)
- No screen change. No new clicks. The fix is inside the data loader only.

## 6. Business Rules
### Must always be true
- Every new library transaction created by the seeder carries the book's title.
### Must never happen
- A library transaction is saved without a book title.
### Edge cases and what happens then
- Existing library transactions (created by the librarian UI or a previous seed run) are never modified.
- Re-running the seed skips students who already have a transaction for the same book (idempotency preserved).

## 7. Out of Scope
- Clinic seeding (no changes).
- Librarian UI or workflows.
- Fee calculation or report logic.
- Changes to existing transaction records.

## 8. Success Checks
- [ ] Full seed (`php artisan db:seed`) completes without errors.
- [ ] Every library transaction in the database has a non-null book title.
- [ ] Re-running the full seed changes nothing and duplicates nothing.

## 9. Open Questions (if any)
None.

## 10. Technical Notes (for developers)
- File to change: `database/seeders/LibraryAndClinicSeeder.php` — add `'book_title' => $book->title` to the `LibraryTransaction::create()` call (line ~119).
- The `library_transactions` table has a NOT NULL `book_title` column (migration `2026_03_29_142152`).
- The `LibraryTransaction` model already has `book_title` in `$fillable`.
- The idempotency check at line 114-117 already queries by `book_title`, so it will work correctly once the insert includes it.

## 11. Approval
> Approved by user on 2026-10-09.
