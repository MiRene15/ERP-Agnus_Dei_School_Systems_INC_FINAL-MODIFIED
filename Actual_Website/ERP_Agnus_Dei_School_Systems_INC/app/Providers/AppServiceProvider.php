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

        // Search-list backstop (Child 2 v1): generous per-person per-minute on
        // GET list/search routes only. Normal rush (Child 1 calm: 1 per 600ms
        // pause) never hits 60/min sustained; spam/stuck-key/bypass does.
        // Never applied to POST money/grade routes. Same rule year-round.
        RateLimiter::for('search', function (Request $request) {
            // Throttle actual search requests only; full-page shells bypass
            // so a throttled reload never shows raw JSON (back button safe).
            $isSearchRequest = $request->boolean('ajax')
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || str_contains($request->path(), '/search');
            if (! $isSearchRequest) {
                return Limit::none();
            }

            return Limit::perMinute(60)
                ->by('search:' . ($request->user()?->id ?: $request->ip()))
                ->response(function (Request $request) {
                    $route = (string) ($request->route()?->getName() ?? $request->path());
                    \App\Services\SearchMetricsService::recordHit($route);
                    try {
                        log_activity('search', 'Search throttled', 'Search wait shown (counts only, no search words stored).', ['route' => $route]);
                    } catch (\Throwable $e) {
                        // Metrics must never break search.
                    }

                    // Truthful countdown: actual seconds until the window frees,
                    // so 0 always means the next search runs through.
                    $uid = $request->user()?->id ?: $request->ip();
                    $retry = (int) \Illuminate\Support\Facades\RateLimiter::availableIn('search:' . $uid);
                    if ($retry < 1) {
                        $retry = 1;
                    }
                    if ($retry > 120) {
                        $retry = 60;
                    }

                    return response()->json(['message' => 'Too many searches - wait a few seconds.'], 429, ['Retry-After' => (string) $retry]);
                });
        });
    }
}
