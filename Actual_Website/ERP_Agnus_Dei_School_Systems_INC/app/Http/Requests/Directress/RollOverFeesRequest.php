<?php

declare(strict_types=1);

namespace App\Http\Requests\Directress;

use Illuminate\Foundation\Http\FormRequest;

class RollOverFeesRequest extends FormRequest
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
            'from_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/', 'different:to_year'],
            'to_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/', 'different:from_year'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from_year.different' => 'Pick two different school years to roll over.',
            'to_year.different' => 'Pick two different school years to roll over.',
        ];
    }
}
