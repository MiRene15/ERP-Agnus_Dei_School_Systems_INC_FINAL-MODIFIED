<?php

declare(strict_types=1);

namespace App\Http\Requests\Directress;

use App\Services\SchoolYearService;
use Illuminate\Foundation\Http\FormRequest;

class AnnounceSchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Directress (8) owns the setting; Registrar (3) announces per spec §2.
        return $user !== null && in_array((int) $user->role_id, [3, 8], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'school_year' => ['required', 'string', 'max:20', 'regex:/^\d{4}-\d{4}$/'],
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Contracts\Validation\Validator $v): void {
            $year = (string) ($this->input('school_year') ?? '');
            if ($year !== '' && ! SchoolYearService::isValidFormat($year)) {
                $v->errors()->add('school_year', 'School year must be two consecutive years (e.g. 2027-2028).');
            }
        });
    }
}
