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

        <div x-data="ajaxTable('{{ route('principal.grade-unlocks.index', ['ajax' => 1]) }}', { search: '', school_year: '{{ active_school_year() }}' })">
            <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Search Requests</h3>
                </div>
                <div class="flex gap-2 flex-wrap items-center">
                    <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
                        <select name="school_year" x-model="filters.school_year" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            @foreach($schoolYears as $sy)
                                <option value="{{ $sy }}" {{ $sy === active_school_year() ? 'selected' : '' }}>{{ $sy }}</option>
                            @endforeach
                        </select>
                        <input type="text" x-model="filters.search" @input="scheduleReload()"
                               placeholder="Search by class, teacher, or reason..."
                               class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Search</button>
                        <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
                    </form>
                </div>
            </div>

            <form method="POST" action="{{ route('principal.grade-unlocks.batch-approve') }}" onsubmit="return confirmBatchUnlocks(this);">
                @csrf
                <div class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-[#E8EAF6]">Pending</h3>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">Approve Selected</button>
                            <button type="submit" formaction="{{ route('principal.grade-unlocks.batch-reject') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition">Reject Selected</button>
                        </div>
                    </div>
                    <div x-show="loading && !html" class="space-y-3">
                        <template x-for="i in 3" :key="i">
                            <div class="skelly sk-card">
                                <div class="grid grid-cols-4 gap-4 px-2">
                                    <div class="skelly sk-line-md col-span-2"></div>
                                    <div class="skelly sk-line-md"></div>
                                    <div class="skelly sk-line-sm"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="(loading || isRateLimited) && html" x-cloak class="mb-2 px-3 py-2 rounded-lg bg-amber-50 dark:bg-[rgba(245,158,11,0.12)] border border-amber-200 dark:border-[rgba(245,158,11,0.3)] text-xs text-amber-800 dark:text-[#FCD34D]">Showing results for &quot;<span class="font-semibold" x-text="displayedSearch"></span>&quot; &mdash; searching for &quot;<span class="font-semibold" x-text="filters.search"></span>&quot;&hellip;</div>
                    <div x-show="html || !loading" x-cloak x-html="html" :class="((loading || isRateLimited) && html) ? 'opacity-50 transition-opacity' : 'opacity-100 transition-opacity'"></div>
                </div>
            </form>
        </div>

        <script>
        function toggleAllUnlocks(source) {
            document.querySelectorAll('.unlock-select').forEach(function (box) { box.checked = source.checked; });
        }
        function confirmBatchUnlocks(form) {
            var count = form.querySelectorAll('.unlock-select:checked').length;
            if (count === 0) {
                alert('Select at least one request first.');
                return false;
            }
            var action = (document.activeElement && document.activeElement.getAttribute('formaction')) || form.getAttribute('action');
            if (action && action.indexOf('reject') !== -1) {
                return confirm('Reject ' + count + ' selected request(s)? Grades stay submitted.');
            }
            return confirm('Approve ' + count + ' selected request(s)? Their grades return to Pending for correction.');
        }
        </script>

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
