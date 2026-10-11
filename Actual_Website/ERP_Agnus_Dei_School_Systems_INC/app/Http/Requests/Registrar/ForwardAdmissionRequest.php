<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class ForwardAdmissionRequest extends FormRequest
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
            // Optional — the service writes a sensible default when blank.
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
