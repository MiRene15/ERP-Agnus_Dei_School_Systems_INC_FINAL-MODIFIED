<?php

declare(strict_types=1);

namespace App\Http\Requests\Directress;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 8;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'grade_level' => ['required', 'string', 'max:20'],
            'term' => ['required', 'in:1st Term,2nd Term,3rd Term'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
            'misc_fee' => ['required', 'numeric', 'min:0'],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }
}
