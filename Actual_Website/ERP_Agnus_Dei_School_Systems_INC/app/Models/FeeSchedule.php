<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_level',
        'term',
        'tuition_fee',
        'misc_fee',
        'misc_fee_items',
        'school_year',
    ];

    protected $casts = [
        'misc_fee_items' => 'array',
    ];

    /**
     * Spec: cashier-fee-rules.md — fee edits save under the selected
     * school year only. Always scope reads/writes by school year.
     *
     * @param  Builder<FeeSchedule>  $query
     * @return Builder<FeeSchedule>
     */
    public function scopeForSchoolYear(Builder $query, string $schoolYear): Builder
    {
        return $query->where('school_year', $schoolYear);
    }
}
