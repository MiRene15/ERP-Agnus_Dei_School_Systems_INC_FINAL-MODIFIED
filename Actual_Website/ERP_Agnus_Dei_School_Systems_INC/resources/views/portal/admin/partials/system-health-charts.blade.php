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
        <p class="text-sm text-gray-600 dark:text-[#C1C4DC]">Collecting trends — run the daily snapshot, then graphs appear here. Counts only, never search words.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($metrics as $key => $m)
            @php
                $vals = array_values($m['values']);
                $max = max(array_merge([1], $vals));
                $w = 280; $h = 60; $n = max(count($vals), 1);
                $pts = [];
                foreach ($vals as $i => $v) {
                    $x = $n > 1 ? ($i / ($n - 1)) * $w : $w / 2;
                    $y = $h - (($v / $max) * ($h - 8)) - 4;
                    $pts[] = round($x, 1) . ',' . round($y, 1);
                }
            @endphp
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold text-gray-900 dark:text-[#E8EAF6]">{{ $m['title'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-[#8A90B0]">max {{ max($vals) }}</p>
                </div>
                <svg viewBox="0 0 280 60" class="w-full h-16 mt-2" role="img" aria-label="{{ $m['title'] }} line graph">
                    <polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="{{ $m['color'] }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach($pts as $p)
                        <circle cx="{{ explode(',', $p)[0] }}" cy="{{ explode(',', $p)[1] }}" r="2.5" fill="{{ $m['color'] }}" />
                    @endforeach
                </svg>
                <p class="mt-1 text-[11px] text-gray-500 dark:text-[#8A90B0]">{{ reset($labels) }} → {{ end($labels) }}</p>
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-[11px] text-gray-500 dark:text-[#8A90B0]">Trends update daily via snapshot. Counts only — what anyone typed is never shown.</p>
@endif
