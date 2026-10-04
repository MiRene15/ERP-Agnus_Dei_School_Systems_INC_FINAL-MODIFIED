<div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-[#E8EAF6] mb-3">Trends (14 days)</h3>
    @include('portal.admin.partials.system-health-charts', ['trends' => $trends ?? ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []]])
</div>
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    @include('portal.admin.partials.system-health-results', ['cards' => $cards, 'acknowledged' => $acknowledged ?? [], 'ackInfo' => $ackInfo ?? []])
</div>
