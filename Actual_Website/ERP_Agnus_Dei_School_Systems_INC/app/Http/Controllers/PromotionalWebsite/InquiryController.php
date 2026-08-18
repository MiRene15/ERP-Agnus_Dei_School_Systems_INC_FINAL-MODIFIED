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
            ],
        ]);

        try {
            $firstName = $request->first_name;
            $lastName = $request->last_name;
            $personalEmail = $request->personal_email;

            $password = Str::random(8);
            $institutionalEmail = null;

            DB::transaction(function () use ($firstName, $lastName, $personalEmail, $password, &$institutionalEmail) {
                $baseEmail = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
                $institutionalEmail = $baseEmail . '@agnusdei.edu.ph';

                $counter = 1;
                while (User::where('email', $institutionalEmail)->exists()) {
                    $institutionalEmail = $baseEmail . $counter . '@agnusdei.edu.ph';
                    $counter++;
                }

                $user = User::create([
                    'name' => $firstName . ' ' . $lastName,
                    'email' => $institutionalEmail,
                    'password' => Hash::make($password),
                    'role_id' => 7,
                ]);

                Student::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'personal_email' => $personalEmail,
                    'status' => 'pre-admission'
                ]);
            });

            try {
                Mail::to($personalEmail)->send(new InquiryCredentialsMail($firstName, $institutionalEmail, $password));
            } catch (\Exception $mailError) {
                Log::error('Inquiry mail failed: ' . $mailError->getMessage());
            }

            return redirect('/inquiry')->with('success', true);

        } catch (\Exception $e) {
            Log::error('Inquiry submission failed: ' . $e->getMessage());
            return redirect('/inquiry')->with('error', 'Something went wrong while processing your inquiry. Please try again or contact support.');
        }
    }
}
