@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Teachers</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Teacher Management</h2>
        <p class="text-gray-600 mt-1">Manage teacher profiles and accounts.</p>
    </div>
    <a href="{{ route('directress.teachers.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Teacher</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by name or email..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            <option value="">All Status</option>
            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
        @if(request()->anyFilled(['search', 'status']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Name</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Teacher #</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Email</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Department</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $teacher)
                <tr class="border-b border-gray-100">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $teacher->first_name }} {{ $teacher->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600 text-xs">{{ $teacher->teacher_number ?? '—' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $teacher->email }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $teacher->department ?? '—' }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $teacher->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $teacher->status }}
                        </span>
                    </td>
                    <td class="py-2 px-2">
                        <div class="flex gap-1">
                            <a href="{{ route('directress.teachers.edit', $teacher) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                            <form method="POST" action="{{ route('directress.teachers.reset-password', $teacher) }}" onsubmit="return confirm('Reset password for {{ $teacher->first_name }}?')" class="inline">
                                @csrf
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-orange-600 hover:text-orange-800">Reset Password</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-gray-500 text-sm">No teachers found. <a href="{{ route('directress.teachers.create') }}" class="text-blue-600 hover:underline">Add one</a>.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">
    {{ $teachers->links() }}
</div>
@endsection
