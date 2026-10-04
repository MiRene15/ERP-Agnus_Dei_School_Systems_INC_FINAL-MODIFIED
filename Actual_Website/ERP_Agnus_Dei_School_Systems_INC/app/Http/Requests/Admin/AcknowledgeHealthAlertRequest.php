<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeHealthAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 1;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'alert_type' => ['required', 'string', 'in:abuse,slow,uptime,logins'],
            'route' => ['nullable', 'string', 'max:255'],
            'counts' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
