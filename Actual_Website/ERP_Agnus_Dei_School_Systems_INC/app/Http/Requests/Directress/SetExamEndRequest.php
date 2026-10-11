<?php

declare(strict_types=1);

namespace App\Http\Requests\Directress;

use App\Services\GradingCloseService;
use Illuminate\Foundation\Http\FormRequest;

class SetExamEndRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && in_array((int) $user->role_id, [3, 8], true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'grading_period' => ['required', 'string', 'in:' . implode(',', GradingCloseService::PERIODS)],
            'exam_end_date' => ['required', 'date'],
        ];
    }
}
