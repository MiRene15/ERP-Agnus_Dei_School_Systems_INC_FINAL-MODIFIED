<div class="mb-6">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-[#E8EAF6] mb-3">Trend (14 days)</h3>
    @include('portal.admin.partials.system-health-charts', ['trends' => $trends ?? ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []], 'only' => [$detail['type'] === 'logins' ? 'logins' : $detail['type']]])
</div>
@include('portal.admin.partials.system-health-detail-results', ['detail' => $detail, 'rows' => $rows ?? ($detail['rows'] ?? [])])
