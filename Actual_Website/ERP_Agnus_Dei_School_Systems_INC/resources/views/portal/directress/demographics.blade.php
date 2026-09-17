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
        <canvas id="chartGrade" height="200"></canvas>
        @foreach($byGrade as $grade => $count)
            @php $pct = $total ? round($count / $total * 100, 1) : 0; @endphp
            <div class="mb-2">
                <div class="flex justify-between text-xs mb-1"><span class="text-gray-700">{{ $grade }}</span><span class="font-medium">{{ $count }} ({{ $pct }}%)</span></div>
                <div class="w-full bg-gray-100 rounded-full h-1.5"><div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div></div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By Section</h3>
        @foreach($bySection as $sec => $count)
            @php $pct = $total ? round($count / $total * 100, 1) : 0; @endphp
            <div class="mb-3">
                <div class="flex justify-between text-sm mb-1"><span class="text-gray-700">Section {{ $sec }}</span><span class="font-medium">{{ $count }} ({{ $pct }}%)</span></div>
                <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-teal-500 h-2 rounded-full" style="width: {{ $pct }}%"></div></div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By School Year</h3>
        @foreach($byYear as $year => $count)
            @php $pct = $total ? round($count / $total * 100, 1) : 0; @endphp
            <div class="mb-3">
                <div class="flex justify-between text-sm mb-1"><span class="text-gray-700">{{ $year }}</span><span class="font-medium">{{ $count }}</span></div>
                <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-amber-500 h-2 rounded-full" style="width: {{ $pct }}%"></div></div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">By Strand (SHS)</h3>
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
@endsection
