@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('registrar.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><a href="{{ route('registrar.sections.index') }}" class="no-underline" style="color: var(--muted);">Sections</a><span class="opacity-40"> / </span><span class="current">Edit</span>
@endsection

@section('content')
<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">Edit Section: {{ $section->grade_level }} - {{ $section->section_name }}</h2></div>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <form method="POST" action="{{ route('registrar.sections.update', $section) }}">
        @csrf @method('PATCH')
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
            <select name="grade_level" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach($gradeLevels as $gl)
                <option value="{{ $gl }}" {{ $section->grade_level === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Section Name *</label>
            <input type="text" name="section_name" value="{{ old('section_name', $section->section_name) }}" required maxlength="50" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div class="mb-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" {{ $section->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm text-gray-700">Active</span>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Class Adviser</label>
            <select name="adviser_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">— None —</option>
                @foreach($teachers as $t)
                <option value="{{ $t->id }}" {{ $section->adviser_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Update</button>
    </form>
</div>
@endsection
