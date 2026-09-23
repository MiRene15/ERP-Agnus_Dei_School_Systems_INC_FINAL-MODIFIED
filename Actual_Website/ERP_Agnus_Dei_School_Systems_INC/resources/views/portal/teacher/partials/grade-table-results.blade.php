@if(!$class)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="text-center py-12 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="font-medium">Select a class to start entering grades</p>
            <p class="text-sm text-gray-400 mt-1">Choose from the dropdown above</p>
        </div>
    </div>
@else
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-bold text-gray-900">{{ $class->subject->name ?? 'N/A' }}</h3>
            <p class="text-sm text-gray-500">{{ $class->grade_level }} - {{ $class->section }} | {{ $selectedPeriod }}</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Grading Period Tabs --}}
            @foreach($gradingPeriods as $period)
            <a href="{{ route('teacher.grade-table') }}?school_year={{ $schoolYear }}&class_id={{ $class->id }}&grading_period={{ $period }}"
               class="px-3 py-1 rounded-lg text-sm font-medium transition {{ $selectedPeriod === $period ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
               style="{{ $selectedPeriod === $period ? 'background: var(--navy);' : '' }}">
                {{ $period }}
            </a>
            @endforeach
        </div>
    </div>

    {{-- Class selector --}}
    <div class="mb-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="school_year" value="{{ $schoolYear }}">
            <input type="hidden" name="grading_period" value="{{ $selectedPeriod }}">
            <label class="text-sm font-medium text-gray-700">Class:</label>
            <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">-- Select Class --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                        {{ $c->subject->name ?? 'N/A' }} ({{ $c->grade_level }} - {{ $c->section }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($activeEnrollments->isEmpty())
        <p class="text-sm text-gray-500 text-center py-8">No active students enrolled in this class.</p>
    @else
    <div x-data="gradeTable()" x-init="init()">
        {{-- Save Bar --}}
        <div x-show="hasChanges" x-transition class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between">
            <span class="text-sm text-amber-700 font-medium">Unsaved changes</span>
            <button @click="saveAll()" :disabled="saving" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition">
                <span x-show="!saving">Save All</span>
                <span x-show="saving">Saving...</span>
            </button>
        </div>

        {{-- Success/Error message --}}
        <div x-show="message" x-transition class="mb-4 p-3 rounded-lg text-sm" :class="messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'" x-text="message"></div>

        {{-- Spreadsheet Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-300">
                        <th rowspan="2" class="py-2 px-2 text-left font-semibold text-gray-700 bg-gray-50 sticky left-0 z-10 min-w-[180px]">#</th>
                        <th rowspan="2" class="py-2 px-2 text-left font-semibold text-gray-700 bg-gray-50 sticky left-[48px] z-10 min-w-[180px]">Student</th>
                        @foreach($assessmentTypes as $type)
                        <th colspan="2" class="py-2 px-1 text-center font-semibold text-white text-xs uppercase tracking-wide border-l border-gray-400" style="background: var(--navy);">
                            {{ $type }}
                            <span class="block text-[10px] font-normal opacity-80">{{ number_format(($weights[$type] ?? 0.25) * 100, 0) }}%</span>
                        </th>
                        @endforeach
                        <th rowspan="2" class="py-2 px-3 text-center font-semibold text-gray-700 bg-gray-50 border-l-2 border-gray-400 min-w-[100px]">Computed</th>
                        <th rowspan="2" class="py-2 px-2 text-center font-semibold text-gray-700 bg-gray-50 min-w-[90px]">Actions</th>
                    </tr>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        @foreach($assessmentTypes as $type)
                        <th class="py-1 px-1 text-center text-[10px] font-medium text-gray-500 border-l border-gray-300">Raw</th>
                        <th class="py-1 px-1 text-center text-[10px] font-medium text-gray-500">Max</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeEnrollments as $idx => $enrollment)
                    @php
                        $studentAssessments = $existingAssessments->get($enrollment->id, collect());
                    @endphp
                    <tr class="border-b border-gray-100 hover:bg-blue-50/30 transition">
                        <td class="py-1.5 px-2 text-gray-400 text-xs sticky left-0 bg-white z-10">{{ $idx + 1 }}</td>
                        <td class="py-1.5 px-2 sticky left-[48px] bg-white z-10">
                            <span class="font-medium text-gray-900 text-sm">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                            <span class="text-xs text-gray-400 block">{{ $enrollment->student->student_number ?? '' }}</span>
                        </td>
                        @foreach($assessmentTypes as $type)
                        @php
                            $typeItems = $studentAssessments->where('type', $type)->values();
                            if ($typeItems->isEmpty()) {
                                $typeItems = collect([['id' => '', 'title' => '', 'raw_score' => '', 'max_score' => '']]);
                            }
                        @endphp
                        <td class="py-1 px-1 border-l border-gray-200 align-top" colspan="2">
                            <div class="space-y-1">
                                @foreach($typeItems as $itemIdx => $item)
                                <div class="flex items-center gap-0.5">
                                    <input type="number" 
                                        name="assessments[{{ $enrollment->id }}][{{ $type }}][{{ $itemIdx }}][raw_score]" 
                                        value="{{ $item['raw_score'] ?? $item->raw_score ?? '' }}" 
                                        step="0.01" min="0" placeholder="0"
                                        @blur="recalcStudent({{ $enrollment->id }}); hasChanges = true"
                                        class="w-14 text-center text-xs rounded border border-gray-300 px-1 py-1 focus:ring-2 focus:ring-blue-500 outline-none">
                                    <span class="text-gray-400 text-xs">/</span>
                                    <input type="number" 
                                        name="assessments[{{ $enrollment->id }}][{{ $type }}][{{ $itemIdx }}][max_score]" 
                                        value="{{ $item['max_score'] ?? $item->max_score ?? '' }}" 
                                        step="0.01" min="0" placeholder="0"
                                        @blur="recalcStudent({{ $enrollment->id }}); hasChanges = true"
                                        class="w-14 text-center text-xs rounded border border-gray-300 px-1 py-1 focus:ring-2 focus:ring-blue-500 outline-none">
                                    <input type="hidden" name="assessments[{{ $enrollment->id }}][{{ $type }}][{{ $itemIdx }}][type]" value="{{ $type }}">
                                    <input type="hidden" name="assessments[{{ $enrollment->id }}][{{ $type }}][{{ $itemIdx }}][title]" value="{{ $item['title'] ?? $item->title ?? '' }}">
                                </div>
                                @endforeach
                                <button type="button" @click="addItem('{{ $enrollment->id }}', '{{ $type }}', this)" class="text-[10px] text-blue-500 hover:text-blue-700 font-medium">+ Add</button>
                            </div>
                        </td>
                        @endforeach
                        <td class="py-1.5 px-3 text-center border-l-2 border-gray-300">
                            <span class="font-bold text-sm" :class="getComputed({{ $enrollment->id }}) >= 75 ? 'text-green-600' : 'text-red-600'" x-text="getComputed({{ $enrollment->id }}).toFixed(2)"></span>
                        </td>
                        <td class="py-1.5 px-2 text-center">
                            <button @click="removeEmptyRows()" class="text-gray-400 hover:text-red-500 text-xs" title="Clear empty rows">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endif

@push('scripts')
<script>
function gradeTable() {
    return {
        hasChanges: false,
        saving: false,
        message: '',
        messageType: 'success',
        computed: @json($computedMap),
        weights: @json($weights),

        init() {
            document.querySelectorAll('input[type="number"]').forEach(el => {
                el.addEventListener('input', () => { this.hasChanges = true; });
            });
        },

        recalcStudent(enrollmentId) {
            const types = ['Written Work', 'Quiz', 'Seatwork', 'Exam'];
            let weightedSum = 0;

            types.forEach(type => {
                const rawInputs = document.querySelectorAll(`input[name^="assessments[${enrollmentId}][${type}]"][name$="[raw_score]"]`);
                const maxInputs = document.querySelectorAll(`input[name^="assessments[${enrollmentId}][${type}]"][name$="[max_score]"]`);
                
                let totalRaw = 0, totalMax = 0;
                rawInputs.forEach(i => totalRaw += parseFloat(i.value) || 0);
                maxInputs.forEach(i => totalMax += parseFloat(i.value) || 0);
                
                const percentage = totalMax > 0 ? (totalRaw / totalMax) * 100 : 0;
                weightedSum += percentage * (this.weights[type] || 0.25);
            });

            this.computed[enrollmentId] = Math.round(weightedSum * 100) / 100;
        },

        getComputed(enrollmentId) {
            return this.computed[enrollmentId] || 0;
        },

        addItem(enrollmentId, type, btn) {
            const container = btn.closest('.space-y-1');
            const count = container.querySelectorAll('input[type="number"]').length / 2;
            const newName = `assessments[${enrollmentId}][${type}][${count}]`;
            
            const div = document.createElement('div');
            div.className = 'flex items-center gap-0.5';
            div.innerHTML = `
                <input type="number" name="${newName}[raw_score]" step="0.01" min="0" placeholder="0" class="w-14 text-center text-xs rounded border border-gray-300 px-1 py-1 focus:ring-2 focus:ring-blue-500 outline-none">
                <span class="text-gray-400 text-xs">/</span>
                <input type="number" name="${newName}[max_score]" step="0.01" min="0" placeholder="0" class="w-14 text-center text-xs rounded border border-gray-300 px-1 py-1 focus:ring-2 focus:ring-blue-500 outline-none">
                <input type="hidden" name="${newName}[type]" value="${type}">
                <input type="hidden" name="${newName}[title]" value="">
            `;
            container.insertBefore(div, btn);
            this.hasChanges = true;
        },

        removeEmptyRows() {
            document.querySelectorAll('.space-y-1').forEach(container => {
                const rows = container.querySelectorAll('.flex.items-center.gap-0\\.5');
                rows.forEach(row => {
                    const inputs = row.querySelectorAll('input[type="number"]');
                    const allEmpty = Array.from(inputs).every(i => i.value === '');
                    if (allEmpty && rows.length > 1) {
                        row.remove();
                    }
                });
            });
            this.hasChanges = true;
        },

        async saveAll() {
            this.saving = true;
            this.message = '';

            const form = new FormData();
            form.append('grading_period', '{{ $selectedPeriod }}');
            form.append('_token', '{{ csrf_token() }}');

            const inputs = document.querySelectorAll('input[name^="assessments["]');
            inputs.forEach(input => {
                if (input.type !== 'hidden' && input.value !== '') {
                    form.append(input.name, input.value);
                } else if (input.type === 'hidden') {
                    form.append(input.name, input.value);
                }
            });

            try {
                const response = await fetch('{{ route("teacher.grade-table.save", $class) }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: form,
                });
                const data = await response.json();
                if (data.success) {
                    this.message = data.message;
                    this.messageType = 'success';
                    this.hasChanges = false;
                } else {
                    this.message = data.message || 'Save failed.';
                    this.messageType = 'error';
                }
            } catch (e) {
                this.message = 'Network error. Please try again.';
                this.messageType = 'error';
            }
            this.saving = false;
        }
    };
}
</script>
@endpush
