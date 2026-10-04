<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthDaily extends Model
{
    protected $table = 'system_health_daily';

    protected $fillable = [
        'date',
        'abuse_hits',
        'slow_total',
        'login_failed',
        'login_locked',
        'new_accounts',
        'uptime_ok',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'uptime_ok' => 'boolean',
        ];
    }
}
