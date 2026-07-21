@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('directress.fees') }}" class="no-underline" style="color: var(--muted);">Fee Schedule</a>
    <span class="opacity-40">/</span>
    <span class="current">Edit</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Edit Fee Schedule</h2>
    <p class="text-gray-600 mt-1">{{ $fee->grade_level }} - {{ $fee->term }} ({{ $fee->school_year }})</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <form method="POST" action="{{ route('directress.fees.update', $fee) }}">
        @csrf @method('PATCH')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">School Year *</label>
            <input type="text" name="school_year" value="{{ old('school_year', $fee->school_year) }}" required maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
            <select name="grade_level" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach($gradeLevels as $gl)
                <option value="{{ $gl }}" {{ $fee->grade_level === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Term *</label>
            <select name="term" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach($terms as $term)
                <option value="{{ $term }}" {{ $fee->term === $term ? 'selected' : '' }}>{{ $term }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tuition Fee (₱) *</label>
                <input type="number" name="tuition_fee" value="{{ old('tuition_fee', $fee->tuition_fee) }}" required step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Misc Fee (₱) *</label>
                <input type="number" name="misc_fee" value="{{ old('misc_fee', $fee->misc_fee) }}" required step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>
        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Update Fee Schedule</button>
    </form>
</div>
@endsection
