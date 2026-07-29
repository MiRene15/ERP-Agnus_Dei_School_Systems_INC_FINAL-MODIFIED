@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><a href="{{ route('admin.subjects.index') }}" class="no-underline" style="color: var(--muted);">Subjects</a><span class="opacity-40"> / </span><span class="current">Create</span>
@endsection

@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Create Subject</h2></div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <form method="POST" action="{{ route('admin.subjects.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject Code *</label>
            <input type="text" name="subject_code" value="{{ old('subject_code') }}" required maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. SCI-10">
            @error('subject_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject Name *</label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="e.g. Science 10">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
            <select name="grade_level" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select grade level</option>
                @foreach($gradeLevels as $gl)
                <option value="{{ $gl }}" {{ old('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
            </select>
            @error('grade_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
            <select name="category" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select category</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
            @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Create Subject</button>
    </form>
</div>
@endsection
