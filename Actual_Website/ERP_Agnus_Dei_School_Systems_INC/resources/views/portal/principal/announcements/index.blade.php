@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Announcements</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Announcements & Events</h2>
        <p class="text-gray-600 mt-1">Create and manage school announcements and events.</p>
    </div>
    <a href="{{ route('principal.announcements.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ New Announcement</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by title..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('type') === 'All' || !request('type') ? 'selected' : '' }}>All Types</option>
            <option value="announcement" {{ request('type') === 'announcement' ? 'selected' : '' }}>Announcement</option>
            <option value="event" {{ request('type') === 'event' ? 'selected' : '' }}>Event</option>
        </select>
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="All" {{ request('status') === 'All' || !request('status') ? 'selected' : '' }}>All Status</option>
            <option value="Published" {{ request('status') === 'Published' ? 'selected' : '' }}>Published</option>
            <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        @if(request()->anyFilled(['search', 'type', 'status']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Title</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Type</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $a)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $a->title }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->type === 'event' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($a->type) }}
                        </span>
                    </td>
                    <td class="py-2 px-2 text-gray-600">{{ \Carbon\Carbon::parse($a->date)->format('M d, Y') }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $a->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </td>
                    <td class="py-2 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('principal.announcements.edit', $a) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('principal.announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-500 text-sm">No announcements yet. <a href="{{ route('principal.announcements.create') }}" class="text-blue-600 hover:underline">Create one</a>.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $announcements->links() }}
    </div>
</div>
@endsection
