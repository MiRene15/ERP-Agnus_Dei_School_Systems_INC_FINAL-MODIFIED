<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCashierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 3;
    }

    /**
     * Shape validation only. The per-term floor (minimum for the term,
     * naming the floor) is enforced in the controller via
     * FeeAssessmentService::floorFor() because it depends on the ledger state.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payment_plan' => ['required', 'in:installment,full'],
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'discount_type' => ['nullable', 'in:honor,sibling,esc,other'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'ar_number' => ['nullable', 'string', 'max:30'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'library_transaction_ids' => ['nullable', 'array'],
            'library_transaction_ids.*' => ['integer', 'exists:library_transactions,id'],
        ];
    }
}
