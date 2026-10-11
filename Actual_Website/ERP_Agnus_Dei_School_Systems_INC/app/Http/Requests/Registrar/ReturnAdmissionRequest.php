<?php

declare(strict_types=1);

namespace App\Http\Requests\Registrar;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 2;
    }

    /**
     * The note itself is composed by the service from the documents still
     * missing; the registrar's comment is appended to it.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
