<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">End-of-Year Promotion</h2>
    <p class="text-gray-600 mt-1">Select an action for each student to process end-of-year promotion, retention, graduation, or transfer.</p>
</div>

@if($enrollments->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
    <p class="text-sm text-gray-500 py-4">No active enrollments found.</p>
</div>
@else
<form method="POST" action="{{ route('admin.promotion.process') }}" onsubmit="return confirm('Process all selected actions? This will create new enrollments and carry over fees.')">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">New School Year:</label>
        <select name="school_year" required class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">Select school year</option>
            @foreach($schoolYears as $sy)
            <option value="{{ $sy }}">{{ $sy }}</option>
            @endforeach
            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}">{{ date('Y') . '-' . (date('Y') + 1) }} (New)</option>
        </select>
    </div>

    @foreach($enrollments as $gradeLevel => $gradeEnrollments)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ $gradeLevel }} <span class="text-sm font-normal text-gray-500">({{ $gradeEnrollments->count() }} student(s))</span></h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Student</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Section</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Balance</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-600">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradeEnrollments as $enrollment)
                    @php
                        $isGrade12 = $gradeLevel === 'Grade 12';
                    @endphp
                    <tr class="border-b border-gray-100">
                        <td class="py-3 px-2">
                            <span class="font-medium text-gray-900">{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</span>
                        </td>
                        <td class="py-3 px-2 text-gray-700">{{ $enrollment->section->section_name ?? 'N/A' }}</td>
                        <td class="py-3 px-2">
                            @php $bal = $enrollment->student->ledger?->balance ?? 0; @endphp
                            <span class="{{ $bal > 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">
                                ₱ {{ number_format($bal, 2) }}
                            </span>
                        </td>
                        <td class="py-3 px-2">
                            <select name="actions[{{ $enrollment->id }}]" required class="rounded-lg border border-gray-300 px-2 py-1 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Select action</option>
                                @if(!$isGrade12)
                                <option value="promote">Promote to {{ $gradeLevel === 'Grade 11' ? 'Grade 12' : 'next grade' }}</option>
                                <option value="retain">Retain in {{ $gradeLevel }}</option>
                                @endif
                                <option value="graduate" {{ $isGrade12 ? 'selected' : '' }}>Graduate</option>
                                <option value="transfer">Transfer Out</option>
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    <div class="flex justify-end">
        <button type="submit" class="px-6 py-3 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            Process All Actions
        </button>
    </div>
</form>
@endif
