<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 7 && $user->student !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '_idempotency_key' => ['nullable', 'string', 'max:255'],
            'application_type' => ['required', 'in:New,Transferee'],
            'grade_level' => ['required', 'string', 'max:20'],
            'strand' => ['nullable', 'required_if:grade_level,Grade 11,Grade 12', 'in:Arts, Social Sciences, and Humanities,Business and Entrepreneurship'],
            'school_year' => ['required', 'string', 'max:20'],

            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:Male,Female,Non-binary,Prefer not to say'],
            'gender_detail' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'after_or_equal:1950-01-01', 'before_or_equal:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'legacy_lrn' => ['nullable', 'digits:12'],
            'contact_number' => ['nullable', 'string', 'max:15'],

            'permanent_address' => ['nullable', 'string', 'max:500'],
            'same_as_permanent' => ['nullable', 'boolean'],
            'current_address' => ['nullable', 'string', 'max:500'],

            'father_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_contact' => ['nullable', 'string', 'max:15'],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_number' => ['nullable', 'string', 'max:15'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],

            'previous_school' => ['nullable', 'string', 'max:255'],
            'previous_school_address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'application_type.required' => 'Please choose New or Transferee.',
            'grade_level.required' => 'Please choose a grade level.',
            'strand.required_if' => 'Pick an elective for Grade 11/12.',
            'school_year.required' => 'Please choose a school year.',
            'first_name.required' => 'Please enter the first name.',
            'last_name.required' => 'Please enter the last name.',
            'gender.required' => 'Please choose the option that fits best — Prefer not to say is okay.',
            'date_of_birth.required' => 'Please enter the date of birth.',
            'date_of_birth.after_or_equal' => 'Birth date is too far back.',
            'date_of_birth.before_or_equal' => "Birth date can't be in the future.",
        ];
    }
}
