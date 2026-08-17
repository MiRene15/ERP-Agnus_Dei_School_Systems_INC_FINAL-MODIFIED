@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Class Schedule</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Class Schedule</h2>
    <p class="text-gray-600 mt-1">{{ $activeEnrollment->section->grade_level }} - {{ $activeEnrollment->section->section_name }} &middot; {{ $activeEnrollment->school_year }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    @if($activeEnrollment->subjects->isNotEmpty())
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="text-left py-3 px-3 font-semibold text-gray-600 border border-gray-200">Time</th>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                    <th class="text-center py-3 px-2 font-semibold text-gray-600 border border-gray-200">{{ substr($day, 0, 3) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($scheduleSlots as $slot)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-3 font-medium text-gray-700 border border-gray-200 text-xs whitespace-nowrap">{{ $slot['time'] }}</td>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                    <td class="py-3 px-2 text-center border border-gray-200">
                        @if(isset($slot['days'][$day]))
                            <div class="text-xs font-medium text-gray-900">{{ $slot['days'][$day]['subject'] }}</div>
                            <div class="text-xs text-gray-500">{{ $slot['days'][$day]['teacher'] }}</div>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-sm text-gray-400 border border-gray-200">No schedules set.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @else
    <p class="text-sm text-gray-500 text-center py-4">No subjects assigned yet.</p>
    @endif
</div>
@endsection
