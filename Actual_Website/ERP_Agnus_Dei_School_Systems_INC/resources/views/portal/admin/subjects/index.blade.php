@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Subjects</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Subjects</h2>
        <p class="text-gray-600 mt-1">View and manage subjects by grade level.</p>
    </div>
    <a href="{{ route('admin.subjects.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Subject</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name or code..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="grade_level" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('grade_level') === 'All' || !request('grade_level') ? 'selected' : '' }}>All Grade Levels</option>
            @foreach($gradeLevels as $gl)
                <option value="{{ $gl }}" {{ request('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'grade_level']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

@php
    $categoryColors = [
        'Core'           => 'bg-blue-100 text-blue-800',
        'Contextualized'  => 'bg-green-100 text-green-800',
        'Specialized'     => 'bg-purple-100 text-purple-800',
        'TVL'             => 'bg-orange-100 text-orange-800',
    ];
@endphp

@foreach($gradeLevels as $gl)
    @php $glSubjects = $subjects->get($gl, collect()); @endphp
    @if($glSubjects->isEmpty())
        @continue
    @endif
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4" x-data="{ open: true }">
        <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 rounded-t-xl transition cursor-pointer">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">{{ $gl }}</h3>
                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">{{ $glSubjects->count() }} {{ Str::plural('subject', $glSubjects->count()) }}</span>
            </div>
            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse x-cloak>
            <div class="px-4 pb-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Code</th>
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Name</th>
                                <th class="text-left py-2 px-2 font-medium text-gray-500 text-xs uppercase">Category</th>
                                <th class="text-right py-2 px-2 font-medium text-gray-500 text-xs uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($glSubjects as $subject)
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                                <td class="py-2.5 px-2 font-mono text-xs text-gray-700">{{ $subject->subject_code }}</td>
                                <td class="py-2.5 px-2 font-medium text-gray-900">{{ $subject->name }}</td>
                                <td class="py-2.5 px-2">
                                    @if($subject->category)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$subject->category] ?? 'bg-gray-100 text-gray-700' }}">{{ $subject->category }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                                        <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('Delete {{ $subject->subject_code }}?')" class="inline">@csrf @method('DELETE')<button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button></form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

@empty($subjects)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
        <p class="text-sm text-gray-500 py-4">No subjects yet. <a href="{{ route('admin.subjects.create') }}" class="text-blue-600 font-medium">Add one</a>.</p>
    </div>
@endempty
@endsection
