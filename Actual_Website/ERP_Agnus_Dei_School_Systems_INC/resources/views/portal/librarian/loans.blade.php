@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Book Loans</span>
@endsection

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Book Loans</h2>
        <p class="text-gray-600 mt-1">Manage book borrowing and returns.</p>
    </div>
    <a href="{{ route('librarian.loans.borrow') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ New Loan</a>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

<div class="mb-4 flex gap-2 flex-wrap items-center">
    <form method="GET" class="flex gap-2 flex-1 flex-wrap">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search by student name or book title..."
               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }} class="rounded border-gray-300 text-red-600 focus:ring-red-500">
            Overdue only
        </label>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
        @if(request()->anyFilled(['search', 'overdue']))
            <a href="{{ url()->current() }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Book Title</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Borrowed</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Return Due</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $txn)
                @php $overdue = $txn->status === 'Borrowed' && \Carbon\Carbon::parse($txn->return_date)->isPast(); @endphp
                <tr class="border-b border-gray-100 {{ $overdue ? 'bg-red-50/50' : '' }}">
                    <td class="py-2 px-2 text-gray-900">{{ $txn->student->first_name ?? '' }} {{ $txn->student->last_name ?? '' }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $txn->book_title }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ \Carbon\Carbon::parse($txn->borrow_date)->format('M d, Y') }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ \Carbon\Carbon::parse($txn->return_date)->format('M d, Y') }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $overdue ? 'bg-red-100 text-red-700' : ($txn->status === 'Returned' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ $overdue ? 'Overdue' : $txn->status }}
                        </span>
                    </td>
                    <td class="py-2 px-2">
                        @if($txn->status === 'Borrowed')
                        <form method="POST" action="{{ route('librarian.loans.return', $txn) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800">Mark Returned</button>
                        </form>
                        @else
                        <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-gray-500 text-sm">No loans found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
