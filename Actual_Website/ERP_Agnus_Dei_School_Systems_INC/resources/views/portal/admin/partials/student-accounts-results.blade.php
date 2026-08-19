<table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
            <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Name</th>
            <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Email</th>
            <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Student No.</th>
            <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Status</th>
            <th class="text-left px-6 py-3 font-semibold text-gray-600 uppercase tracking-wide text-xs">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse ($users as $user)
        <tr class="hover:bg-gray-50/50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm border border-purple-200 shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <span class="font-medium text-gray-800">{{ $user->name }}</span>
                </div>
            </td>
            <td class="px-6 py-4 text-gray-500">{{ $user->email }}</td>
            <td class="px-6 py-4 text-gray-500">{{ $user->student?->student_number ?? 'N/A' }}</td>
            <td class="px-6 py-4">
                @if ($user->status === 'active')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span> Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span> Inactive
                    </span>
                @endif
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.student-accounts.toggle-status', $user) }}" onsubmit="return confirm('Toggle account status for {{ $user->name }}?')">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $user->status === 'active' ? 'text-red-700 bg-red-50 hover:bg-red-100' : 'text-green-700 bg-green-50 hover:bg-green-100' }}">
                            {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.student-accounts.reset-password', $user) }}" onsubmit="return confirm('Reset password for {{ $user->name }}? The new password will be shown once.')">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-orange-700 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">Reset PW</button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                No student accounts found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
<div class="mt-4">{{ $users->links() }}</div>
