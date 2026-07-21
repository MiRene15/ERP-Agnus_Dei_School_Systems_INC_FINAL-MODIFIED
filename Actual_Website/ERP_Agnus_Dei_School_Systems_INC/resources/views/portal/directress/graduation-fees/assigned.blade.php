@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('directress.graduation-fees') }}" class="no-underline" style="color: var(--muted);">Graduation Fees</a>
    <span class="opacity-40">/</span>
    <span class="current">Assigned Students</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Assigned Students</h2>
        <p class="text-gray-600 mt-1">{{ $graduationFee->grade_level }} ({{ $graduationFee->school_year }})</p>
    </div>
    <a href="{{ route('directress.graduation-fees.assign', $graduationFee) }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Assign More</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student Name</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Amount</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 text-gray-900">{{ $assignment->student->first_name }} {{ $assignment->student->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $assignment->enrollment->section->section_name ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-700">₱ {{ number_format($assignment->amount, 2) }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $assignment->paid ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $assignment->paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </td>
                    <td class="py-2 px-2">
                        <form method="POST" action="{{ route('directress.graduation-fees.toggle-paid', $assignment) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-1 text-xs font-medium {{ $assignment->paid ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}">
                                {{ $assignment->paid ? 'Mark Unpaid' : 'Mark Paid' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500 text-sm">No students assigned yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
