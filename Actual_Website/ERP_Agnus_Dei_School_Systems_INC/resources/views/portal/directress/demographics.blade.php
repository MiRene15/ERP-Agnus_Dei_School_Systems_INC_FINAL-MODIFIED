@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Demographics</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Demographics</h2>
    <p class="text-gray-600 mt-1">Enrollments categorized by grade, section, year, and strand. Total active: {{ $total }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By Grade Level</h3>
        <div style="height: 280px; position: relative;"><canvas id="chartGrade"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By Section</h3>
        <div style="height: 280px; position: relative;"><canvas id="chartSection"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By School Year</h3>
        <div style="height: 280px; position: relative;"><canvas id="chartYear"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By Strand (SHS)</h3>
        <div style="height: 280px; position: relative;">
            @forelse($byStrand as $strand => $count)
                @php $pct = $total ? round($count / $total * 100, 1) : 0; @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1"><span class="text-gray-700">{{ $strand ?: 'No Strand' }}</span><span class="font-medium">{{ $count }}</span></div>
                    <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-purple-500 h-2 rounded-full" style="width: {{ $pct }}%"></div></div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No strand data (non-SHS grades).</p>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Payment Collections ({{ date('Y') }})</h3>
        <p class="text-xs text-gray-500 mb-4">Total: ₱{{ number_format($totalCollected, 2) }} &middot; {{ $totalTransactions }} transactions</p>
        <div style="height: 280px; position: relative;"><canvas id="chartPaymentsMonthly"></canvas></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-1">Collections by Year</h3>
        <p class="text-xs text-gray-500 mb-4">All-time total: ₱{{ number_format($totalCollected, 2) }}</p>
        <div style="height: 280px; position: relative;"><canvas id="chartPaymentsYearly"></canvas></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const gradeLabels = @json($byGrade->keys());
    const gradeData = @json($byGrade->values());
    const sectionLabels = @json($bySection->keys());
    const sectionData = @json($bySection->values());
    const yearLabels = @json($byYear->keys());
    const yearData = @json($byYear->values());
    const monthLabels = @json($paymentsByMonth->keys());
    const monthData = @json($paymentsByMonth->values());
    const payYearLabels = @json($paymentsByYear->keys());
    const payYearData = @json($paymentsByYear->values());

    const colors = ['#24225C','#A39FE9','#E5C06A','#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#EC4899','#14B8A6','#6366F1'];

    new Chart(document.getElementById('chartGrade'), {
        type: 'bar',
        data: {
            labels: gradeLabels,
            datasets: [{
                label: 'Students',
                data: gradeData,
                backgroundColor: gradeData.map((_, i) => colors[i % colors.length]),
                borderRadius: 6,
                barPercentage: 0.7,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 10 }, maxRotation: 45 }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartSection'), {
        type: 'doughnut',
        data: {
            labels: sectionLabels,
            datasets: [{
                data: sectionData,
                backgroundColor: sectionData.map((_, i) => colors[i % colors.length]),
                borderWidth: 2, borderColor: '#fff',
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 }, padding: 10 } } },
            cutout: '55%',
        }
    });

    new Chart(document.getElementById('chartYear'), {
        type: 'line',
        data: {
            labels: yearLabels,
            datasets: [{
                label: 'Enrolled',
                data: yearData,
                borderColor: '#24225C',
                backgroundColor: 'rgba(36,34,92,0.08)',
                fill: true, tension: 0.4,
                pointBackgroundColor: '#24225C', pointRadius: 5, pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartPaymentsMonthly'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Collected (₱)',
                data: monthData,
                backgroundColor: 'rgba(16,185,129,0.7)',
                borderColor: '#10B981',
                borderWidth: 1,
                borderRadius: 6,
                barPercentage: 0.65,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 11 }, callback: v => '₱' + v.toLocaleString() }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartPaymentsYearly'), {
        type: 'line',
        data: {
            labels: payYearLabels,
            datasets: [{
                label: 'Collected (₱)',
                data: payYearData,
                borderColor: '#E5C06A',
                backgroundColor: 'rgba(229,192,106,0.15)',
                fill: true, tension: 0.4,
                pointBackgroundColor: '#E5C06A', pointRadius: 5, pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 11 }, callback: v => '₱' + v.toLocaleString() }, grid: { color: '#f1f5f9' } },
                x: { ticks: { font: { size: 11 } }, grid: { display: false } }
            }
        }
    });
});
</script>
@endsection
