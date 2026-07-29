@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('nurse.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('nurse.logs') }}" class="no-underline" style="color: var(--muted);">Consultation Logs</a>
    <span class="opacity-40">/</span>
    <span class="current">New Log</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">New Clinic Log</h2>
    <p class="text-gray-600 mt-1">Record a student clinic visit.</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('nurse.logs.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                <select name="student_id" required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Select student...</option>
                    @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->last_name }}, {{ $s->first_name }} ({{ $s->student_number ?? 'N/A' }})</option>
                    @endforeach
                </select>
                @error('student_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Visit Date</label>
                <input type="date" name="incident_date" value="{{ old('incident_date', date('Y-m-d')) }}" required
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @error('incident_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Complaint / Symptoms</label>
                <textarea name="complaint" rows="2" placeholder="Describe the complaint or symptoms"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('complaint') }}</textarea>
                @error('complaint') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                <textarea name="diagnosis" rows="2" placeholder="Diagnosis or assessment"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('diagnosis') }}</textarea>
                @error('diagnosis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Treatment / First Aid Given</label>
                <textarea name="treatment" rows="2" placeholder="Treatment administered"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('treatment') }}</textarea>
                @error('treatment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" placeholder="Additional notes"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referred To</label>
                <input type="text" name="referred_to" value="{{ old('referred_to') }}" placeholder="e.g. Hospital, Specialist, etc."
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                @error('referred_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Log</button>
                <a href="{{ route('nurse.logs') }}" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection