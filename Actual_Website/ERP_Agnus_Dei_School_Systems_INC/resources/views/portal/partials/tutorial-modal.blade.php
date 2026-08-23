@if(auth()->user()->first_login_at && request()->routeIs('*.dashboard') && !session('tutorial_dismissed'))
@php
$tutorial = match(auth()->user()->role_id) {
    1 => ['title' => 'Welcome to the Admin Dashboard!', 'desc' => 'Manage users, review verification, configure school settings, and export data. Use the sidebar to access all admin tools.', 'link' => route('admin.pending-accounts'), 'linkText' => 'Review Verification →', 'color' => 'purple'],
    2 => ['title' => 'Welcome to the Registrar Dashboard!', 'desc' => 'Review student applications, verify requirements, manage enrollments, and process student records.', 'link' => route('registrar.admissions.index'), 'linkText' => 'Review Admissions →', 'color' => 'teal'],
    3 => ['title' => 'Welcome to the Cashier Dashboard!', 'desc' => 'Process payments, view collections, and manage student ledgers. Use the sidebar to search students and record payments.', 'link' => route('cashier.payments'), 'linkText' => 'Search Students →', 'color' => 'green'],
    4 => ['title' => 'Welcome to the Faculty Portal!', 'desc' => 'View your classes, manage student grades, and check your daily schedule. Click a class to open its grade sheet.', 'link' => route('teacher.schedule'), 'linkText' => 'View Schedule →', 'color' => 'orange'],
    5 => ['title' => 'Welcome to the Library Dashboard!', 'desc' => 'Manage book inventory, track borrowings, and monitor overdue returns. Use the sidebar for all library tools.', 'link' => route('librarian.books'), 'linkText' => 'Manage Books →', 'color' => 'yellow'],
    6 => ['title' => 'Welcome to the Health & Wellness Dashboard!', 'desc' => 'Track student health records, log consultations, and manage referrals. Use the sidebar for all clinic tools.', 'link' => route('nurse.logs'), 'linkText' => 'Log Consultation →', 'color' => 'red'],
    7 => ['title' => 'Welcome to the Student Portal!', 'desc' => 'Apply for admission, track your application, view grades, and manage your account. Complete all steps to get enrolled.', 'link' => route('student.dashboard'), 'linkText' => 'Go to Dashboard →', 'color' => 'blue'],
    8 => ['title' => 'Welcome to the Directress Dashboard!', 'desc' => 'Manage fee schedules, graduation fees, and review school financials.', 'link' => route('directress.dashboard'), 'linkText' => 'Manage Fees →', 'color' => 'indigo'],
    9 => ['title' => 'Welcome to the Principal Dashboard!', 'desc' => 'Manage class schedules, view grades, and publish announcements.', 'link' => route('principal.schedules'), 'linkText' => 'Manage Schedules →', 'color' => 'violet'],
    default => ['title' => 'Welcome!', 'desc' => 'Explore your dashboard to get started.', 'link' => route('dashboard'), 'linkText' => 'Go to Dashboard →', 'color' => 'purple'],
};
@endphp
<div x-data="tutorialModal()" x-show="show" x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[998] flex items-center justify-center p-4"
     style="background: rgba(14,17,36,0.55); backdrop-filter: blur(6px);">
    <div class="bg-white dark:bg-[#1A1E3B] rounded-2xl shadow-2xl w-full max-w-md p-8 relative border border-gray-100 dark:border-[#2A2F58]" @click.stop>
        <button @click="dismiss()" class="absolute top-4 right-4 text-gray-400 dark:text-[#6A7094] hover:text-gray-600 dark:hover:text-[#E8EAF6] transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: var(--navy);">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-[#E8EAF6]">{{ $tutorial['title'] }}</h3>
            <p class="text-sm text-gray-500 dark:text-[#8A90B0] mt-2">{{ $tutorial['desc'] }}</p>
        </div>
        <div class="flex flex-col gap-3">
            <a href="{{ $tutorial['link'] }}" @click="dismiss()" class="w-full py-3 rounded-lg text-white font-bold text-sm tracking-wide text-center transition hover:opacity-90" style="background: var(--navy);">
                {{ $tutorial['linkText'] }}
            </a>
            <button @click="dismiss()" class="w-full py-2.5 rounded-lg text-sm font-semibold text-gray-600 dark:text-[#8A90B0] bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58] transition">
                Got it, don't show again
            </button>
        </div>
        <p class="text-xs text-gray-400 dark:text-[#6A7094] text-center mt-3">You can always revisit this guide from your dashboard.</p>
    </div>
</div>
<script>
function tutorialModal() {
    return {
        show: true,
        async dismiss() {
            this.show = false;
            try {
                await fetch('/dismiss-welcome', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });
            } catch (e) {}
        }
    }
}
</script>
@endif
