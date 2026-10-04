@extends('portal.layouts.app')

@section('breadcrumbs')
    <span class="current">System Health</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">System Health</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">One calm place to see health at a glance. Counts only — what anyone typed is never shown.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="ajaxTable('{{ route('admin.system-health') }}')">
    <div class="flex justify-end mb-4">
        <button type="button" @click="reload()" class="px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 dark:bg-[#23274C] text-gray-700 dark:text-[#C1C4DC] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">Refresh</button>
    </div>

    <div x-show="loading && !html" class="space-y-4">
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 space-y-3">
            <div class="skelly sk-line-md w-32"></div>
            <div class="skelly sk-card"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <template x-for="i in 4" :key="i">
                <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-5">
                    <div class="skelly sk-line-sm w-24 mb-2"></div>
                    <div class="skelly sk-line-md w-16"></div>
                </div>
            </template>
        </div>
    </div>

    <div x-show="error" x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"><span x-text="error"></span><button type="button" @click="reload()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-red-200 hover:bg-red-100">Refresh</button></div>

    <div x-show="html || !loading" x-cloak x-ref="results" x-html="html" class="fade-in"></div>

    <div x-show="!loading && !html && !error">
        @include('portal.admin.partials.system-health-overview-results', ['cards' => $cards, 'acknowledged' => $acknowledged ?? [], 'ackInfo' => $ackInfo ?? [], 'trends' => $trends ?? ['labels' => [], 'abuse' => [], 'slow' => [], 'logins' => [], 'uptime' => []]])
    </div>

    @if(!($hasData ?? true))
        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center mt-6">
            <p class="text-sm text-gray-600 dark:text-[#C1C4DC]">All calm — no alerts in the last 24 hours. Refresh to re-check.</p>
        </div>
    @endif
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
function renderHealthPatterns() {
    if (typeof Chart === 'undefined') return;
    document.querySelectorAll('.health-patterns-canvas').forEach(function (cv) {
        try {
            var labels = JSON.parse(cv.dataset.labels || '[]');
            var abuse = JSON.parse(cv.dataset.abuse || '[]');
            var slow = JSON.parse(cv.dataset.slow || '[]');
            var logins = JSON.parse(cv.dataset.logins || '[]');
            if (cv._chart) { try { cv._chart.destroy(); } catch (e) {} }
            cv._chart = new Chart(cv, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Abuse', data: abuse, borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.08)', fill: false, tension: 0.4, pointBackgroundColor: '#EF4444', pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Slow', data: slow, borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.08)', fill: false, tension: 0.4, pointBackgroundColor: '#F59E0B', pointRadius: 4, pointHoverRadius: 6 },
                        { label: 'Logins', data: logins, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.08)', fill: false, tension: 0.4, pointBackgroundColor: '#3B82F6', pointRadius: 4, pointHoverRadius: 6 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 }, padding: 12 } } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                        x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
                    }
                }
            });
        } catch (e) { console.error('Patterns chart failed:', e); }
    });
    document.querySelectorAll('.health-uptime-canvas').forEach(function (cv) {
        try {
            var labels = JSON.parse(cv.dataset.labels || '[]');
            var values = JSON.parse(cv.dataset.values || '[]');
            if (cv._chart) { try { cv._chart.destroy(); } catch (e) {} }
            cv._chart = new Chart(cv, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: values.map(function (v) { return (parseInt(v, 10) === 1) ? '#22C55E' : '#EF4444'; }),
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (ctx) { return ' ' + (parseInt(ctx.raw, 10) === 1 ? 'OK' : 'Issue'); } } }
                    },
                    scales: {
                        y: { min: 0, max: 1, ticks: { stepSize: 1, font: { size: 11 }, callback: function (v) { return v === 1 ? 'OK' : (v === 0 ? 'Issue' : v); } }, grid: { color: '#f1f5f9' } },
                        x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
                    }
                }
            });
        } catch (e) { console.error('Uptime chart failed:', e); }
    });
    document.querySelectorAll('.health-mini-canvas').forEach(function (cv) {
        try {
            var labels = JSON.parse(cv.dataset.labels || '[]');
            var values = JSON.parse(cv.dataset.values || '[]');
            var title = cv.dataset.title || '';
            var color = cv.dataset.color || '#3B82F6';
            if (cv._chart) { try { cv._chart.destroy(); } catch (e) {} }
            cv._chart = new Chart(cv, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ label: title, data: values, borderColor: color, backgroundColor: color, fill: false, tension: 0.4, pointBackgroundColor: color, pointRadius: 3, pointHoverRadius: 5 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } }, grid: { color: '#f1f5f9' } },
                        x: { ticks: { font: { size: 9 }, maxRotation: 45 }, grid: { display: false } }
                    }
                }
            });
        } catch (e) { console.error('Mini chart failed:', e); }
    });
}
document.addEventListener('DOMContentLoaded', function () {
    renderHealthPatterns();
    var obs = new MutationObserver(function () { renderHealthPatterns(); });
    obs.observe(document.body, { childList: true, subtree: true });
});
</script>
@endsection
