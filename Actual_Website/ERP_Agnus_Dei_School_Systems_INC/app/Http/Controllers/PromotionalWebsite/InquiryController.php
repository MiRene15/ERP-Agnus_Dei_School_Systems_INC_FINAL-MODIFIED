<?php

namespace App\Http\Controllers\PromotionalWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\InquiryCredentialsMail;

class InquiryController extends Controller
{
    public function show()
    {
        return view('PromotionalWebsite.inquiry');
    }

    public function store(Request $request)
    {
        // Honeypot: bots fill this hidden field; humans never see it.
        // Fake success without creating anything (don't tip off spammers).
        if ($request->filled('website')) {
            return redirect('/inquiry')->with('success', true);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'personal_email' => [
                'required',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    $allowed = ['gmail.com', 'yahoo.com', 'proton.me', 'protonmail.com', 'outlook.com', 'hotmail.com'];
                    $domain = substr(strrchr($value, '@'), 1);
                    if (!in_array(strtolower($domain), $allowed)) {
                        $fail('Please use a verified email provider (Gmail, Yahoo, Proton, or Outlook).');
                    }
                },
                // No duplicate applications: same child (name) + same contact
                // email already in the system. Siblings (different names) pass.
                function ($attribute, $value, $fail) use ($request) {
                    $first = strtolower(trim((string) $request->first_name));
                    $last = strtolower(trim((string) $request->last_name));
                    $email = strtolower(trim((string) $value));
                    $dup = Student::whereRaw('LOWER(TRIM(first_name)) = ?', [$first])
                        ->whereRaw('LOWER(TRIM(last_name)) = ?', [$last])
                        ->whereRaw('LOWER(TRIM(personal_email)) = ?', [$email])
                        ->exists();
                    if ($dup) {
                        $fail('An application with this name and email already exists. Please log in or contact the registrar instead of applying again.');
                    }
                },
            ],
        ]);

        try {
            $createdUser = null;
            $firstName = $request->first_name;
            $lastName = $request->last_name;
            $personalEmail = $request->personal_email;

            $baseEmail = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
            $institutionalEmail = $baseEmail . '@agnusdei.edu.ph';

            $counter = 1;
            while (User::where('email', $institutionalEmail)->exists()) {
                $institutionalEmail = $baseEmail . $counter . '@agnusdei.edu.ph';
                $counter++;
            }

            $password = Str::random(8);

            DB::transaction(function () use ($request, &$createdUser, $firstName, $lastName, $personalEmail, $institutionalEmail, $password) {
                $user = User::create([
                    'name' => $firstName . ' ' . $lastName,
                    'email' => $institutionalEmail,
                    'password' => Hash::make($password),
                    'role_id' => 7,
                ]);
                $createdUser = $user;

                Student::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'personal_email' => $personalEmail,
                    'status' => 'pre-admission'
                ]);
            });

            // Mail is sent AFTER the commit: a mail-provider outage must never
            // roll back (or block) the inquiry itself.
            try {
                Mail::to($personalEmail)->send(new InquiryCredentialsMail($firstName, $institutionalEmail, $password));
            } catch (\Exception $mailError) {
                Log::warning('Inquiry credentials email failed: ' . $mailError->getMessage(), [
                    'personal_email' => $personalEmail,
                    'institutional_email' => $institutionalEmail,
                ]);
            }

            if ($createdUser) {
                log_activity($createdUser, 'Account Created', 'Pre-admission account created via public inquiry: ' . $createdUser->name . ' (' . $createdUser->email . '). Credentials emailed to ' . $request->personal_email . '.');
            }

            return redirect('/inquiry')->with('success', true);

        } catch (\Exception $e) {
            Log::error('Inquiry submission failed: ' . $e->getMessage());
            return redirect('/inquiry')->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
