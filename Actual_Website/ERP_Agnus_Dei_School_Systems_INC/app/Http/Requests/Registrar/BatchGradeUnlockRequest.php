<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class BatchGradeUnlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && in_array((int) $user->role_id, [2, 9], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:grade_unlock_requests,id'],
        ];
    }
}
