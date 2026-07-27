@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('principal.announcements') }}" class="no-underline" style="color: var(--muted);">Announcements</a>
    <span class="opacity-40">/</span>
    <span class="current">Edit</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Edit Announcement</h2>
    <p class="text-gray-600 mt-1">{{ $announcement->title }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('principal.announcements.update', $announcement) }}">
        @csrf @method('PATCH')
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                <textarea name="content" rows="5" required
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('content', $announcement->content) }}</textarea>
                @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="announcement" {{ old('type', $announcement->type) === 'announcement' ? 'selected' : '' }}>Announcement</option>
                        <option value="event" {{ old('type', $announcement->type) === 'event' ? 'selected' : '' }}>Event</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input type="date" name="date" value="{{ old('date', $announcement->date) }}" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $announcement->is_published) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label class="text-sm text-gray-700">Published</label>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Update Announcement</button>
                <a href="{{ route('principal.announcements') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
