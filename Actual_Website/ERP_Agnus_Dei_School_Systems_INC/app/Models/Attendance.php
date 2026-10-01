<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
    ];

    protected $fillable = [
        'class_id',
        'enrollment_id',
        'marked_on',
        'status',
        'marked_by',
    ];

    protected $casts = [
        'marked_on' => 'date',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
