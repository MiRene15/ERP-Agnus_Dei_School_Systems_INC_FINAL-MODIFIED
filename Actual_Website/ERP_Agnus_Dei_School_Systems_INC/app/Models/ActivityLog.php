<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model {
    protected $table = 'activity_log';
    protected $fillable = ['subject_type', 'subject_id', 'causer_id', 'event', 'description', 'properties'];
    protected $casts = ['properties' => 'array'];
    public function subject() { return $this->morphTo(); }
    public function causer() { return $this->belongsTo(User::class, 'causer_id'); }
}
