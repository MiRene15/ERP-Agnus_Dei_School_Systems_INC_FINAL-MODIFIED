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
<div class="p-4">{{ $teachers->links() }}</div>
