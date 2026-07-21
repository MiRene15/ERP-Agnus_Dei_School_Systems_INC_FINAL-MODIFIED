@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Graduation Fees</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Graduation Fees</h2>
        <p class="text-gray-600 mt-1">Manage graduation and miscellaneous fees for graduating students.</p>
    </div>
    <a href="{{ route('directress.graduation-fees.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Graduation Fee</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

@forelse($fees as $gradeLevel => $gradeFees)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
    <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }}</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">School Year</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Graduation Fee</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Other Fees</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Total</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gradeFees as $gf)
                <tr class="border-b border-gray-100">
                    <td class="py-3 px-2 text-gray-700">{{ $gf->school_year }}</td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($gf->graduation_fee, 2) }}</td>
                    <td class="py-3 px-2 text-gray-700">₱ {{ number_format($gf->other_fees, 2) }}</td>
                    <td class="py-3 px-2 font-medium text-gray-900">₱ {{ number_format($gf->graduation_fee + $gf->other_fees, 2) }}</td>
                    <td class="py-3 px-2">
                        <div class="flex gap-1 flex-wrap">
                            <a href="{{ route('directress.graduation-fees.assign', $gf) }}" class="px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800">Assign</a>
                            <a href="{{ route('directress.graduation-fees.assigned', $gf) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">View Assigned</a>
                            <a href="{{ route('directress.graduation-fees.edit', $gf) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('directress.graduation-fees.destroy', $gf) }}" onsubmit="return confirm('Delete this graduation fee?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <p class="text-sm text-gray-500 text-center py-4">No graduation fees created yet. <a href="{{ route('directress.graduation-fees.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add one</a>.</p>
</div>
@endforelse
@endsection
