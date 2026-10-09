<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Total Sections</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalSections }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Active Students</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalStudents }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-sm text-gray-500">Announcements</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalAnnouncements }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('principal.approvals', ['view' => 'grade-edit']) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition block">
        <p class="text-sm text-gray-500">Grade Unlock Reviews</p>
        <p class="text-2xl font-bold {{ ($pendingUnlocks ?? 0) > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">{{ $pendingUnlocks ?? 0 }}</p>
        <p class="text-sm text-gray-500 mt-1">Teacher correction requests</p>
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-4">Recent Announcements</h3>
    @if($recentAnnouncements->isEmpty())
    <p class="text-sm text-gray-500 text-center py-4">No announcements yet.</p>
    @else
    <div class="space-y-2">
        @foreach($recentAnnouncements as $a)
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
                <p class="font-medium text-sm text-gray-900">{{ $a->title }}</p>
                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($a->date)->format('M d, Y') }} — {{ ucfirst($a->type) }}</p>
            </div>
            <span class="text-xs {{ $a->is_published ? 'text-green-600' : 'text-gray-400' }}">{{ $a->is_published ? 'Published' : 'Draft' }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>
