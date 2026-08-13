<?php

namespace App\Models;

use App\Services\SupabaseStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'document_type',
        'file_path',
        'original_filename',
        'file_size',
        'status',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Requirement $req) {
            if ($req->file_path) {
                try {
                    (new SupabaseStorage())->delete($req->file_path);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete requirement file from Supabase: ' . $e->getMessage());
                }
            }
        });
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function getSignedUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return (new SupabaseStorage())->getSignedUrl($this->file_path);
    }
}
