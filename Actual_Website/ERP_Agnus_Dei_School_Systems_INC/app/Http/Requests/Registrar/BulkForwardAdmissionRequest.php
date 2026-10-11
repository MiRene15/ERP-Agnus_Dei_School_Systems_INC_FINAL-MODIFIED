<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class BulkForwardAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 2;
    }

    /**
     * One note for the whole batch; each admission still goes through the
     * single-file gates, and blocked files come back per file.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'admission_ids' => ['required', 'array', 'min:1'],
            'admission_ids.*' => ['integer', 'exists:admissions,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admission_ids.required' => 'Select at least one application to forward.',
            'admission_ids.min' => 'Select at least one application to forward.',
        ];
    }
}
