<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class BulkSectionAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 2;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'enrollment_ids' => ['required', 'array', 'min:1'],
            'enrollment_ids.*' => ['integer', 'exists:enrollments,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id'],
        ];
    }
}
