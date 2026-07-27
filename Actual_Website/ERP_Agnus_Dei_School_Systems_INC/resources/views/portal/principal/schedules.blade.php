@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Schedules</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Class Schedules</h2>
    <p class="text-gray-600 mt-1">View and manage class schedules per grade level.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($gradeLevels as $gl)
    <a href="{{ route('principal.schedules', ['grade_level' => $gl]) }}"
       class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $selectedGrade === $gl ? 'text-white' : 'text-gray-600 bg-gray-100 hover:bg-gray-200' }}"
       style="{{ $selectedGrade === $gl ? 'background: var(--navy);' : '' }}">
        {{ $gl }}
    </a>
    @endforeach
</div>

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by subject or teacher..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="day" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All Days</option>
            @foreach($days as $d)
                <option value="{{ $d }}" {{ request('day') === $d ? 'selected' : '' }}>{{ $d }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        @if(request()->anyFilled(['search', 'day']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">{{ $selectedGrade }} — {{ active_school_year() }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Subject</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Teacher</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    @foreach($days as $day)
                    <th class="text-left py-3 px-2 font-medium text-gray-600">{{ substr($day, 0, 3) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $class->subject->name ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $class->teacher->name ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $class->section }}</td>
                    @foreach($days as $day)
                    @php $slot = $class->schedules->firstWhere('day_of_week', $day); @endphp
                    <td class="py-2 px-2 text-gray-600 text-xs">
                        @if($slot)
                            {{ substr($slot->start_time, 0, 5) }}–{{ substr($slot->end_time, 0, 5) }}
                            <br><span class="text-gray-400">{{ $slot->room ?? '' }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 3 + count($days) }}" class="py-6 text-center text-gray-500 text-sm">No classes scheduled for {{ $selectedGrade }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
