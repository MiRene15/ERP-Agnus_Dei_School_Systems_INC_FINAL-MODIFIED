<?php

use App\Models\IdempotencyKey;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database')->dailyAt('02:00');
Schedule::command('reminders:payment')->dailyAt('08:00');
Schedule::command('system-health:snapshot')->dailyAt('23:55');

Schedule::call(function () {
    // spec: safe-actions-one-submission.md — 3-day retention, expiry restores old behavior.
    IdempotencyKey::where('created_at', '<', now()->subDays(IdempotencyKey::RETENTION_DAYS))->delete();
})->dailyAt('03:00');
