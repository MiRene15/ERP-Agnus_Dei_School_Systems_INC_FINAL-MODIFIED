# Inquiry Feature Bug Analysis

## Issue Summary

Users submitting the inquiry form experience:
- **Endless loading** (spinning/hanging)
- **HTTP 500 error** after timeout
- **No email ever sent** (no credentials received)

---

## Root Causes Identified

### 1. SMTP Hangs Indefinitely (Primary Cause of Endless Loading)

**File:** `config/mail.php:48`

```php
'timeout' => null,  // <-- No timeout set!
```

**File:** `app/Http/Controllers/PromotionalWebsite/InquiryController.php:76`

```php
Mail::to($personalEmail)->send(new InquiryCredentialsMail(...));
```

The `Mail::send()` call is **synchronous** with **no timeout**. If the Gmail SMTP server (`smtp.gmail.com:465`) is unreachable, blocked by a firewall, or the App Password (`vfsr uyge kxyj bvld`) is expired/revoked, the PHP process hangs indefinitely waiting for a connection that will never complete. This causes the "endless loading" the user sees in the browser.

### 2. No Error Handling (Primary Cause of 500 Error)

**File:** `app/Http/Controllers/PromotionalWebsite/InquiryController.php:21-79`

The entire `store()` method has **zero try-catch blocks**. When the SMTP connection eventually times out or fails, it throws an unhandled exception. Laravel catches it and returns a generic 500 error with no useful feedback to the user.

### 3. No Database Transaction (Data Integrity Risk)

**File:** `app/Http/Controllers/PromotionalWebsite/InquiryController.php:59-76`

The flow is:
1. `User::create()` -- writes to DB (line 59)
2. `Student::create()` -- writes to DB (line 67)
3. `Mail::send()` -- **fails here** (line 76)

When step 3 fails, the User and Student records **already exist** in the database. The redirect to `/inquiry` with `success` flash never executes. On retry, the user may get duplicate records or a unique constraint violation.

### 4. Gmail App Password Likely Invalid

**File:** `.env:56`

```
MAIL_PASSWORD="vfsr uyge kxyj bvld"
```

Gmail App Passwords can expire or be revoked. If this password is no longer valid, SMTP authentication fails silently (after hanging), causing the 500.

### 5. No Feedback Mechanism

The inquiry form (`resources/views/PromotionalWebsite/inquiry.blade.php`) has no:
- Loading spinner / disabled button during submission
- Error state display for 500 errors
- Fallback message if email fails

---

## Complete Request Flow

```
User submits form (POST /inquiry)
  --> InquiryController::store()
    --> Validate input (OK)
    --> Generate institutional email (OK)
    --> User::create() -- WRITES TO DB (OK)
    --> Student::create() -- WRITES TO DB (OK)
    --> Mail::send() -- HANGS / TIMES OUT / THROWS 500
    --> redirect('/inquiry')->with('success', true) -- NEVER REACHED
```

---

## Required Fixes

### Fix 1: Add SMTP Timeout

**File:** `config/mail.php:48`

Change:
```php
'timeout' => null,
```
To:
```php
'timeout' => 10,
```

### Fix 2: Add Try-Catch + DB Transaction

**File:** `app/Http/Controllers/PromotionalWebsite/InquiryController.php`

Wrap the entire `store()` logic in a `DB::transaction()` and `try-catch` block:

```php
use Illuminate\Support\Facades\DB;

public function store(Request $request)
{
    $request->validate([...]);

    try {
        DB::transaction(function () use ($request) {
            // ... User::create(), Student::create(), Mail::send()
        });

        return redirect('/inquiry')->with('success', true);

    } catch (\Exception $e) {
        \Log::error('Inquiry submission failed: ' . $e->getMessage());
        return redirect('/inquiry')->with('error', 'Something went wrong. Please try again.');
    }
}
```

### Fix 3: Add Error Display to Inquiry View

**File:** `resources/views/PromotionalWebsite/inquiry.blade.php`

Add error flash display after the form (same pattern as the success modal).

### Fix 4: Verify Gmail App Password

Test the SMTP credentials independently or regenerate a new App Password from the Google account.

---

## Files to Modify

| File | Change |
|------|--------|
| `config/mail.php` | Set SMTP timeout to 10 seconds |
| `app/Http/Controllers/PromotionalWebsite/InquiryController.php` | Add DB::transaction + try-catch + Log error |
| `resources/views/PromotionalWebsite/inquiry.blade.php` | Add error flash display |
| `.env` | Verify/regenerate Gmail App Password |

---

## Awaiting Signal to Execute Fixes
