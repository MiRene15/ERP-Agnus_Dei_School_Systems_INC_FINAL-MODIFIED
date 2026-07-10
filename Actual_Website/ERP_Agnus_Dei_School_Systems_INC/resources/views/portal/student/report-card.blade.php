@extends('portal.layouts.app')
@section('breadcrumbs')
    <span class="current">Report Card</span>
@endsection
@section('sidebar-links')
    @if($enrollment->student->student_number)
        <a href="#grades" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span class="sidebar-label">My Grades</span></a>
        <a href="#schedule" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="sidebar-label">Class Schedule</span></a>
        <a href="#ledger" class="sidebar-link"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg><span class="sidebar-label">Statement of Account</span></a>
        <a href="{{ route('student.report-card') }}" class="sidebar-link active"><svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="sidebar-label">Report Card</span></a>
    @endif
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">My Report Card</h2>
    <p class="text-gray-600 mt-1">{{ $enrollment->section->grade_level }} - {{ $enrollment->section->section_name ?? 'N/A' }} — {{ $enrollment->school_year }}</p>
</div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="mb-4">
        <p class="text-sm text-gray-500">LRN: <span class="font-medium text-gray-900">{{ $enrollment->student->student_number ?? 'N/A' }}</span></p>
        @if($enrollment->strand)
        <p class="text-sm text-gray-500">Strand: <span class="font-medium text-gray-900">{{ $enrollment->strand }}</span></p>
        @endif
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="text-left py-3 px-3 font-medium text-gray-600 border border-gray-200">Subject</th>
                    @foreach($gradingPeriods as $period)
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">{{ $period }}</th>
                    @endforeach
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Final</th>
                    <th class="text-center py-3 px-2 font-medium text-gray-600 border border-gray-200">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-3 font-medium text-gray-900 border border-gray-200">{{ $subject->subject }}</td>
                    @foreach($gradingPeriods as $period)
                    <td class="py-3 px-2 text-center text-gray-700 border border-gray-200">{{ $subject->{$period} }}</td>
                    @endforeach
                    <td class="py-3 px-2 text-center font-semibold text-gray-900 border border-gray-200">{{ $subject->final }}</td>
                    <td class="py-3 px-2 text-center border border-gray-200">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subject->remarks === 'Passed' ? 'bg-green-100 text-green-700' : ($subject->remarks === 'Failed' ? 'bg-red-100 text-red-700' : '') }}">
                            {{ $subject->remarks }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-sm text-gray-400 border border-gray-200">No grades available yet.</td>
                </tr>
                @endforelse
            </tbody>
            @if($subjects->isNotEmpty())
            <tfoot>
                <tr class="bg-gray-50 font-semibold">
                    <td class="py-3 px-3 text-gray-900 border border-gray-200">General Average</td>
                    <td colspan="{{ count($gradingPeriods) }}" class="py-3 px-2 text-center text-gray-900 border border-gray-200"></td>
                    <td class="py-3 px-2 text-center text-gray-900 border border-gray-200">{{ $overallAverage ? number_format($overallAverage, 2) : '—' }}</td>
                    <td class="py-3 px-2 text-center border border-gray-200">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ ($overallAverage ?? 0) >= 75 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ ($overallAverage ?? 0) >= 75 ? 'Passed' : 'Failed' }}
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
