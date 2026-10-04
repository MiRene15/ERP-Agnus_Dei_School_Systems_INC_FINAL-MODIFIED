<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SearchMetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackSearchMetrics
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = (int) (microtime(true) * 1000);
        /** @var Response $response */
        $response = $next($request);
        $ms = (int) (microtime(true) * 1000) - $start;

        // Counts only; never log raw search input (names/LRN).
        $route = (string) ($request->route()?->getName() ?? $request->path());
        SearchMetricsService::recordSlow($route, $ms);

        return $response;
    }
}
