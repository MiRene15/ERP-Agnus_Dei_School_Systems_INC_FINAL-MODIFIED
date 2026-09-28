@extends('portal.layouts.app')
@section('breadcrumbs')
    <a href="{{ route('principal.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a><span class="opacity-40"> / </span><a href="{{ route('principal.schedules') }}" class="no-underline" style="color: var(--muted);">Schedules</a><span class="opacity-40"> / </span><span class="current">Manage</span>
@endsection
@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-[#E8EAF6]">Manage Schedules</h2>
    <p class="text-gray-600 dark:text-[#C1C4DC] mt-1">Add, import, or edit class schedules for efficiency.</p>
</div>
@if(session('success'))<div class="mb-4 p-3 bg-green-50 dark:bg-[rgba(74,222,128,0.12)] border border-green-200 dark:border-[rgba(74,222,128,0.25)] rounded text-sm text-green-700 dark:text-[#4ADE80]">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 p-3 bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] rounded text-sm text-red-700 dark:text-[#F87171]">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 dark:bg-[rgba(248,113,113,0.12)] border border-red-200 dark:border-[rgba(248,113,113,0.25)] rounded text-sm text-red-700 dark:text-[#F87171]">
        <ul class="list-disc ml-4">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
@endif

<div x-data="{ tab: 'add' }" class="bg-white dark:bg-[#1A1E3B] rounded-xl shadow-sm border border-gray-100 dark:border-[#2A2F58] overflow-hidden">
    <div class="flex border-b border-gray-100 dark:border-[#2A2F58]">
        <button @click="tab='add'" :class="tab==='add' ? 'text-white' : 'text-gray-600 dark:text-[#C1C4DC] bg-white dark:bg-[#1A1E3B]'" :style="tab==='add' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Add Schedule</button>
        <button @click="tab='import'" :class="tab==='import' ? 'text-white' : 'text-gray-600 dark:text-[#C1C4DC] bg-white dark:bg-[#1A1E3B]'" :style="tab==='import' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Import CSV</button>
        <button @click="tab='edit'" :class="tab==='edit' ? 'text-white' : 'text-gray-600 dark:text-[#C1C4DC] bg-white dark:bg-[#1A1E3B]'" :style="tab==='edit' ? 'background: var(--navy);' : ''" class="flex-1 py-3 text-sm font-semibold">Edit Existing</button>
    </div>
    <div class="p-6">
        <div x-show="tab==='add'" x-cloak>
            <h3 class="font-semibold mb-3 text-gray-900 dark:text-[#E8EAF6]">Add Schedule (manual)</h3>
            <form method="POST" action="{{ route('principal.schedules.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                @csrf
                <select name="class_id" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="">Select Class</option>@foreach($classes as $cls)<option value="{{ $cls->id }}">{{ $cls->grade_level }} - {{ $cls->section }} — {{ $cls->subject->name }} ({{ $cls->teacher->name ?? 'No teacher' }})</option>@endforeach</select>
                <select name="day_of_week" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="">Day</option>@foreach($days as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select>
                <input type="time" name="start_time" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm">
                <input type="time" name="end_time" required class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm">
                <input type="text" name="room" placeholder="Room (e.g. J-101)" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm">
                <button type="submit" class="md:col-span-5 px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Add Schedule</button>
            </form>
            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-2">Overlapping validation checks class, teacher, and room conflicts. Audit logged.</p>
        </div>
        <div x-show="tab==='import'" x-cloak>
            <h3 class="font-semibold mb-3 text-gray-900 dark:text-[#E8EAF6]">Import CSV</h3>
            <p class="text-xs text-gray-500 dark:text-[#8A90B0] mb-2">Columns: <code>grade_level, section, subject_code, day_of_week, start_time, end_time, room</code> — e.g., <code>Grade 7, Charity, G7-ENG, Monday, 08:00, 09:00, J-101</code></p>
            <div class="flex gap-2 mb-3">
                <a href="{{ route('principal.schedules.template') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-[#23274C] hover:bg-gray-200 dark:hover:bg-[#2A2F58] text-gray-700 dark:text-[#C1C4DC]">Download template</a>
            </div>
            <form method="POST" action="{{ route('principal.schedules.import') }}" enctype="multipart/form-data" class="flex gap-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" required class="text-sm border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded-lg px-3 py-1.5">
                <button type="submit" class="px-4 py-1.5 rounded-lg text-sm font-semibold text-white" style="background: var(--navy);">Import CSV</button>
            </form>
            @if(session('import_errors') || session('import_skipped'))
                <div class="text-xs mt-3">@if(session('import_errors'))<p class="font-semibold text-red-600 dark:text-[#F87171]">Errors:</p><ul class="list-disc ml-4 text-red-600 dark:text-[#F87171]">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>@endif @if(session('import_skipped'))<p class="font-semibold text-amber-600 dark:text-[#FCD34D] mt-2">Skipped:</p><ul class="list-disc ml-4 text-amber-600 dark:text-[#FCD34D]">@foreach(session('import_skipped') as $s)<li>{{ $s }}</li>@endforeach</ul>@endif</div>
            @endif
        </div>
        <div x-show="tab==='edit'" x-cloak>
            <h3 class="font-semibold mb-3 text-gray-900 dark:text-[#E8EAF6]">Edit Existing — Pick Class First</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                <select id="editGrade" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="">Select Grade</option>@foreach($gradeLevels as $gl)<option value="{{ $gl }}">{{ $gl }}</option>@endforeach</select>
                <select id="editSection" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="">Select Section</option></select>
                <select id="editClass" class="rounded-lg border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] px-3 py-2 text-sm"><option value="">Select Class</option></select>
            </div>
            <div id="editResults" class="text-sm">
                <p class="text-gray-500 dark:text-[#8A90B0]">Choose grade → section → class to load and edit its weekly slots. All subjects for the selected section will be listed below.</p>
            </div>
            <script>
                const allClasses = @json($classes);
                const days = @json($days);
                const schedulesBase = '{{ url('/principal/schedules') }}';
                const csrf = '{{ csrf_token() }}';
                const gradeEl = document.getElementById('editGrade');
                const sectionEl = document.getElementById('editSection');
                const classEl = document.getElementById('editClass');
                const resultsEl = document.getElementById('editResults');
                const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                const timeVal = t => (t || '').slice(0, 5);
                const introHtml = '<p class="text-sm text-gray-500 dark:text-[#8A90B0]">Choose grade &rarr; section &rarr; class to load and edit its weekly slots. All subjects for the selected section will be listed below.</p>';
                const daySelect = day => '<select name="day_of_week" class="px-1 py-0.5 border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded text-xs">' + days.map(d => '<option value="' + d + '"' + (d === day ? ' selected' : '') + '>' + d + '</option>').join('') + '</select>';
                gradeEl.addEventListener('change', () => {
                    const grade = gradeEl.value;
                    const filtered = allClasses.filter(c => c.grade_level === grade);
                    const sections = [...new Set(filtered.map(c => c.section))];
                    sectionEl.innerHTML = '<option value="">Select Section</option>' + sections.map(s => `<option value="${esc(s)}">${esc(s)}</option>`).join('');
                    classEl.innerHTML = '<option value="">Select Class</option>';
                    resultsEl.innerHTML = introHtml;
                });
                sectionEl.addEventListener('change', () => {
                    const grade = gradeEl.value;
                    const section = sectionEl.value;
                    const filtered = allClasses.filter(c => c.grade_level === grade && c.section === section);
                    const subjectOptions = filtered.length ? filtered.map(c => `<option value="${c.id}">${esc(c.subject?.name || c.subject_id)} — ${esc(c.teacher?.name || 'No teacher')}</option>`).join('') : '<option value="">No classes yet — add via Add tab</option>';
                    classEl.innerHTML = '<option value="">Select Class</option>' + subjectOptions;
                    if (filtered.length) {
                        resultsEl.innerHTML = '<p class="text-xs text-gray-400 dark:text-[#8A90B0] mb-2">All subjects for ' + esc(grade) + ' ' + esc(section) + ':</p><div class="flex flex-wrap gap-1">' + filtered.map(c => `<span class="px-2 py-1 bg-gray-100 dark:bg-[#23274C] rounded text-xs dark:text-[#C1C4DC]">${esc(c.subject?.name || c.subject_id)}</span>`).join('') + '</div>';
                    }
                });
                classEl.addEventListener('change', () => {
                    const classId = classEl.value;
                    if (!classId) return;
                    const cls = allClasses.find(c => String(c.id) === String(classId));
                    if (!cls) return;
                    const slots = (cls.schedules || []).slice().sort((a, b) => days.indexOf(a.day_of_week) - days.indexOf(b.day_of_week));
                    if (!slots.length) {
                        resultsEl.innerHTML = '<p class="text-sm text-gray-500 dark:text-[#8A90B0]">No schedule slots yet for <strong>' + esc(cls.subject?.name || 'this class') + '</strong> — use the <strong>Add Schedule</strong> tab to create one.</p>';
                        return;
                    }
                    const rows = slots.map(s => `
                        <tr class="border-b border-gray-100 dark:border-[#2A2F58]">
                            <td class="py-2 px-2"><form method="POST" action="${schedulesBase}/${s.id}" class="flex gap-1 items-center flex-wrap">
                                <input type="hidden" name="_token" value="${csrf}">
                                <input type="hidden" name="_method" value="PATCH">
                                ${daySelect(s.day_of_week)}
                                <input type="time" name="start_time" value="${timeVal(s.start_time)}" required class="px-1 py-0.5 border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded text-xs">
                                <input type="time" name="end_time" value="${timeVal(s.end_time)}" required class="px-1 py-0.5 border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded text-xs">
                                <input type="text" name="room" maxlength="50" placeholder="Room" value="${esc(s.room)}" class="w-20 px-1 py-0.5 border border-gray-300 dark:border-[#3B4172] dark:bg-[#23274C] dark:text-[#E8EAF6] rounded text-xs">
                                <button type="submit" class="px-2 py-0.5 bg-blue-600 text-white rounded text-xs">Save</button>
                            </form></td>
                            <td class="py-2 px-2 whitespace-nowrap">
                                <a href="${schedulesBase}/${s.id}/edit" class="px-2 py-0.5 bg-gray-100 dark:bg-[#23274C] rounded text-xs dark:text-[#C1C4DC]">Open</a>
                                <form method="POST" action="${schedulesBase}/${s.id}" class="inline" onsubmit="return confirm('Delete this schedule slot? This will be audit logged.');">
                                    <input type="hidden" name="_token" value="${csrf}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="px-2 py-0.5 bg-red-50 dark:bg-[rgba(248,113,113,0.12)] text-red-600 dark:text-[#F87171] rounded text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>`).join('');
                    resultsEl.innerHTML = `
                        <div class="bg-white dark:bg-[#1A1E3B] rounded-xl border border-gray-100 dark:border-[#2A2F58] p-4">
                            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mb-2">${esc(cls.subject?.name || '')} — ${esc(cls.grade_level)} ${esc(cls.section)} (${slots.length} slot${slots.length > 1 ? 's' : ''})</p>
                            <table class="w-full text-sm"><tbody>${rows}</tbody></table>
                            <p class="text-xs text-gray-400 dark:text-[#8A90B0] mt-3">Edits validate class, section, teacher and room conflicts. <strong>Open</strong> loads the full edit page; Delete requires confirmation and is audit logged.</p>
                        </div>`;
                });
            </script>
        </div>
    </div>
</div>
@endsection
