@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">School Years</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">School Years</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Manage academic years (July–March). Adding a year makes it available for fees and enrollments. Announcing a new year raises holds for returnees with dues.</p>
    @if(!empty($announced))
    <p class="text-sm mt-1 text-gray-600 dark:text-[#C1C4DC]">Announced year: <strong>{{ $announced }}</strong> · Active year: <strong>{{ $active ?? '' }}</strong></p>
    @endif
</div>
@if(session('success'))<div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ $errors->first() }}</div>@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 max-w-lg mb-6">
    <form method="POST" action="{{ route('directress.school-years.store') }}">
        @csrf
        <label class="block text-sm font-medium text-gray-700 mb-1">New School Year (YYYY-YYYY)</label>
        <div class="flex gap-2">
            <input type="text" name="school_year" placeholder="e.g. 2027-2028" pattern="\d{4}-\d{4}" required class="flex-1 rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm">
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Add</button>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 max-w-lg mb-6">
    <h3 class="text-sm font-semibold text-gray-900 dark:text-[#E8EAF6] mb-2">Exam weeks → grading closes 7 days later</h3>
    <form method="POST" action="{{ route('directress.school-years.exam-end') }}" class="flex flex-wrap gap-2 items-end">
        @csrf
        <div>
            <label class="block text-xs text-gray-600 mb-1">Grading period</label>
            <select name="grading_period" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach(['1st Term','2nd Term','3rd Term'] as $period)
                <option value="{{ $period }}">{{ $period }}{{ !empty($examEnds[$period]) ? ' (closes ' . \Carbon\Carbon::parse($examEnds[$period])->addDays(7)->format('M d, Y') . ')' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-600 mb-1">Last exam day</label>
            <input type="date" name="exam_end_date" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Save exam end</button>
    </form>
    <p class="text-xs text-gray-500 mt-2">Rescheduling moves the close date automatically (close = last exam day + 7 days). Teachers are notified via the grade-list banner.</p>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b bg-gray-50 dark:bg-[#161A33]"><th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">School Year</th><th class="text-right py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Fee Schedules</th><th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Status</th><th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Action</th></tr></thead>
        <tbody>
            @forelse($years as $row)
            <tr class="border-b border-gray-50">
                <td class="py-3 px-4 font-medium">{{ $row['period_label'] ?? $row['year'] }}
                    @if(!empty($row['is_active'])) <span class="ml-1 text-xs text-gray-500">· Active</span> @endif
                    @if(!empty($row['is_announced'])) <span class="ml-1 text-xs text-blue-600">· Announced</span> @endif
                </td>
                <td class="text-right py-3 px-4">{{ $row['count'] }}</td>
                <td class="text-center py-3 px-4">
                    @if($row['locked'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                            Locked
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Open</span>
                    @endif
                </td>
                <td class="text-center py-3 px-4">
                    @if(empty($row['is_announced']) && empty($row['locked']))
                    <form method="POST" action="{{ route('directress.school-years.announce') }}" class="inline mr-1">
                        @csrf
                        <input type="hidden" name="school_year" value="{{ $row['year'] }}">
                        <button type="submit" class="text-xs font-medium px-3 py-1 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Announce New School Year
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('directress.school-years.toggle-lock') }}" class="inline">
                        @csrf
                        <input type="hidden" name="school_year" value="{{ $row['year'] }}">
                        <button type="submit" class="text-xs font-medium px-3 py-1 rounded-lg transition {{ $row['locked'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                            {{ $row['locked'] ? 'Unlock' : 'Lock' }}
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="py-6 text-center text-gray-400">No school years yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
