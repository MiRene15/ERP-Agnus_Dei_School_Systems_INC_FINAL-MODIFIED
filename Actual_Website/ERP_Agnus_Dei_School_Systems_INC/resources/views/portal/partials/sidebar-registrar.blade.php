<a href="{{ route('registrar.admissions.index') }}" class="sidebar-link {{ request()->routeIs('registrar.admissions.*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span class="sidebar-label">Admissions Queue</span>
</a>
<a href="{{ route('registrar.report-cards.index') }}" class="sidebar-link {{ request()->routeIs('registrar.report-cards.*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <span class="sidebar-label">Report Cards</span>
</a>
<a href="{{ route('registrar.requests') }}" class="sidebar-link {{ request()->routeIs('registrar.requests*', 'registrar.withdrawals.*', 'discount-requests.*', 'registrar.promotion.*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3-2-1.343-2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="sidebar-label">Requests</span>
</a>
<a href="{{ route('registrar.sections-subjects') }}" class="sidebar-link {{ request()->routeIs('registrar.sections-subjects*', 'registrar.sections.*', 'registrar.subjects.*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    <span class="sidebar-label">Sections &amp; Subjects</span>
</a>
<a href="{{ route('registrar.grade-fee') }}" class="sidebar-link {{ request()->routeIs('registrar.grade-fee*', 'registrar.grade-unlocks.*', 'registrar.fee-assignment.*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
    <span class="sidebar-label">Grade &amp; Fee</span>
</a>
<a href="{{ route('registrar.reports') }}" class="sidebar-link {{ request()->routeIs('registrar.reports*') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2z"/></svg>
    <span class="sidebar-label">Reports</span>
</a>
