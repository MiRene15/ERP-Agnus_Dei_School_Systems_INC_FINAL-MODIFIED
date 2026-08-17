# Switch to BYTEA Database Storage

## Why

Supabase Storage is overkill for a school ERP. BYTEA is simpler:
- No API keys, no buckets, no signed URLs
- Files stored directly in PostgreSQL
- One backup includes everything
- Works for PDFs, images, any binary

## Changes

### Database

| Column | Type | Notes |
|--------|------|-------|
| `file_content` | BYTEA | Raw binary data |
| `mime_type` | string | e.g. "application/pdf", "image/jpeg" |
| `original_filename` | string | Kept |
| `file_size` | integer | Kept |
| `file_path` | DROPPED | No longer needed |

### Upload Flow

```php
Requirement::create([
    'admission_id' => $admission->id,
    'document_type' => $documentType,
    'file_content' => $file->getContent(),
    'original_filename' => $file->getClientOriginalName(),
    'mime_type' => $file->getMimeType(),
    'file_size' => $file->getSize(),
]);
```

### View Flow

New route: `GET /student/admission/requirements/{requirement}/view`
- Reads `file_content` from DB
- Returns `Response` with correct `Content-Type` header
- Browser renders PDF inline or downloads image

### Files to Modify

| File | Change |
|------|--------|
| New migration | Add BYTEA + mime_type, drop file_path |
| `Requirement.php` | Add file_content, mime_type to fillable, remove file_path accessor |
| `StudentAdmissionController.php` | Save binary instead of uploading to Supabase |
| `StudentController.php` | Add `viewRequirement()` method |
| `routes/web.php` | Add file viewing route |
| `admission-status.blade.php` | Update View links |
| `admissions-show.blade.php` | Update View links |

### Cleanup

- Remove `app/Services/SupabaseStorage.php`
- Remove Supabase env vars from .env
- Remove Supabase config from services.php

## Awaiting Signal to Execute
