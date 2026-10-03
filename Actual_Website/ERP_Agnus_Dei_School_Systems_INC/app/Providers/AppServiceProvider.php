<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Public inquiry protection: 5 attempts per IP per hour, 3 per email
        // per day. Exceeding either locks the submitter out until the window
        // decays (up to an hour). Matches the capstone inquiry safeguards.
        RateLimiter::for('inquiry', function (Request $request) {
            $email = strtolower(trim((string) $request->input('personal_email')));
            return [
                Limit::perHour(5)->by('inquiry-ip:' . $request->ip()),
                Limit::perDay(3)->by('inquiry-email:' . ($email !== '' ? $email : $request->ip())),
            ];
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
