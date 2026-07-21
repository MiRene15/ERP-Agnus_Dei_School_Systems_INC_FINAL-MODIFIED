@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('directress.graduation-fees') }}" class="no-underline" style="color: var(--muted);">Graduation Fees</a>
    <span class="opacity-40">/</span>
    <span class="current">Assign Students</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Assign Graduation Fee</h2>
    <p class="text-gray-600 mt-1">{{ $graduationFee->grade_level }} ({{ $graduationFee->school_year }}) — ₱ {{ number_format($graduationFee->graduation_fee + $graduationFee->other_fees, 2) }} per student</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif

<div class="mb-4">
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by student name..."
               class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->filled('search'))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <form method="POST" action="{{ route('directress.graduation-fees.assign.store', $graduationFee) }}">
        @csrf
        <div class="mb-4">
            <div class="flex items-center gap-2 mb-2">
                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="select-all" class="text-sm font-medium text-gray-700">Select All Unassigned</label>
            </div>
        </div>

        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 w-10"></th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student Name</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                    @if(!in_array($enrollment->student_id, $alreadyAssigned))
                    <tr class="border-b border-gray-100">
                        <td class="py-2 px-2">
                            <input type="checkbox" name="student_ids[]" value="{{ $enrollment->id }}"
                                   class="student-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="py-2 px-2 text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</td>
                        <td class="py-2 px-2 text-gray-600">{{ $enrollment->section->section_name ?? '—' }}</td>
                        <td class="py-2 px-2"><span class="text-xs font-medium text-green-600">Available</span></td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-gray-500 text-sm">No active enrollments found for this grade level.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Assign Selected Students</button>
    </form>
</div>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
