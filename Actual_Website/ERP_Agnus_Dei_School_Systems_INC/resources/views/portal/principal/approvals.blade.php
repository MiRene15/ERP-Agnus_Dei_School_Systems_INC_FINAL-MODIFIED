@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Approvals</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Approvals</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Everything awaiting the principal's decision — promotions, subject changes, grade edits — one queue.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="{ tab: ((v) => ['promotions','subject-approvals','grade-edit'].includes(v) ? v : 'promotions')(new URLSearchParams(window.location.search).get('view')), setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'promotions') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Approval type">
        <button @click="setTab('promotions')" :class="tab==='promotions' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='promotions' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='promotions'">Promotions @if($badgeCounts['promotions'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-green-600 text-white">{{ $badgeCounts['promotions'] }}</span>@endif</button>
        <button @click="setTab('subject-approvals')" :class="tab==='subject-approvals' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='subject-approvals' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='subject-approvals'">Subject Approvals @if($badgeCounts['subject-approvals'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $badgeCounts['subject-approvals'] }}</span>@endif</button>
        <button @click="setTab('grade-edit')" :class="tab==='grade-edit' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='grade-edit' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='grade-edit'">Grade Edit @if($badgeCounts['grade-edit'] > 0)<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500 text-white">{{ $badgeCounts['grade-edit'] }}</span>@endif</button>
    </div>

    <div x-show="tab==='promotions'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Promotion Approvals</h2>
            <p class="text-gray-600 mt-1">Proposals prepared by the Registrar. Approve to send to the Directress for sign-off, or reject back. Nothing executes from this page.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Proposed ({{ $proposals->count() }})</h3>
            @if($proposals->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">No proposals waiting for approval.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Student</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Section / Year</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Proposed</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">GWA</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Proposed by</th>
                            <th class="text-center py-2 px-2 font-medium text-gray-600">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proposals as $proposal)
                        @php
                            $q = \App\Services\PromotionService::qualify($proposal->enrollment, $passingGrade ?? 75);
                            $name = $proposal->enrollment->student->first_name . ' ' . $proposal->enrollment->student->last_name;
                        @endphp
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-2 font-medium text-gray-900">{{ $name }}<span class="block text-xs text-gray-400 font-normal">{{ $proposal->enrollment->student->student_number }}</span></td>
                            <td class="py-2 px-2 text-gray-600">{{ $proposal->enrollment->section->grade_level ?? '?' }} {{ $proposal->enrollment->section->section_name ?? '' }}<span class="block text-xs text-gray-400">→ {{ $proposal->school_year }}</span></td>
                            <td class="py-2 px-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ \App\Models\PromotionProposal::ACTIONS[$proposal->action] ?? $proposal->action }}</span>
                                @if($proposal->reason)<span class="block text-xs text-gray-500 mt-1">{{ $proposal->reason }}</span>@endif
                            </td>
                            <td class="py-2 px-2">
                                @if($q['avg'] === null)
                                    <span class="text-gray-400 text-xs">No grades</span>
                                @else
                                    <span class="font-semibold {{ $q['qualified'] ? 'text-gray-900' : 'text-red-600' }}">{{ number_format($q['avg'], 2) }}</span>
                                    @if($q['failCount'] > 0)<span class="block text-xs text-red-500">{{ $q['failCount'] }} failing</span>@endif
                                @endif
                            </td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $proposal->proposer?->name ?? '—' }}<br>{{ $proposal->created_at->format('M d, Y') }}</td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('principal.promotion.approve', $proposal) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('principal.promotion.reject', $proposal) }}" onsubmit="return confirm('Reject this proposal?')" class="inline">
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
    </div>

    <div x-show="tab==='subject-approvals'" x-cloak>
        @php [$pending, $history] = [$subjectPending, $subjectHistory]; @endphp
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Subject Approvals</h2>
            <p class="text-gray-600 mt-1">Change requests staged by the Registrar. Approving applies them to the live subjects table immediately; rejecting leaves live data untouched.</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4">Pending ({{ $pending->count() }})</h3>
            @if($pending->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Action</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Subject</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Requested by</th>
                            <th class="text-center py-2 px-2 font-medium text-gray-600">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $req)
                        @php
                            $badge = ['create' => 'bg-blue-100 text-blue-700', 'update' => 'bg-amber-100 text-amber-700', 'delete' => 'bg-red-100 text-red-700'][$req->action] ?? 'bg-gray-100 text-gray-600';
                            $p = $req->payload ?? [];
                        @endphp
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-2"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($req->action) }}</span></td>
                            <td class="py-2 px-2">
                                @if($req->action === 'create')
                                    <span class="font-medium text-gray-900">{{ $p['subject_code'] ?? '?' }} — {{ $p['name'] ?? '' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $p['grade_level'] ?? '' }} · {{ $p['category'] ?? '' }}</span>
                                @elseif($req->subject)
                                    <span class="font-medium text-gray-900">{{ $req->subject->subject_code }} — {{ $req->subject->name }}</span>
                                    @if($req->action === 'update')
                                    <span class="block text-xs text-gray-500">→ {{ $p['subject_code'] ?? '' }} — {{ $p['name'] ?? '' }} ({{ $p['grade_level'] ?? '' }} · {{ $p['category'] ?? '' }})</span>
                                    @else
                                    <span class="block text-xs text-red-500">Delete this subject</span>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-xs">Target subject no longer exists</span>
                                @endif
                            </td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}<br>{{ $req->created_at->format('M d, Y') }}</td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('principal.subject-approvals.approve', $req) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Approve & Apply</button>
                                </form>
                                <form method="POST" action="{{ route('principal.subject-approvals.reject', $req) }}" onsubmit="return confirm('Reject this change? Live subjects stay untouched.')" class="inline">
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
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Action</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Subject</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Status</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600">Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $req)
                        <tr class="border-b border-gray-50">
                            <td class="py-2 px-2 text-gray-600">{{ ucfirst($req->action) }}</td>
                            <td class="py-2 px-2 text-gray-900">{{ $req->subject?->subject_code ?? ($req->payload['subject_code'] ?? '—') }}</td>
                            <td class="py-2 px-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($req->status) }}</span>
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
    </div>

    <div x-show="tab==='grade-edit'" x-cloak>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Grade Edit</h2>
            <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Teachers ask to reopen submitted grades for correction. Approving returns those grades to Pending — the teacher corrects and re-submits.</p>
        </div>

        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Pending ({{ $pending->count() }})</h3>
            @if($pending->isEmpty())
                <p class="text-sm text-gray-500 py-4 text-center">No pending requests.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Term</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher / Reason</th>
                            <th class="text-center py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $req)
                        <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                            <td class="py-2 px-2 font-medium text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }}<span class="block text-xs text-gray-500 font-normal">{{ $req->schoolClass->grade_level ?? '' }} {{ $req->schoolClass->section ?? '' }}</span></td>
                            <td class="py-2 px-2 text-gray-600 dark:text-[#C1C4DC]">{{ $req->grading_period }}</td>
                            <td class="py-2 px-2 text-xs text-gray-600 dark:text-[#C1C4DC] max-w-sm">{{ $req->requester?->name ?? '—' }}: {{ $req->reason }}<span class="block text-gray-400 mt-1">{{ $req->created_at->format('M d, Y h:i A') }}</span></td>
                            <td class="py-2 px-2 text-center whitespace-nowrap">
                                <form method="POST" action="{{ route('registrar.grade-unlocks.approve', $req) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg">Unlock</button>
                                </form>
                                <form method="POST" action="{{ route('registrar.grade-unlocks.reject', $req) }}" onsubmit="return confirm('Reject this unlock request? Grades stay submitted.')" class="inline">
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

        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6] mb-4">Recent Decisions</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#2A2F58]">
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Class / Term</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Teacher</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Status</th>
                            <th class="text-left py-2 px-2 font-medium text-gray-600 dark:text-[#8A90B0]">Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $req)
                        <tr class="border-b border-gray-50 dark:border-[#2A2F58]">
                            <td class="py-2 px-2 text-gray-900 dark:text-[#E8EAF6]">{{ $req->schoolClass->subject->name ?? '—' }} <span class="text-xs text-gray-500">· {{ $req->grading_period }}</span></td>
                            <td class="py-2 px-2 text-xs text-gray-500">{{ $req->requester?->name ?? '—' }}</td>
                            <td class="py-2 px-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ ucfirst($req->status) }}</span>
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
    </div>
</div>
@endsection
