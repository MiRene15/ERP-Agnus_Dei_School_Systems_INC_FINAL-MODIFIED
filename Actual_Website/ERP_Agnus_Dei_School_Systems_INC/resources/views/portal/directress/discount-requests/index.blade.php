@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Discount Approvals</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Discount Approvals</h2>
    <p class="text-gray-600 mt-1">You approve or reject. Approving posts the discount by itself — you never touch paid marking.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Pending ({{ $pending->count() }})</h3>
    @if($pending->isEmpty())
        <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Type / Amount</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Proof</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Requested by</th>
                    <th class="text-center py-2 px-2 font-medium text-gray-600">Decision</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $req)
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ \App\Models\DiscountRequest::TYPES[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                    <td class="py-2 px-2 text-gray-600 text-xs max-w-xs">{{ $req->proof_details }}</td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}<br>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                    <td class="py-2 px-2 text-center whitespace-nowrap">
                        <form method="POST" action="{{ route('directress.discount-requests.approve', $req) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('directress.discount-requests.reject', $req) }}" onsubmit="return confirm('Reject this discount request?')" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg">Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-4">Recent Decisions</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Type / Amount</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Status</th>
                    <th class="text-left py-2 px-2 font-medium text-gray-600">Reviewed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $req)
                <tr class="border-b border-gray-50">
                    <td class="py-2 px-2 font-medium text-gray-900">{{ $req->ledger->student->first_name }} {{ $req->ledger->student->last_name }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ \App\Models\DiscountRequest::TYPES[$req->discount_type] ?? $req->discount_type }} — ₱{{ number_format($req->discount_amount, 2) }}</td>
                    <td class="py-2 px-2">
                        @php $badge = ['approved' => 'bg-blue-100 text-blue-700', 'rejected' => 'bg-red-100 text-red-700', 'applied' => 'bg-green-100 text-green-700'][$req->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->status) }}</span>
                    </td>
                    <td class="py-2 px-2 text-xs text-gray-500">{{ $req->reviewer?->name ?? '—' }} · {{ $req->reviewed_at?->format('M d, Y') ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="py-6 text-center text-gray-500 text-sm">No decisions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
