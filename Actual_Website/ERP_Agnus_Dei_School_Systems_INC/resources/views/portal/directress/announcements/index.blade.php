@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Announcements</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">School Announcements</h2>
    <p class="text-gray-600 mt-1">Posted by the Principal. Acknowledge each one so nothing school-wide goes out unnoticed. Editing resets an announcement to unacknowledged.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

@if($unseenCount > 0)
<div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm font-medium">{{ $unseenCount }} announcement(s) waiting for your acknowledgement.</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Title</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Type</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Date</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Awareness</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $a)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $a->title }}<span class="block text-xs text-gray-500 font-normal">by {{ $a->admin?->name ?? '—' }}</span></td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->type === 'event' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($a->type) }}</span>
                    </td>
                    <td class="py-2 px-2 text-gray-600">{{ \Carbon\Carbon::parse($a->date)->format('M d, Y') }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $a->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $a->is_published ? 'Published' : 'Draft' }}</span>
                    </td>
                    <td class="py-2 px-2">
                        @if($a->directress_seen_at)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Seen {{ $a->directress_seen_at->format('M d, Y') }}</span>
                        @else
                            <form method="POST" action="{{ route('directress.announcements.acknowledge', $a) }}" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1 text-xs font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Acknowledge</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-6 text-center text-gray-500 text-sm">No announcements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
</div>
@endsection
