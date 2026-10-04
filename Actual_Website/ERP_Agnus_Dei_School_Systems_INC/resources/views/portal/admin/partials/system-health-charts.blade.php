@php
    $metrics = [
        'uptime' => ['title' => 'Uptime checks (14d)', 'color' => '#22C55E', 'values' => $trends['uptime'] ?? []],
        'abuse' => ['title' => 'Abuse hits (14d)', 'color' => '#EF4444', 'values' => $trends['abuse'] ?? []],
        'slow' => ['title' => 'Slow lists (14d)', 'color' => '#F59E0B', 'values' => $trends['slow'] ?? []],
        'logins' => ['title' => 'Login spikes (14d)', 'color' => '#3B82F6', 'values' => $trends['logins'] ?? []],
    ];
    if (!empty($only)) {
        $metrics = array_intersect_key($metrics, array_flip((array) $only));
    }
    $labels = $trends['labels'] ?? [];
@endphp
@if(empty($labels))
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
        <p class="text-sm text-gray-600 dark:text-[#C1C4DC]">Collecting trends — run the daily snapshot, then graphs appear here. Run again tomorrow for lines. Counts only, never search words.</p>
    </div>
@else
    @php
        $showCombined = empty($only ?? null);
        $abuseVals = array_values($trends['abuse'] ?? []);
        $slowVals = array_values($trends['slow'] ?? []);
        $loginVals = array_values($trends['logins'] ?? []);
    @endphp
    @if($showCombined)
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold text-gray-900 dark:text-[#E8EAF6]">Patterns (14d)</p>
                <p class="text-[11px] text-gray-500 dark:text-[#8A90B0]">{{ reset($labels) }} → {{ end($labels) }}</p>
            </div>
            <div style="height: 260px; position: relative;"><canvas class="health-patterns-canvas" data-labels='@json($labels)' data-abuse='@json($abuseVals)' data-slow='@json($slowVals)' data-logins='@json($loginVals)'></canvas></div>
            @if(count($labels) <= 1)
                <p class="mt-1 text-[11px] text-gray-500 dark:text-[#8A90B0]">Single day — run snapshot tomorrow for lines.</p>
            @endif
        </div>
    @endif
    @if($showCombined)
        @php
            $uptimeVals = array_values($trends['uptime'] ?? []);
        @endphp
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 mb-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-900 dark:text-[#E8EAF6]">Uptime (14d)</p>
                <p class="text-[11px] text-gray-500 dark:text-[#8A90B0]">{{ reset($labels) }} → {{ end($labels) }}</p>
            </div>
            <div style="height: 160px; position: relative;"><canvas class="health-uptime-canvas" data-labels='@json($labels)' data-values='@json($uptimeVals)'></canvas></div>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-[#8A90B0]">Tall = OK, flat = issue.</p>
        </div>
    @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($metrics as $key => $m)
            @php
                $vals = array_values($m['values']);
            @endphp
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $m['title'] }}</p>
                    <p class="text-[11px] text-gray-500 dark:text-[#8A90B0]">{{ reset($labels) }} → {{ end($labels) }}</p>
                </div>
                <div style="height: 180px; position: relative;"><canvas class="health-mini-canvas" data-labels='@json($labels)' data-values='@json($vals)' data-title="{{ $m['title'] }}" data-color="{{ $m['color'] }}"></canvas></div>
                @if(count($vals) <= 1)
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-[#8A90B0]">Single day — run snapshot tomorrow for lines.</p>
                @endif
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-[11px] text-gray-500 dark:text-[#8A90B0]">Trends update daily via snapshot. Counts only — what anyone typed is never shown.</p>
@endif
