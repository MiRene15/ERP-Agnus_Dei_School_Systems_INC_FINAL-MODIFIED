<?php

declare(strict_types=1);

namespace App\Http\Requests\Directress;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->role_id === 8;
    }

    /**
     * Spec fee-itemized-manager.md: Books + Uniform + ID + Miscellaneous
     * must add to the misc total on save (4-way split, never one lump).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'grade_level' => ['required', 'string', 'max:20'],
            'term' => ['required', 'in:1st Term,2nd Term,3rd Term'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
            'misc_fee' => ['required', 'numeric', 'min:0'],
            'misc_fee_items' => ['required', 'array'],
            'misc_fee_items.books' => ['required', 'numeric', 'min:0'],
            'misc_fee_items.uniform' => ['required', 'numeric', 'min:0'],
            'misc_fee_items.id' => ['required', 'numeric', 'min:0'],
            'misc_fee_items.miscellaneous' => ['required', 'numeric', 'min:0'],
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $items = $this->input('misc_fee_items', []);
            if (! is_array($items)) {
                return;
            }
            $sum = round((float) ($items['books'] ?? 0) + (float) ($items['uniform'] ?? 0) + (float) ($items['id'] ?? 0) + (float) ($items['miscellaneous'] ?? 0), 2);
            $total = round((float) $this->input('misc_fee', 0), 2);
            if (abs($sum - $total) > 0.01) {
                $diff = round(abs($sum - $total), 2);
                $validator->errors()->add(
                    'misc_fee_items',
                    'Books + Uniform + ID + Miscellaneous = ₱' . number_format($sum, 2) . ', but the Misc total is ₱' . number_format($total, 2) . ' — off by ₱' . number_format($diff, 2) . '. Adjust the 4 boxes so they add up.'
                );
            }
        });
    }
}
