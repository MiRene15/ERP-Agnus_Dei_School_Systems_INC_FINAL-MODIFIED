<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradeAssessmentStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'grading_period' => ['required', 'string', 'in:1st Term,2nd Term,3rd Term'],
            'assessments' => ['required', 'array'],
            'assessments.*.type' => ['required', 'string', 'in:' . implode(',', Assessment::ASSESSMENT_TYPES)],
            'assessments.*.title' => ['nullable', 'string', 'max:255'],
            'assessments.*.raw_score' => ['nullable', 'numeric', 'min:0'],
            'assessments.*.max_score' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'assessments.required' => 'Nothing to save — add at least one score row first.',
            'assessments.*.raw_score.numeric' => 'Every score must be a number.',
            'assessments.*.max_score.numeric' => 'Every total must be a number.',
        ];
    }
}
