@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><a href="{{ route('principal.schedules') }}" class="no-underline" style="color: var(--muted);">Schedules</a><span class="opacity-40"> / </span><span class="current">Manage</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Manage Schedules</h2>
    <p class="text-gray-600 mt-1">Add, import, or edit class schedules for efficiency.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ session('error') }}</div>@endif

<div x-data="{ tab: 'add' }" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="flex border-b border-gray-100">
        <button @click="tab='add'" :class="tab==='add' ? 'text-white' : 'text-gray-600 bg-white'" :style="tab==='add' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Add Schedule</button>
        <button @click="tab='import'" :class="tab==='import' ? 'text-white' : 'text-gray-600 bg-white'" :style="tab==='import' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Import CSV</button>
        <button @click="tab='edit'" :class="tab==='edit' ? 'text-white' : 'text-gray-600 bg-white'" :style="tab==='edit' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Edit Existing</button>
    </div>
    <div class="p-6">
        <div x-show="tab==='add'" x-cloak>
            <h3 class="font-semibold mb-3">Add Schedule (manual)</h3>
            <form method="POST" action="{{ route('principal.schedules.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <select name="class_id" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select Class</option>@foreach($classes as $cls)<option value="{{ $cls->id }}">{{ $cls->grade_level }} - {{ $cls->section }} — {{ $cls->subject->name }} ({{ $cls->teacher->name ?? 'No teacher' }})</option>@endforeach</select>
                <select name="day_of_week" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Day</option>@foreach($days as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select>
                <input type="time" name="start_time" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <input type="time" name="end_time" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <input type="text" name="room" placeholder="Room (e.g. J-101)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <button type="submit" class="md:col-span-5 px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Add Schedule</button>
            </form>
        </div>
        <div x-show="tab==='import'" x-cloak>
            <h3 class="font-semibold mb-3">Import CSV</h3>
            <p class="text-xs text-gray-500 mb-2">Columns: <code>grade_level, section, subject_code, day_of_week, start_time, end_time, room</code> — e.g., <code>Grade 7, A, ENG7, Monday, 08:00, 09:00, J-101</code></p>
            <div class="flex gap-2 mb-3">
                <a href="{{ route('principal.schedules.template') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200">Download template</a>
            </div>
            <form method="POST" action="{{ route('principal.schedules.import') }}" enctype="multipart/form-data" class="flex gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required class="text-sm border border-gray-300 rounded-lg px-3 py-1.5">
                <button type="submit" class="px-4 py-1.5 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Import CSV</button>
            </form>
            @if(session('import_errors') || session('import_skipped'))
                <div class="text-xs mt-3">@if(session('import_errors'))<p class="font-semibold text-red-600">Errors:</p><ul class="list-disc ml-4 text-red-600">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>@endif @if(session('import_skipped'))<p class="font-semibold text-amber-600 mt-2">Skipped:</p><ul class="list-disc ml-4 text-amber-600">@foreach(session('import_skipped') as $s)<li>{{ $s }}</li>@endforeach</ul>@endif</div>
            @endif
        </div>
        <div x-show="tab==='edit'" x-cloak>
            <h3 class="font-semibold mb-3">Edit Existing — Pick Class First</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <select id="editGrade" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select Grade</option>@foreach($gradeLevels as $gl)<option value="{{ $gl }}">{{ $gl }}</option>@endforeach</select>
                <select id="editSection" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select Section</option></select>
                <select id="editClass" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select Class</option></select>
            </div>
            <div id="editResults" class="text-sm text-gray-500">Choose grade → section → class to load its weekly slots. Each slot has <span class="font-semibold">Edit</span> / <span class="font-semibold">Delete</span> and an <span class="font-semibold">Add</span> row at bottom.</div>
            <script>
                const allClasses = @json($classes);
                const gradeEl = document.getElementById('editGrade');
                const sectionEl = document.getElementById('editSection');
                const classEl = document.getElementById('editClass');
                const resultsEl = document.getElementById('editResults');
                const days = @json($days);
                gradeEl.addEventListener('change', () => {
                    const grade = gradeEl.value;
                    const filtered = allClasses.filter(c => c.grade_level === grade);
                    const sections = [...new Set(filtered.map(c => c.section))];
                    sectionEl.innerHTML = '<option value=\"\">Select Section</option>' + sections.map(s => `<option value=\"${s}\">${s}</option>`).join('');
                    classEl.innerHTML = '<option value=\"\">Select Class</option>';
                    resultsEl.innerHTML = '<p class=\"text-sm text-gray-500\">Choose section and class.</p>';
                });
                sectionEl.addEventListener('change', () => {
                    const grade = gradeEl.value;
                    const section = sectionEl.value;
                    const filtered = allClasses.filter(c => c.grade_level === grade && c.section === section);
                    classEl.innerHTML = '<option value=\"\">Select Class</option>' + filtered.map(c => `<option value=\"${c.id}\">${c.subject?.name || c.subject_id} — ${c.teacher?.name || 'No teacher'}</option>`).join('');
                });
                classEl.addEventListener('change', async () => {
                    const classId = classEl.value;
                    if (!classId) { resultsEl.innerHTML = '<p class=\"text-sm text-gray-500\">Select a class.</p>'; return; }
                    resultsEl.innerHTML = '<p class=\"text-sm text-gray-500\">Loading slots...</p>';
                    try {
                        const res = await fetch(`{{ url('/principal/schedules') }}?class_id=${classId}&ajax=1`);
                        const data = await res.json();
                        // Extract table from returned html (simple)
                        const temp = document.createElement('div');
                        temp.innerHTML = data.html;
                        // Find the table rows for this class
                        resultsEl.innerHTML = '<p class=\"text-sm text-gray-600 mb-2\">Showing slots for selected class. Click a time to <strong>Edit</strong> or use <strong>Add</strong> at bottom.</p>' + temp.innerHTML;
                    } catch(e){ resultsEl.innerHTML = '<p class=\"text-sm text-red-500\">Failed to load.</p>'; }
                });
            </script>
        </div>
    </div>
</div>
@endsection
