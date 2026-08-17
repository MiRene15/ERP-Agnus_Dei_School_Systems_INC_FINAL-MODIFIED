@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Pending Onboarding</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Pending Onboarding</h2>
    <p class="text-gray-600 mt-1">Confirm student accounts after payment clearance to activate portal access.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ selected: [], allIds: @js($pendingConfirmations->pluck('id')->toArray()) }">
    @if($pendingConfirmations->isEmpty())
        <p class="text-sm text-gray-500 text-center py-4">No pending onboarding. All students have been confirmed.</p>
    @else
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       :checked="selected.length === allIds.length && allIds.length > 0"
                       @click="selected.length === allIds.length ? selected = [] : selected = [...allIds]">
                <span class="text-sm text-gray-600">Select All</span>
            </label>
            <span class="text-xs text-gray-400" x-text="selected.length + ' / ' + allIds.length + ' selected'"></span>
        </div>
        <form method="POST" action="{{ route('admin.confirm-batch') }}" x-show="selected.length > 0">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ledger_ids[]" :value="id">
            </template>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Confirm Selected (<span x-text="selected.length"></span>)
            </button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600 w-10"></th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student No.</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Grade / Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Payment Plan</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Total Paid</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingConfirmations as $ledger)
                <tr class="border-b border-gray-100" :class="selected.includes({{ $ledger->id }}) ? 'bg-blue-50' : ''">
                    <td class="py-3 px-2">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               :checked="selected.includes({{ $ledger->id }})"
                               @click="selected.includes({{ $ledger->id }}) ? selected = selected.filter(x => x !== {{ $ledger->id }}) : selected.push({{ $ledger->id }})">
                    </td>
                    <td class="py-3 px-2">
                        <span class="font-medium text-gray-900">{{ $ledger->student->first_name }} {{ $ledger->student->last_name }}</span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">{{ $ledger->student->student_number }}</td>
                    <td class="py-3 px-2 text-gray-700">
                        {{ $ledger->student->enrollments->first()?->section?->grade_level ?? 'N/A' }} -
                        {{ $ledger->student->enrollments->first()?->section?->section_name ?? '' }}
                    </td>
                    <td class="py-3 px-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $ledger->payment_plan === 'full' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($ledger->payment_plan) }}
                        </span>
                    </td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($ledger->total_paid, 2) }}</td>
                    <td class="py-3 px-2">
                        <form method="POST" action="{{ route('admin.confirm-account', $ledger) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium text-white transition cursor-pointer"
                                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                Confirm
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
