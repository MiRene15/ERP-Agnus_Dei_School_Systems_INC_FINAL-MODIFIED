# Database File Storage Review - Requirements Upload

## Current State

Files are stored **locally** on disk at `storage/app/public/requirements/` via the `public` disk. The `requirements` table saves the relative file path (VARCHAR 255). Files are served through a `public/storage` symlink.

**Problem:** Files only exist on the local server. If Render restarts or deploys, local files are lost. The user wants files stored on **Supabase** (cloud).

**Supabase Storage Status:** NOT configured. No env vars, no packages, no config files exist.

---

## What Needs to Happen

### Step 1: Get Supabase API Keys

From: `https://supabase.com/dashboard/project/sjnuvyiwszcjxixunlzi/settings/api`

Need:
- `SUPABASE_URL` = `https://sjnuvyiwszcjxixunlzi.supabase.co`
- `SUPABASE_KEY` = anon/public key
- `SUPABASE_SERVICE_KEY` = service_role key

### Step 2: Create Storage Bucket

From: `https://supabase.com/dashboard/project/sjnuvyiwszcjxixunlzi/storage/buckets`

Create bucket: `school-documents`
- Set to **private** (signed URLs for access)
- Max file size: 5MB
- Allowed MIME types: `application/pdf, image/jpeg, image/png`

### Step 3: Add to `.env`

```
SUPABASE_URL=https://sjnuvyiwszcjxixunlzi.supabase.co
SUPABASE_KEY=<anon-key>
SUPABASE_SERVICE_KEY=<service-role-key>
SUPABASE_STORAGE_BUCKET=school-documents
```

### Step 4: Create SupabaseStorage Service Class

**File:** `app/Services/SupabaseStorage.php`

Uses Laravel's `Http` facade (Guzzle already installed) to call Supabase Storage REST API:
- `upload($bucket, $path, $content)` -- PUT to `/storage/v1/object/{bucket}/{path}`
- `delete($bucket, $path)` -- DELETE to `/storage/v1/object/{bucket}/{path}`
- `getSignedUrl($bucket, $path, $expires)` -- POST to `/storage/v1/object/sign/{bucket}/{path}`

No Composer package needed.

### Step 5: Update Controllers

**`StudentAdmissionController.php`:**
- Replace `$file->store('requirements/{id}', 'public')` with `SupabaseStorage::upload()`
- Store the Supabase path (not local path) in `file_path`
- On re-upload: delete old file from Supabase first

**`RegistrarAdmissionController.php`:**
- Replace `asset('storage/' . $req->file_path)` with `SupabaseStorage::getSignedUrl()`
- Signed URLs expire after 1 hour for security

### Step 6: Add File Cleanup on Deletion

**`app/Models/Requirement.php`:**
```php
protected static function booted(): void
{
    static::deleted(function (Requirement $req) {
        SupabaseStorage::delete('school-documents', $req->file_path);
    });
}
```

### Step 7: Database Changes

**New migration:** Widen `file_path` to VARCHAR(500), add metadata columns

```php
Schema::table('requirements', function (Blueprint $table) {
    $table->string('file_path', 500)->change();
    $table->string('original_filename')->nullable()->after('file_path');
    $table->unsignedInteger('file_size')->nullable()->after('original_filename');
});
```

---

## Architecture Summary

```
[UPLOAD]
  Student -> POST /student/admission/requirements
  -> Validates: mimes, max:5MB
  -> SupabaseStorage::upload('school-documents', 'requirements/{id}/{hash}.{ext}', $content)
  -> DB row: { admission_id, document_type, file_path: 'requirements/{id}/{hash}.{ext}', original_filename, file_size, status }

[VIEW]
  -> SupabaseStorage::getSignedUrl('school-documents', $file_path, expires: 3600)
  -> Returns temporary URL: https://sjnuvyiwszcjxixunlzi.supabase.co/storage/v1/object/sign/school-documents/...
  -> URL expires after 1 hour

[DELETE]
  -> Requirement deleted event triggers
  -> SupabaseStorage::delete('school-documents', $file_path)
  -> Physical file removed from Supabase Storage
```

---

## Files to Create/Modify

| File | Change |
|------|--------|
| `.env` | Add SUPABASE_URL, SUPABASE_KEY, SUPABASE_SERVICE_KEY, SUPABASE_STORAGE_BUCKET |
| `app/Services/SupabaseStorage.php` | **NEW** - Supabase Storage REST API wrapper |
| `app/Http/Controllers/Portal/StudentAdmissionController.php` | Use SupabaseStorage for upload |
| `app/Http/Controllers/Portal/RegistrarAdmissionController.php` | Use signed URLs for viewing |
| `app/Models/Requirement.php` | Add deleted event for file cleanup |
| `database/migrations/xxxx_widen_file_path_add_metadata.php` | **NEW** - Widen file_path, add original_filename + file_size |
| `config/filesystems.php` | No change needed (service class handles it) |

---

## Awaiting Signal to Execute
