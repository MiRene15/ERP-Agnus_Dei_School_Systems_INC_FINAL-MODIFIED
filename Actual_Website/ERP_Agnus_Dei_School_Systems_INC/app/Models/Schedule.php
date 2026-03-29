<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
