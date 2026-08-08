@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('librarian.dashboard') }}" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('librarian.loans') }}" style="color: var(--muted);">Borrowing & Returns</a>
    <span class="opacity-40">/</span>
    <span class="current">Return Book</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Return Book</h2>
    <p class="text-gray-600 mt-1">Record return condition and assess any fees.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Loan Details</h3>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">Book</dt>
                <dd class="font-medium text-gray-900">{{ $transaction->book?->title ?? $transaction->book_title }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Serial No.</dt>
                <dd class="font-medium text-gray-900">{{ $transaction->book?->serial_number ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Student</dt>
                <dd class="font-medium text-gray-900">{{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Borrowed</dt>
                <dd class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($transaction->borrow_date)->format('M d, Y') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">Due Date</dt>
                <dd class="font-medium {{ $transaction->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">{{ \Carbon\Carbon::parse($transaction->return_date)->format('M d, Y') }}</dd>
            </div>
            @if($transaction->isOverdue())
            <div class="flex justify-between">
                <dt class="text-gray-500">Days Overdue</dt>
                <dd class="font-medium text-red-600">{{ $transaction->daysOverdue() }} day(s) × ₱5.00 = ₱{{ number_format($transaction->daysOverdue() * 5, 2) }}</dd>
            </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">Condition at Borrow</dt>
                <dd class="font-medium text-gray-900">{{ $transaction->condition_at_borrow }}</dd>
            </div>
        </dl>
    </div>

    @if($transaction->isOverdue() || true)
    <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-6">
        <h3 class="font-semibold text-orange-800 mb-4">Fee Schedule</h3>
        <dl class="space-y-3">
            @if($transaction->isOverdue())
            <div class="flex justify-between">
                <dt class="text-gray-500">Late Fee ({{ $transaction->daysOverdue() }} day(s) × ₱5)</dt>
                <dd class="font-medium text-orange-600">₱{{ number_format($transaction->daysOverdue() * 5, 2) }}</dd>
            </div>
            @endif
            <div class="border-t border-gray-100 pt-3 mt-3">
                <dt class="text-gray-500 mb-2">Damage Fee Schedule</dt>
                <dd class="text-sm text-gray-600">
                    <div class="flex justify-between mb-1"><span>Good Condition</span><span class="text-green-600">₱0.00</span></div>
                    <div class="flex justify-between mb-1"><span>Minor Damage</span><span>₱50.00</span></div>
                    <div class="flex justify-between mb-1"><span>Major Damage</span><span>₱200.00</span></div>
                    <div class="flex justify-between"><span>Lost / Not Returned</span><span>₱{{ number_format($transaction->book->price ?? 500, 2) }} (book price)</span></div>
                </dd>
            </div>
            @if($transaction->isOverdue())
            <div class="border-t border-gray-100 pt-3 mt-3 flex justify-between">
                <dt class="font-semibold text-gray-900">Maximum Possible Fees</dt>
                <dd class="font-bold text-red-600">₱{{ number_format(($transaction->book->price ?? 500) + ($transaction->daysOverdue() * 5), 2) }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Return Assessment</h3>
        <form method="POST" action="{{ route('librarian.loans.process-return', $transaction) }}">
            @csrf @method('PATCH')

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Condition at Return *</label>
                <select name="condition_at_return" required class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="Good">Good</option>
                    <option value="Minor Damage">Minor Damage (e.g., bent pages, small tears)</option>
                    <option value="Major Damage">Major Damage (e.g., water damage, missing pages)</option>
                    <option value="Lost">Lost / Not Returned</option>
                </select>
                @error('condition_at_return') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-500 mb-1">Damage Notes (optional)</label>
                <textarea name="damage_notes" rows="3" placeholder="Describe any damage..."
                          class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">{{ old('damage_notes') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Process Return</button>
                <a href="{{ route('librarian.loans') }}" class="px-5 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
