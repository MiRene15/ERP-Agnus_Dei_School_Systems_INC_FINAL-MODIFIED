@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Assign Fees</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Assign Fees</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Tuition schedules and graduation fees — one place for yearly fee setup.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{!! session('success') !!}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div x-data="{ tab: ((v) => ['fee-management','graduation-fees'].includes(v) ? v : 'fee-management')(new URLSearchParams(window.location.search).get('view')), setTab(v) { this.tab = v; const u = new URL(window.location.href); if (v === 'fee-management') { u.searchParams.delete('view'); } else { u.searchParams.set('view', v); } window.history.replaceState({}, '', u); } }">
    <div class="inline-flex gap-1 mb-6 p-1 rounded-xl bg-gray-100 dark:bg-[#23274C]" role="tablist" aria-label="Fee type">
        <button @click="setTab('fee-management')" :class="tab==='fee-management' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='fee-management' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='fee-management'">Fee Management</button>
        <button @click="setTab('graduation-fees')" :class="tab==='graduation-fees' ? 'text-white shadow font-semibold' : 'text-gray-500 dark:text-[#8A90B0]'" :style="tab==='graduation-fees' ? 'background: var(--navy);' : ''" class="px-4 py-2 text-sm rounded-lg transition" role="tab" :aria-selected="tab==='graduation-fees'">Graduation Fees</button>
    </div>

    <div x-show="tab==='fee-management'" x-cloak>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Fee Management</h2>
                <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Manage tuition and miscellaneous fees per grade level, term, and school year.</p>
            </div>
            <a href="{{ route('directress.fees.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Fee</a>
        </div>

        <div x-data="ajaxTable('{{ route('directress.fees') }}', { school_year: '{{ request('school_year') }}' })">
            <div class="mb-4 flex gap-2 flex-wrap items-center">
                <form method="GET" class="flex gap-2 flex-1 flex-wrap" @submit.prevent="reload()">
                    <select x-model="filters.school_year" @change="reload()" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">All School Years</option>
                        @foreach($schoolYears as $sy)
                            <option value="{{ $sy }}">{{ $sy }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);">Filter</button>
                    <button type="button" @click="reset()" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Clear</button>
                </form>
            </div>

            <div x-show="loading" class="space-y-3">
                <template x-for="i in 5" :key="i">
                    <div class="skelly sk-card">
                        <div class="grid grid-cols-4 gap-4 px-2">
                            <div class="skelly sk-line-md"></div>
                            <div class="skelly sk-line-md"></div>
                            <div class="skelly sk-line-md"></div>
                            <div class="skelly sk-line-sm"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!loading" x-cloak @click="handlePaginationClick($event)" x-ref="results" x-html="html" class="fade-in"></div>
        </div>
    </div>

    <div x-show="tab==='graduation-fees'" x-cloak>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Graduation Fees</h2>
                <p class="text-gray-600 mt-1">Manage graduation and miscellaneous fees for graduating students.</p>
            </div>
            <a href="{{ route('directress.graduation-fees.create') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">+ Add Graduation Fee</a>
        </div>

        @forelse($gradFees as $gradeLevel => $gradeFees)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-2 font-medium text-gray-600">School Year</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Graduation Fee</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Other Fees</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Total</th>
                            <th class="text-left py-3 px-2 font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gradeFees as $gf)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-2 text-gray-700">{{ $gf->school_year }}</td>
                            <td class="py-3 px-2 text-gray-700">₱ {{ number_format($gf->graduation_fee, 2) }}</td>
                            <td class="py-3 px-2 text-gray-700">₱ {{ number_format($gf->other_fees, 2) }}</td>
                            <td class="py-3 px-2 font-medium text-gray-900">₱ {{ number_format($gf->graduation_fee + $gf->other_fees, 2) }}</td>
                            <td class="py-3 px-2">
                                <div class="flex gap-1 flex-wrap">
                                    <a href="{{ route('directress.graduation-fees.assigned', $gf) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">View Assigned</a>
                                    <a href="{{ route('directress.graduation-fees.edit', $gf) }}" class="px-2 py-1 text-xs font-medium text-gray-600 hover:text-gray-800">Edit</a>
                                    <form method="POST" action="{{ route('directress.graduation-fees.destroy', $gf) }}" onsubmit="return confirm('Delete this graduation fee?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 text-center py-4">No graduation fees created yet. <a href="{{ route('directress.graduation-fees.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Add one</a>.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
