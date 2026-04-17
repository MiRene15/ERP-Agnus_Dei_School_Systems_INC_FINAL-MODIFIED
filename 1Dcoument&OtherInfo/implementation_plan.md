# Inquiry Page & Institutional Email Generation

We will create an Inquiry page on the promotional website where prospective students can submit their basic information. Upon submission, the system will automatically generate a new institutional email and a random password for them, create their initial portal accounts, and securely email these credentials to their provided personal email address.

## User Review Required

> [!IMPORTANT]  
> Are we directly creating their `User` and `Student` records in the database at the time of inquiry? The plan below assumes **YES**, mapping the generated email to a `User` account and saving their personal email to the `students` table. If you'd prefer to just save it to the `inquiries` table for now without creating a full `User` record, please let me know!
> 
> Also, since we are sending an email, we will need to set up mail configuration in the `.env` file (e.g., using Mailtrap for local testing). Are you okay with me configuring Laravel to log emails locally for now until an SMTP server is set up?

## Proposed Changes

### Routes & Controllers

#### [NEW] `InquiryController.php`
- `show()`: Renders the inquiry form view.
- `store()`: Validates request, generates the institutional email (handles duplicates by appending a number, e.g., `juan.delacruz1@agnusdei.edu.ph`), generates a random password, creates the `User` and `Student` record, and dispatches the email.

#### [MODIFY] `routes/web.php`
- Add `GET /inquiry` routing to `InquiryController@show`.
- Add `POST /inquiry` routing to `InquiryController@store`.

***
### Views & UI Components

#### [NEW] `resources/views/PromotionalWebsite/inquiry.blade.php`
- An aesthetically pleasing and modern form following your premium vanilla CSS architecture.
- Fields: First Name, Last Name, and Personal Email.

***
### Mailables & Notifications

#### [NEW] `app/Mail/InquiryCredentialsMail.php`
- A Laravel Mailable handling the email generation.

#### [NEW] `resources/views/emails/inquiry_credentials.blade.php`
- The email template containing their new `@agnusdei.edu.ph` institutional email and the randomly generated default password.

## Verification Plan

### Manual Verification
1. I will navigate to `/inquiry` and verify the UI looks consistent with `layout.blade.php`.
2. I will test submitting the form.
3. I will check the database (`users` and `students` tables) to verify the records are created correctly with the new `personal_email` column.
4. I will check the Laravel logs (or local mail solution) to ensure the email was constructed correctly with the right credentials.
