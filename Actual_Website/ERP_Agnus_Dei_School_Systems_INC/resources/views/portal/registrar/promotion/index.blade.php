@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">End-of-Year Promotion</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">End-of-Year Promotion — Prepare Proposals</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">You propose an action per student. The Principal approves, then the Directress signs off and the system moves the students. Nothing executes from this page.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if($enrollments->isEmpty())
<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 text-center">
    <p class="text-sm text-gray-500 dark:text-[#8A90B0] py-4">No active enrollments found.</p>
</div>
@else
<form method="POST" action="{{ route('registrar.promotion.propose') }}">
    @csrf
    <div class="mb-5 flex items-center gap-3 flex-wrap bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-4">
        <label class="text-sm font-medium text-gray-700 dark:text-[#C1C4DC] whitespace-nowrap">New School Year:</label>
        <select name="school_year" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Select school year</option>
            @foreach($schoolYears as $sy)
            <option value="{{ $sy }}">{{ $sy }}</option>
            @endforeach
            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
        </select>
        <button type="submit" onclick="return confirm('Send the selected proposals to the Principal for approval?')" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Send Proposals to Principal</button>
        <button type="submit" formaction="{{ route('registrar.promotion.batch-qualified') }}" onclick="return confirm('Propose promotion for all qualified students? Unqualified students and open proposals will be skipped.')" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Batch Qualified — Level Up Only</button>
    </div>

    @foreach($enrollments as $gradeLevel => $gradeEnrollments)
    <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-5">
        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-1">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500 dark:text-[#8A90B0]">({{ $gradeEnrollments->count() }} student(s))</span></h3>
        <p class="text-xs text-gray-400 dark:text-[#8A90B0] mb-4">GWA ≥ {{ $passingGrade ?? 75 }} and no failing subject (&lt;{{ $passingGrade ?? 75 }}) = qualified. Leave the action blank to skip a student.</p>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="border-y border-gray-200 dark:border-[#2A2F58] bg-gray-50 dark:bg-[#161A33]">
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Student</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Section</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">GWA</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Qualification</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC]">Proposal Status</th>
                        <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-[#C1C4DC] w-56">Proposed Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeEnrollments as $enrollment)
                    @php
                        $isGrade12 = $gradeLevel === 'Grade 12';
                        $passing = $passingGrade ?? 75;
                        $grades = $enrollment->grades ?? collect();
                        $grouped = $grades->groupBy('class_id');
                        $finals = $grouped->map(fn($g) => round($g->avg('final_grade'), 2));
                        $avg = $finals->isNotEmpty() ? round($finals->avg(), 2) : null;
                        $failCount = $finals->filter(fn($f) => $f < $passing)->count();
                        $qualified = $avg !== null && $avg >= $passing && $failCount === 0;
                        $open = $openProposals[$enrollment->id] ?? null;
                    @endphp
                    <tr class="border-b border-gray-100 dark:border-[#2A2F58] hover:bg-gray-50 dark:hover:bg-[#1E2447]">
                        <td class="py-3 px-4">
                            <span class="font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="block text-xs text-gray-400 dark:text-[#8A90B0]">{{ $enrollment->student->student_number }}</span>
                        </td>
                        <td class="py-3 px-4 text-gray-700 dark:text-[#C1C4DC]">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-4">
                            @if($avg === null)
                                <span class="text-gray-400 text-xs">No grades</span>
                            @else
                                <span class="font-semibold {{ $avg >= $passing ? 'text-gray-900 dark:text-[#E8EAF6]' : 'text-red-600' }}">{{ number_format($avg, 2) }}</span>
                                @if($failCount > 0)<span class="block text-xs text-red-500">{{ $failCount }} failing</span>@endif
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($avg === null)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-[#23274C] dark:text-[#C1C4DC]">No grades</span>
                            @elseif($qualified)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">Qualified</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Not qualified</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($open)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst(str_replace('_', ' ', $open->status)) }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                            @if(!empty($holdMap[$enrollment->student_id] ?? []))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 mt-1" title="@foreach($holdMap[$enrollment->student_id] as $h)[{{ ucfirst($h['source']) }}] {{ $h['reason'] }}&#10;@endforeach">HOLD ({{ count($holdMap[$enrollment->student_id]) }})</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($open)
                                <span class="text-xs text-gray-400">Locked by open proposal</span>
                            @else
                                <div class="flex flex-col gap-1.5" x-data="{ act: '' }">
                                <select name="actions[{{ $enrollment->id }}]" @change="act = $event.target.value" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">Skip</option>
                                    @if(!$isGrade12)
                                    <option value="promote" @if($qualified) selected @endif>Promote</option>
                                    <option value="retain" @if(!$qualified && $avg !== null) selected @endif>Retain</option>
                                    @endif
                                    <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                    <option value="transfer">Transfer Out</option>
                                    <option value="dropped">Dropped Out</option>
                                </select>
                                <input type="text" name="reasons[{{ $enrollment->id }}]" placeholder="Reason (required for Transfer/Dropped)" x-show="act === 'transfer' || act === 'dropped'" x-cloak class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500 outline-none" maxlength="500">
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</form>
@endif
@endsection
