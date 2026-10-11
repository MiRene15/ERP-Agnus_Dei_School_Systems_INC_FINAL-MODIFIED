<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class BulkDownpaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 3;
    }

    /**
     * One plan + one amount for the whole batch (the enrollment-fee
     * downpayment is identical across files); each file still goes
     * through the single-file gates, and blocked files come back per
     * file with their reason.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'admission_ids' => ['required', 'array', 'min:1'],
            'admission_ids.*' => ['integer', 'exists:admissions,id'],
            'payment_plan' => ['required', 'in:installment,full'],
            'amount_each' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admission_ids.required' => 'Select at least one application to confirm.',
            'admission_ids.min' => 'Select at least one application to confirm.',
            'amount_each.required' => 'Enter the amount collected per application.',
        ];
    }
}
