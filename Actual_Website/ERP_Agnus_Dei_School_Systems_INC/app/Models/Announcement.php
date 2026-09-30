<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'content',
        'type',
        'date',
        'is_published',
        'directress_seen_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'is_published' => 'boolean',
        'directress_seen_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
