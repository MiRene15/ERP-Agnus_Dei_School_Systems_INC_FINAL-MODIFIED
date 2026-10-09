@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('teacher.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Grade Edit Request</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Grade Edit Request</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Submitted grades are locked. Request a reopen → Principal/Registrar approves → you correct → you re-submit. Unsubmitted grades can still be edited directly.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Request Reopen</h3>
    <form method="POST" action="{{ route('teacher.grade-unlocks.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Class *</label>
            <select name="class_id" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select class…</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->subject->name ?? $class->subject_code }} — {{ $class->grade_level }} {{ $class->section }}
                        @if(($submittedPeriods[$class->id] ?? collect())->isNotEmpty())
                            (submitted: {{ $submittedPeriods[$class->id]->implode(', ') }})
                        @else
                            (nothing submitted)
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">Grading Period *</label>
            <select name="grading_period" required class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="1st Term">1st Term</option>
                <option value="2nd Term">2nd Term</option>
                <option value="3rd Term">3rd Term</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-[#C1C4DC] mb-1">What went wrong? *</label>
            <textarea name="reason" required minlength="10" maxlength="1000" rows="2" placeholder="e.g. Quiz 3 scores for 5 students were encoded under the wrong names" class="w-full rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">{{ old('reason') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Send Request</button>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">My Requests</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Term</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Reason</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                    <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }} <span class="text-xs text-gray-500 font-normal">{{ $req->schoolClass->grade_level ?? '' }} {{ $req->schoolClass->section ?? '' }}</span></td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $req->grading_period }}</td>
                    <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC] text-xs max-w-xs">{{ $req->reason }}</td>
                    <td class="py-2 px-2">
                        @php $badge = ['pending' => 'bg-amber-100 text-amber-700', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700', 'cancelled' => 'bg-gray-100 text-gray-600'][$req->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->status) }}</span>
                        @if($req->status === 'approved')
                            <span class="block text-xs text-gray-500 mt-1">Reopened — correct then re-submit.</span>
                        @elseif($req->status === 'cancelled')
                            <span class="block text-xs text-gray-500 mt-1">Grades were re-submitted — no longer needed.</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500 text-sm">No requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection
