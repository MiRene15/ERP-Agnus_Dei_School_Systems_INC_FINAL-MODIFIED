@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><span class="current">Sections</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Sections</h2>
        <p class="text-gray-600 mt-1">Manage sections/classrooms per grade level.</p>
    </div>
    <a href="{{ route('admin.sections.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">+ Add Section</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by section name..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="grade_level" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('grade_level') === 'All' || !request('grade_level') ? 'selected' : '' }}>All Grade Levels</option>
            @foreach($gradeLevels as $gl)
                @if($gl !== 'All')
                <option value="{{ $gl }}" {{ request('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endif
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'grade_level']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

@forelse($gradeLevels as $gl)
    @php $glSections = $sections->get($gl, collect()); @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $gl }} <span class="text-sm font-normal text-gray-500">({{ $glSections->count() }})</span></h3>
        @if($glSections->isEmpty())
            <p class="text-sm text-gray-400">No sections for this grade level.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr><th class="text-left py-3 px-2 font-medium text-gray-600">Section</th><th class="text-left py-3 px-2 font-medium text-gray-600">Adviser</th><th class="text-left py-3 px-2 font-medium text-gray-600">Status</th><th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th></tr></thead>
                <tbody>
                    @foreach($glSections as $section)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-2 font-medium text-gray-900">{{ $section->section_name }}</td>
                        <td class="py-3 px-2 text-gray-600 text-xs">{{ $section->adviser?->name ?? '—' }}</td>
                        <td class="py-3 px-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $section->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <div class="flex gap-1">
                                <a href="{{ route('admin.sections.edit', $section) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                                <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" onsubmit="return confirm('Delete {{ $section->section_name }}?')" class="inline">@csrf @method('DELETE')<button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
@empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center"><p class="text-sm text-gray-500 py-4">No sections yet.</p></div>
@endforelse
@endsection
