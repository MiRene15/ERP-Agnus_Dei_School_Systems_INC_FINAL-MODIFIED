<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class BatchGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:classes,id'],
            'grading_period' => ['required', 'string', 'in:1st Term,2nd Term,3rd Term'],
            'action' => ['sometimes', 'string', 'in:draft,save,post'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.enrollment_id' => ['required', 'exists:enrollments,id'],
            'grades.*.final_grade' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
