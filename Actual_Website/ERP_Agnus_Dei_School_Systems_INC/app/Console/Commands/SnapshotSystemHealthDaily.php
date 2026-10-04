<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use Illuminate\Console\Command;

class SnapshotSystemHealthDaily extends Command
{
    protected $signature = 'system-health:snapshot {--date= : Snapshot date Y-m-d (default today)}';

    protected $description = 'Snapshot today’s system health counts into system_health_daily for 14-day graphs (counts only, no PII)';

    public function handle(): int
    {
        $date = $this->option('date');
        if (! is_string($date) || $date === '') {
            $date = null;
        }

        SystemHealthService::snapshot($date);

        $this->info('System health snapshot saved for ' . ($date ?? date('Y-m-d')) . '.');

        return 0;
    }
}
