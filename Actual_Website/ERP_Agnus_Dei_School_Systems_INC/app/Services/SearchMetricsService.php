<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchMetricsService
{
    private const SLOW_THRESHOLD_MS = 2000;

    public static function recordHit(string $route): void
    {
        $day = date('Y-m-d');
        $route = $route !== '' ? $route : 'unknown';
        Cache::increment("search_hits:{$day}:{$route}");
        self::rememberRoute($route);
        Log::warning('Search throttled', ['route' => $route]);
    }

    public static function recordSlow(string $route, int $ms): void
    {
        if ($ms < self::SLOW_THRESHOLD_MS) {
            return;
        }
        $day = date('Y-m-d');
        $route = $route !== '' ? $route : 'unknown';
        Cache::increment("search_slow:{$day}:{$route}");
        self::rememberRoute($route);
        Log::warning('Slow search list', ['route' => $route, 'ms' => $ms]);
    }

    /** @return array{hits: array<string,int>, slow: array<string,int>} */
    public static function summary(): array
    {
        $day = date('Y-m-d');
        /** @var string[] $routes */
        $routes = Cache::get('search_metrics_routes', []);
        $hits = [];
        $slow = [];
        foreach ($routes as $route) {
            $hits[$route] = (int) Cache::get("search_hits:{$day}:{$route}", 0);
            $slow[$route] = (int) Cache::get("search_slow:{$day}:{$route}", 0);
        }
        arsort($hits);
        arsort($slow);

        return ['hits' => $hits, 'slow' => $slow];
    }

    private static function rememberRoute(string $route): void
    {
        /** @var string[] $routes */
        $routes = Cache::get('search_metrics_routes', []);
        if (! in_array($route, $routes, true)) {
            $routes[] = $route;
            Cache::put('search_metrics_routes', $routes, 86400 * 7);
        }
    }
}
