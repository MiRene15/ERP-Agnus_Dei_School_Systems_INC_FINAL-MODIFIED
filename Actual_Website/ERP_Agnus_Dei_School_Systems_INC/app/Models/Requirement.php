<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'document_type',
        'file_path',
        'status',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
