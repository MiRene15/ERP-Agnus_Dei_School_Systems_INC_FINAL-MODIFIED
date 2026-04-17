<?php

namespace App\Http\Controllers\PromotionalWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            'personal_email' => 'required|email|max:255',
        ]);

        $firstName = $request->first_name;
        $lastName = $request->last_name;
        $personalEmail = $request->personal_email;

        // Base email generation: firstname.lastname@agnusdei.edu.ph
        $baseEmail = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
        $institutionalEmail = $baseEmail . '@agnusdei.edu.ph';

        // Check for collision and append number if exists
        $counter = 1;
        while (User::where('email', $institutionalEmail)->exists()) {
            $institutionalEmail = $baseEmail . $counter . '@agnusdei.edu.ph';
            $counter++;
        }

        // Generate a random 8 character password
        $password = Str::random(8);

        // Create the User Account (Role 7 for Student based on migrations default)
        $user = User::create([
            'name' => $firstName . ' ' . $lastName,
            'email' => $institutionalEmail,
            'password' => Hash::make($password),
            'role_id' => 7,
        ]);

        // Create the linked Student profile
        Student::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'personal_email' => $personalEmail,
            'status' => 'pre-admission'
        ]);

        // Dispatch the email Notification
        Mail::to($personalEmail)->send(new InquiryCredentialsMail($firstName, $institutionalEmail, $password));

        return redirect('/inquiry')->with('success', 'Your inquiry has been submitted! Check your personal email for your institutional login credentials.');
    }
}
