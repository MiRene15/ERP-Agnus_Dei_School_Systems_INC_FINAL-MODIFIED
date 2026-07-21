@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Fee Schedule</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Fee Schedule</h2>
        <p class="text-gray-600 mt-1">Manage tuition and miscellaneous fees per grade level, term, and school year.</p>
    </div>
    <a href="{{ route('directress.fees.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Fee</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <select name="school_year" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All School Years</option>
            @foreach($schoolYears as $sy)
                <option value="{{ $sy }}" {{ request('school_year') === $sy ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        @if(request()->filled('school_year'))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

@forelse($fees as $gradeLevel => $gradeFees)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500">({{ $gradeFees->first()->school_year }})</span></h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Term</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Tuition Fee</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Misc Fee</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Total</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($terms as $term)
                @php $fee = $gradeFees->firstWhere('term', $term); @endphp
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2 font-medium text-gray-900">{{ $term }}</td>
                    @if($fee)
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($fee->tuition_fee, 2) }}</td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($fee->misc_fee, 2) }}</td>
                    <td class="py-3 px-2 font-medium text-gray-900">₱ {{ number_format($fee->tuition_fee + $fee->misc_fee, 2) }}</td>
                    <td class="py-3 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('directress.fees.edit', $fee) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('directress.fees.destroy', $fee) }}" onsubmit="return confirm('Delete this fee schedule?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                    @else
                    <td class="py-3 px-2 text-gray-400" colspan="4">Not set</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-200 font-semibold">
                    <td class="py-3 px-2 text-gray-800">Annual Total</td>
                    <td class="py-3 px-2 text-gray-800">₱ {{ number_format($gradeFees->sum('tuition_fee'), 2) }}</td>
                    <td class="py-3 px-2 text-gray-800">₱ {{ number_format($gradeFees->sum('misc_fee'), 2) }}</td>
                    <td class="py-3 px-2 text-gray-900">₱ {{ number_format($gradeFees->sum('tuition_fee') + $gradeFees->sum('misc_fee'), 2) }}</td>
                    <td class="py-3 px-2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <p class="text-sm text-gray-500 text-center py-4">No fee schedules created yet. <a href="{{ route('directress.fees.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add one</a>.</p>
</div>
@endforelse
@endsection
