@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Certificate of Registration</span>
@endsection

@section('content')
<div class="mb-4 flex items-center justify-between no-print">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Certificate of Registration</h2>
        <p class="text-gray-600 mt-1">{{ $enrollment->school_year }}</p>
    </div>
    <button onclick="window.print()" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Print COR</button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 print:p-4" id="cor-content">
    <div class="text-center border-b-2 border-gray-800 pb-4 mb-6">
        <h1 class="text-xl font-bold uppercase tracking-wide">Agnus Dei School Systems Inc.</h1>
        <p class="text-sm text-gray-600 mt-1">Certificate of Registration</p>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
        <div>
            <p><span class="font-semibold">Student Name:</span> {{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</p>
            <p><span class="font-semibold">Student No.:</span> {{ $student->student_number }}</p>
            <p><span class="font-semibold">LRN:</span> {{ $student->legacy_lrn ?? 'N/A' }}</p>
        </div>
        <div>
            <p><span class="font-semibold">Grade Level:</span> {{ $enrollment->section->grade_level }}</p>
            <p><span class="font-semibold">Section:</span> {{ $enrollment->section->section_name }}</p>
            <p><span class="font-semibold">School Year:</span> {{ $enrollment->school_year }}</p>
            @if($enrollment->strand)
            <p><span class="font-semibold">Strand:</span> {{ $enrollment->strand }}</p>
            @endif
        </div>
    </div>

    <h3 class="text-sm font-semibold uppercase border-b border-gray-300 pb-1 mb-3">Enrolled Subjects</h3>
    <table class="w-full text-sm border-collapse mb-6">
        <thead>
            <tr class="bg-gray-50">
                <th class="text-left px-3 py-2 border border-gray-300 font-semibold">Subject</th>
                <th class="text-left px-3 py-2 border border-gray-300 font-semibold">Schedule</th>
                <th class="text-left px-3 py-2 border border-gray-300 font-semibold">Room</th>
                <th class="text-left px-3 py-2 border border-gray-300 font-semibold">Teacher</th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollment->subjects as $cls)
            <tr class="border-b border-gray-200">
                <td class="px-3 py-2 border border-gray-300">{{ $cls->subject->subject_code ?? $cls->subject->name }} - {{ $cls->subject->name }}</td>
                <td class="px-3 py-2 border border-gray-300">
                    @foreach($cls->schedules as $sched)
                        {{ $sched->day_of_week }} {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }}-{{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}@if(!$loop->last), @endif
                    @endforeach
                </td>
                <td class="px-3 py-2 border border-gray-300">{{ $cls->room ?? ($cls->schedules->first()?->room ?? '—') }}</td>
                <td class="px-3 py-2 border border-gray-300">{{ $cls->teacher->name ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="px-3 py-4 text-center text-gray-500 border border-gray-300">No subjects assigned yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($feeSchedules->isNotEmpty())
    @php
        $isSHS = in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);
    @endphp
    <h3 class="text-sm font-semibold uppercase border-b border-gray-300 pb-1 mb-3">Fee Assessment</h3>
    <table class="w-full text-sm border-collapse mb-4">
        <thead>
            <tr class="bg-gray-50">
                <th class="text-left px-3 py-2 border border-gray-300 font-semibold">{{ $isSHS ? 'Term' : 'School Year' }}</th>
                <th class="text-right px-3 py-2 border border-gray-300 font-semibold">Tuition</th>
                <th class="text-right px-3 py-2 border border-gray-300 font-semibold">Misc</th>
                <th class="text-right px-3 py-2 border border-gray-300 font-semibold">Total</th>
            </tr>
        </thead>
        <tbody>
            @if($isSHS)
                @foreach($feeSchedules as $fs)
                <tr>
                    <td class="px-3 py-2 border border-gray-300">{{ $fs->term }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($fs->tuition_fee, 2) }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($fs->misc_fee, 2) }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300 font-medium">₱{{ number_format($fs->tuition_fee + $fs->misc_fee, 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="px-3 py-2 border border-gray-300">{{ $enrollment->school_year }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($feeSchedules->sum('tuition_fee'), 2) }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($feeSchedules->sum('misc_fee'), 2) }}</td>
                    <td class="px-3 py-2 text-right border border-gray-300 font-medium">₱{{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee'), 2) }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 font-semibold">
                <td class="px-3 py-2 border border-gray-300">Total Assessed</td>
                <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($feeSchedules->sum('tuition_fee'), 2) }}</td>
                <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($feeSchedules->sum('misc_fee'), 2) }}</td>
                <td class="px-3 py-2 text-right border border-gray-300">₱{{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee'), 2) }}</td>
            </tr>
            @if($ledger && $ledger->discount_applied > 0)
            <tr>
                <td colspan="3" class="px-3 py-2 text-right border border-gray-300 text-gray-600">Discount ({{ ucfirst($ledger->discount_type ?? 'N/A') }})</td>
                <td class="px-3 py-2 text-right border border-gray-300 text-green-600">-₱{{ number_format($ledger->discount_applied, 2) }}</td>
            </tr>
            @endif
            @if($ledger)
            <tr>
                <td colspan="3" class="px-3 py-2 text-right border border-gray-300 text-gray-600">Total Paid</td>
                <td class="px-3 py-2 text-right border border-gray-300 text-green-600">₱{{ number_format($ledger->total_paid, 2) }}</td>
            </tr>
            <tr class="bg-gray-50 font-semibold">
                <td colspan="3" class="px-3 py-2 text-right border border-gray-300">Balance</td>
                <td class="px-3 py-2 text-right border border-gray-300 {{ $ledger->balance > 0 ? 'text-red-600' : 'text-green-600' }}">₱{{ number_format($ledger->balance, 2) }}</td>
            </tr>
            @endif
        </tfoot>
    </table>
    @endif

    <div class="mt-10 grid grid-cols-2 gap-8 text-sm text-center">
        @if($principalName)
        <div>
            <div class="border-t border-gray-800 w-48 mx-auto mb-1"></div>
            <p class="font-semibold">{{ $principalName }}</p>
            <p class="text-xs text-gray-500">Noted by: School Principal</p>
        </div>
        @endif
        @if($directressName)
        <div>
            <div class="border-t border-gray-800 w-48 mx-auto mb-1"></div>
            <p class="font-semibold">{{ $directressName }}</p>
            <p class="text-xs text-gray-500">Approved by: School Directress</p>
        </div>
        @endif
    </div>

    <div class="mt-6 pt-4 border-t border-gray-300 text-center text-xs text-gray-500">
        <p>This document is system-generated and valid upon presentation.</p>
        <p>Issued: {{ now()->format('F d, Y') }}</p>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white; }
    #cor-content { box-shadow: none; border: none; border-radius: 0; }
}
</style>
@endsection
