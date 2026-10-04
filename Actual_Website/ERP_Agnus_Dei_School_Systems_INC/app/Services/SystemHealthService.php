<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SystemHealthDaily;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthService
{
    private const ABUSE_YELLOW_MIN = 1;
    private const ABUSE_RED_MIN = 10;
    private const SLOW_YELLOW_MIN = 1;
    private const SLOW_RED_MIN = 3;
    private const LOGIN_FAIL_YELLOW_MIN = 5;
    private const LOGIN_FAIL_RED_MIN = 20;

    /**
     * @return array{cards: array<string, array{status: string, label: string, count: int, details: array<int, array{area: string, when: string, times: int}>}>, badge: int, hasData: bool, acknowledged: array<int, string>, ackInfo: array<string, array{by: string, at: string}>}
     */
    public static function overview(): array
    {
        $metrics = SearchMetricsService::summary();
        $hits = $metrics['hits'] ?? [];
        $slow = $metrics['slow'] ?? [];

        $since = now()->subDay();
        $loginFailed = ActivityLog::where('event', 'Login Failed')->where('created_at', '>=', $since)->count();
        $loginLocked = ActivityLog::where('event', 'Login Locked Out')->where('created_at', '>=', $since)->count();
        $newAccounts = User::where('created_at', '>=', $since)->count();

        $uptime = self::uptime();

        $abuseTotal = array_sum($hits);
        $abuseMax = $hits !== [] ? max($hits) : 0;
        $abuseStatus = 'green';
        $abuseLabel = 'All calm';
        if ($abuseMax >= self::ABUSE_RED_MIN) {
            $abuseStatus = 'red';
            $abuseLabel = 'Possible abuse — tap to review and Acknowledge.';
        } elseif ($abuseMax >= self::ABUSE_YELLOW_MIN) {
            $abuseStatus = 'yellow';
            $abuseLabel = 'Unusual searches — tap for details.';
        }

        $slowTotal = array_sum($slow);
        $slowStatus = 'green';
        $slowLabel = 'All calm';
        if ($slowTotal >= self::SLOW_RED_MIN) {
            $slowStatus = 'red';
            $slowLabel = 'Slow list — tap to review and Acknowledge.';
        } elseif ($slowTotal >= self::SLOW_YELLOW_MIN) {
            $slowStatus = 'yellow';
            $slowLabel = 'Slow list — tap for details.';
        }

        $loginTotal = $loginFailed + $loginLocked;
        $loginStatus = 'green';
        $loginLabel = 'All calm';
        if ($loginLocked > 0 || $loginFailed >= self::LOGIN_FAIL_RED_MIN) {
            $loginStatus = 'red';
            $loginLabel = 'Possible abuse — logins spiking.';
        } elseif ($loginFailed >= self::LOGIN_FAIL_YELLOW_MIN || $newAccounts >= 20) {
            $loginStatus = 'yellow';
            $loginLabel = 'Unusual logins — tap for details.';
        }

        $cards = [
            'uptime' => [
                'status' => $uptime['status'],
                'label' => $uptime['label'],
                'count' => 0,
                'details' => $uptime['details'],
            ],
            'abuse' => [
                'status' => $abuseStatus,
                'label' => $abuseStatus === 'green' ? 'All calm' : $abuseLabel,
                'count' => (int) $abuseTotal,
                'details' => self::details($hits),
            ],
            'slow' => [
                'status' => $slowStatus,
                'label' => $slowStatus === 'green' ? 'All calm' : $slowLabel,
                'count' => (int) $slowTotal,
                'details' => self::details($slow),
            ],
            'logins' => [
                'status' => $loginStatus,
                'label' => $loginStatus === 'green' ? 'All calm' : $loginLabel,
                'count' => (int) $loginTotal,
                'details' => array_values(array_filter([
                    ['area' => 'Failed logins (24h)', 'when' => 'Last 24 hours', 'times' => (int) $loginFailed],
                    ['area' => 'Locked logins (24h)', 'when' => 'Last 24 hours', 'times' => (int) $loginLocked],
                    ['area' => 'New accounts (24h)', 'when' => 'Last 24 hours', 'times' => (int) $newAccounts],
                ], fn ($row) => (int) $row['times'] > 0)),
            ],
        ];

        $badge = self::unacknowledgedCriticalCount($cards);
        $hasData = $abuseTotal > 0 || $slowTotal > 0 || $loginTotal > 0 || $newAccounts > 0;
        $states = self::rowStates($cards);
        $acknowledged = [];
        $ackInfo = self::latestAckByType();
        foreach ($states as $type => $info) {
            if (($info['allCovered'] ?? false) && ($info['hasAck'] ?? false)) {
                $acknowledged[] = $type;
            }
        }

        return ['cards' => $cards, 'badge' => $badge, 'hasData' => $hasData, 'acknowledged' => $acknowledged, 'ackInfo' => $ackInfo, 'rowStates' => $states];
    }

    /**
     * @param array<string, mixed> $rowsMap
     */
    public static function acknowledge(string $alertType, string $route, int $counts, int $userId, array $rowsMap = []): void
    {
        $clean = [];
        foreach ($rowsMap as $area => $times) {
            $area = (string) $area;
            if ($area === '') {
                continue;
            }
            $clean[$area] = (int) $times;
        }

        ActivityLog::create([
            'subject_type' => 'system-health',
            'subject_id' => null,
            'causer_id' => $userId,
            'event' => 'System Health Acknowledged',
            'description' => 'Acknowledged ' . $alertType . ' alert' . ($route !== '' ? ' for ' . $route : '') . '.',
            'properties' => [
                'alert_type' => $alertType,
                'route' => $route,
                'counts' => $counts,
                'rows' => $clean,
                'acknowledged_at' => now()->toDateTimeString(),
            ],
        ]);

        Cache::forget('system_health_badge');
    }

    /**
     * Strict per-row NEW check: a row needs a button only when its current
     * count exceeds the count stored at the last covering acknowledge.
     *
     * @param array<string, array{details?: array<int, array{area?: string, times?: int}>}> $cards
     * @return array<string, array{rows: array<string, array{needsAck: bool, by: string, at: string}>, anyNew: bool, allCovered: bool, hasAck: bool}>
     */
    public static function rowStates(array $cards): array
    {
        $acks = ActivityLog::with('causer')
            ->where('event', 'System Health Acknowledged')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(50)
            ->get();

        $out = [];
        foreach ($cards as $type => $card) {
            $rows = $card['details'] ?? [];
            $rowOut = [];
            $anyNew = false;
            $hasAck = false;
            foreach ($rows as $row) {
                $area = (string) ($row['area'] ?? '');
                $times = (int) ($row['times'] ?? 0);
                if ($area === '' || $times <= 0) {
                    continue;
                }
                $cover = self::coveringAck($acks, (string) $type, $area, $times);
                if ($cover !== null) {
                    $hasAck = true;
                }
                $needs = $cover === null;
                if ($needs) {
                    $anyNew = true;
                }
                $rowOut[$area] = [
                    'needsAck' => $needs,
                    'by' => $cover['by'] ?? '',
                    'at' => $cover['at'] ?? '',
                ];
            }
            $out[(string) $type] = [
                'rows' => $rowOut,
                'anyNew' => $anyNew,
                'allCovered' => $rowOut !== [] && ! $anyNew,
                'hasAck' => $hasAck,
            ];
        }

        return $out;
    }

    /**
     * @param \Illuminate\Support\Collection<int, ActivityLog> $acks
     * @return array{by: string, at: string}|null null = NEW, needs a button.
     */
    private static function coveringAck($acks, string $type, string $area, int $times): ?array
    {
        foreach ($acks as $ack) {
            $props = is_array($ack->properties) ? $ack->properties : [];
            if ((string) ($props['alert_type'] ?? '') !== $type) {
                continue;
            }
            $ackRoute = (string) ($props['route'] ?? '');
            $rowsMap = $props['rows'] ?? null;
            if (is_array($rowsMap) && $rowsMap !== []) {
                if ($ackRoute === '' || $ackRoute === $area) {
                    if (array_key_exists($area, $rowsMap)) {
                        if ($times <= (int) $rowsMap[$area]) {
                            return ['by' => $ack->causer?->name ?? 'System', 'at' => $ack->created_at?->toDateTimeString() ?? ''];
                        }
                        return null;
                    }
                    if ($ackRoute === '') {
                        return null;
                    }
                    continue;
                }
                continue;
            }
            $stored = (int) ($props['counts'] ?? 0);
            if ($ackRoute === '' || $ackRoute === $area) {
                if ($times <= $stored) {
                    return ['by' => $ack->causer?->name ?? 'System', 'at' => $ack->created_at?->toDateTimeString() ?? ''];
                }
                return null;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function titles(): array
    {
        return [
            'uptime' => 'Uptime',
            'abuse' => 'Abuse / Spam Alerts',
            'slow' => 'Slow Lists',
            'logins' => 'Logins + New Accounts Spikes',
        ];
    }

    /**
     * @return array{type: string, title: string, status: string, label: string, count: int, rows: array<int, array{area: string, when: string, times: int, needsAck: bool, ackBy: string, ackAt: string}>, acknowledged: bool, bulkNeedsAck: bool, acknowledgements: array<int, array{by: string, at: string, route: string, counts: int}>}
     */
    public static function forDetail(string $type): array
    {
        $overview = self::overview();
        $card = $overview['cards'][$type] ?? ['status' => 'green', 'label' => 'All calm', 'count' => 0, 'details' => []];
        $acks = self::acknowledgements($type);
        $states = $overview['rowStates'][$type] ?? ['rows' => [], 'anyNew' => false, 'allCovered' => false, 'hasAck' => false];
        $rows = [];
        foreach ($card['details'] ?? [] as $row) {
            $area = (string) ($row['area'] ?? '');
            $st = $states['rows'][$area] ?? ['needsAck' => true, 'by' => '', 'at' => ''];
            $rows[] = [
                'area' => $area,
                'when' => (string) ($row['when'] ?? ''),
                'times' => (int) ($row['times'] ?? 0),
                'needsAck' => (bool) ($st['needsAck'] ?? true),
                'ackBy' => (string) ($st['by'] ?? ''),
                'ackAt' => (string) ($st['at'] ?? ''),
            ];
        }

        return [
            'type' => $type,
            'title' => self::titles()[$type] ?? $type,
            'status' => (string) ($card['status'] ?? 'green'),
            'label' => (string) ($card['label'] ?? 'All calm'),
            'count' => (int) ($card['count'] ?? 0),
            'rows' => $rows,
            'acknowledged' => ! ($states['anyNew'] ?? true) && ($states['hasAck'] ?? false),
            'bulkNeedsAck' => (bool) ($states['anyNew'] ?? false),
            'acknowledgements' => $acks,
        ];
    }

    /**
     * @return array<int, array{by: string, at: string, route: string, counts: int}>
     */
    public static function acknowledgements(string $type): array
    {
        $rows = ActivityLog::with('causer')
            ->where('event', 'System Health Acknowledged')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(10)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $props = is_array($row->properties) ? $row->properties : [];
            if ((string) ($props['alert_type'] ?? '') !== $type) {
                continue;
            }
            $out[] = [
                'by' => $row->causer?->name ?? 'System',
                'at' => $row->created_at?->toDateTimeString() ?? '',
                'route' => (string) ($props['route'] ?? ''),
                'counts' => (int) ($props['counts'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array{status: string, label: string, details: array<int, array{area: string, when: string, times: int}>}
     */
    private static function uptime(): array
    {
        try {
            DB::connection()->getPdo();
            Cache::put('health_ping', 1, 60);
            $ok = (int) Cache::get('health_ping', 0) === 1;

            if ($ok) {
                return ['status' => 'green', 'label' => 'All calm', 'details' => []];
            }

            return [
                'status' => 'red',
                'label' => 'Health data unavailable — Refresh.',
                'details' => [['area' => 'Cache', 'when' => 'Now', 'times' => 1]],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'red',
                'label' => 'Health data unavailable — Refresh.',
                'details' => [['area' => 'Database', 'when' => 'Now', 'times' => 1]],
            ];
        }
    }

    /**
     * @param array<string, int> $counts
     * @return array<int, array{area: string, when: string, times: int}>
     */
    private static function details(array $counts): array
    {
        $out = [];
        foreach ($counts as $route => $times) {
            if ((int) $times <= 0) {
                continue;
            }
            $out[] = ['area' => (string) $route, 'when' => 'Today', 'times' => (int) $times];
        }

        return array_slice($out, 0, 20);
    }

    /**
     * @return array{date: string, abuse_hits: int, slow_total: int, login_failed: int, login_locked: int, new_accounts: int, uptime_ok: bool}
     */
    public static function dailyMetrics(?string $date = null): array
    {
        $day = $date ?? date('Y-m-d');
        $isToday = $day === date('Y-m-d');

        if ($isToday) {
            $overview = self::overview();
            $abuse = (int) ($overview['cards']['abuse']['count'] ?? 0);
            $slow = (int) ($overview['cards']['slow']['count'] ?? 0);
            $uptimeOk = ($overview['cards']['uptime']['status'] ?? 'green') === 'green';
        } else {
            $abuse = 0;
            $slow = 0;
            $uptimeOk = true;
        }

        $loginFailed = ActivityLog::where('event', 'Login Failed')->whereDate('created_at', $day)->count();
        $loginLocked = ActivityLog::where('event', 'Login Locked Out')->whereDate('created_at', $day)->count();
        $newAccounts = User::whereDate('created_at', $day)->count();

        return [
            'date' => $day,
            'abuse_hits' => $abuse,
            'slow_total' => $slow,
            'login_failed' => $loginFailed,
            'login_locked' => $loginLocked,
            'new_accounts' => $newAccounts,
            'uptime_ok' => $uptimeOk,
        ];
    }

    public static function snapshot(?string $date = null): void
    {
        $metrics = self::dailyMetrics($date);

        SystemHealthDaily::updateOrCreate(
            ['date' => $metrics['date']],
            [
                'abuse_hits' => $metrics['abuse_hits'],
                'slow_total' => $metrics['slow_total'],
                'login_failed' => $metrics['login_failed'],
                'login_locked' => $metrics['login_locked'],
                'new_accounts' => $metrics['new_accounts'],
                'uptime_ok' => $metrics['uptime_ok'],
            ]
        );
    }

    /**
     * @return array{labels: array<int, string>, abuse: array<int, int>, slow: array<int, int>, logins: array<int, int>, uptime: array<int, int>}
     */
    public static function trends(int $days = 14): array
    {
        try {
            $rows = SystemHealthDaily::orderBy('date')
                ->take($days)
                ->get(['date', 'abuse_hits', 'slow_total', 'login_failed', 'login_locked', 'uptime_ok']);
        } catch (\Throwable $e) {
            return ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []];
        }

        $labels = [];
        $abuse = [];
        $slow = [];
        $logins = [];
        $uptime = [];
        foreach ($rows as $row) {
            $labels[] = $row->date instanceof \DateTimeInterface ? $row->date->format('m/d') : (string) $row->date;
            $abuse[] = (int) $row->abuse_hits;
            $slow[] = (int) $row->slow_total;
            $logins[] = (int) $row->login_failed + (int) $row->login_locked;
            $uptime[] = $row->uptime_ok ? 1 : 0;
        }

        return ['labels' => $labels, 'abuse' => $abuse, 'slow' => $slow, 'logins' => $logins, 'uptime' => $uptime];
    }

    /**
     * @return array<int, string>
     */
    private static function acknowledgedTypes(): array
    {
        $since = now()->subDay();

        return ActivityLog::where('event', 'System Health Acknowledged')
            ->where('created_at', '>=', $since)
            ->get(['properties'])
            ->map(fn ($row) => (string) ($row->properties['alert_type'] ?? ''))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array{by: string, at: string}>
     */
    private static function latestAckByType(): array
    {
        $rows = ActivityLog::with('causer')
            ->where('event', 'System Health Acknowledged')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->take(20)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $props = is_array($row->properties) ? $row->properties : [];
            $type = (string) ($props['alert_type'] ?? '');
            if ($type === '' || isset($out[$type])) {
                continue;
            }
            $out[$type] = [
                'by' => $row->causer?->name ?? 'System',
                'at' => $row->created_at?->toDateTimeString() ?? '',
            ];
        }

        return $out;
    }

    /**
     * @param array<string, array{status: string}> $cards
     */
    private static function unacknowledgedCriticalCount(array $cards): int
    {
        $states = self::rowStates($cards);
        $count = 0;
        foreach ($cards as $type => $card) {
            if (($card['status'] ?? 'green') !== 'red') {
                continue;
            }
            $info = $states[(string) $type] ?? null;
            if ($info === null) {
                $count++;
                continue;
            }
            if (($info['rows'] ?? []) === []) {
                if (! ($info['hasAck'] ?? false)) {
                    $count++;
                }
                continue;
            }
            if ($info['anyNew'] ?? true) {
                $count++;
            }
        }

        return $count;
    }
}
