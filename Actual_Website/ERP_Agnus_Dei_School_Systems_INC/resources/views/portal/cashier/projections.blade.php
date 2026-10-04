@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('cashier.dashboard') }}" style="color: var(--muted);">Dashboard</a><span class="opacity-40">/</span><span class="current">Projections</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Projections</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Monthly collections vs receivables.</p>
</div>
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4 mb-6">
    <div class="flex flex-wrap gap-3 items-end">
        <form method="GET" action="{{ route('cashier.projections') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">School Year</label>
                <select name="school_year" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
                    @foreach($schoolYears as $sy)
                        <option value="{{ $sy }}" {{ $sy === $schoolYear ? 'selected' : '' }}>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Use School Year</button>
        </form>
        <form method="GET" action="{{ route('cashier.projections') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" max="{{ date('Y-m-d') }}" min="{{ $earliestDate }}" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-[#8A90B0] mb-1">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" max="{{ date('Y-m-d') }}" min="{{ $earliestDate }}" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] text-sm">
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Apply Dates</button>
        </form>
    </div>
    <p class="mt-3 text-xs text-gray-500 dark:text-[#8A90B0]">
        @if($usingCustomDates)
            Showing your chosen dates. Use School Year to go back.
        @else
            Showing the school year. Change the dates to override.
        @endif
        Currently showing <span class="font-semibold">{{ $periodLabel }}</span>.
    </p>
</div>
<div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    @include('portal.cashier.partials.projection-summary-cards')
</div>
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    @if(count(array_filter($collected)) === 0)
        <p class="text-center text-gray-500 dark:text-[#8A90B0] py-12">No Collections Yet</p>
    @else
        <div style="height: 400px; position: relative;">
            <canvas id="projectionChart"></canvas>
        </div>
    @endif
</div>
@if(count(array_filter($collected)) > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('projectionChart'), {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [
                { label: 'Collected', data: @json($collected), borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.08)', fill: false, tension: 0.4, pointBackgroundColor: '#10B981', pointRadius: 4, pointHoverRadius: 6 },
                { label: 'Receivables', data: @json($outstanding), borderColor: '#EF4444', backgroundColor: 'rgba(239,68,68,0.08)', fill: false, tension: 0.4, pointBackgroundColor: '#EF4444', pointRadius: 4, pointHoverRadius: 6 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 }, padding: 12 } },
                tooltip: { callbacks: { label: function (ctx) { return ' ' + ctx.dataset.label + ': ₱' + Number(ctx.raw).toLocaleString(); } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 11 }, callback: function (v) { return '₱' + v.toLocaleString(); } }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
            }
        }
    });
});
</script>
@endif
@endsection
