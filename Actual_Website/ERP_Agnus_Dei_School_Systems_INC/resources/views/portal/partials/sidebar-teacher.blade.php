<a href="{{ route('teacher.schedule') }}" class="sidebar-link {{ request()->routeIs('teacher.schedule') ? 'active' : '' }}">
    <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span class="sidebar-label">Schedule</span>
</a>

<div x-data="{ open: {{ request()->routeIs('teacher.class-list*','teacher.grade-assessment*','teacher.computed-grades*') ? 'true' : 'false' }} }">
    <button @click="open = !open" class="sidebar-link w-full {{ request()->routeIs('teacher.class-list*','teacher.grade-assessment*','teacher.computed-grades*') ? 'active' : '' }}">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        <span class="sidebar-label flex-1 text-left">Classes</span>
        <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <div x-show="open" x-transition.opacity.duration.200ms style="display: none;">
        <a href="{{ route('teacher.class-list') }}" class="sidebar-link pl-12 {{ request()->routeIs('teacher.class-list*') ? 'active' : '' }}">
            <span class="sidebar-label">List of Classes</span>
        </a>
        <a href="{{ route('teacher.grade-assessment') }}" class="sidebar-link pl-12 {{ request()->routeIs('teacher.grade-assessment*') ? 'active' : '' }}">
            <span class="sidebar-label">Grade Assessment</span>
        </a>
        <a href="{{ route('teacher.computed-grades') }}" class="sidebar-link pl-12 {{ request()->routeIs('teacher.computed-grades*') ? 'active' : '' }}">
            <span class="sidebar-label">Computed Grades</span>
        </a>
    </div>
</div>
