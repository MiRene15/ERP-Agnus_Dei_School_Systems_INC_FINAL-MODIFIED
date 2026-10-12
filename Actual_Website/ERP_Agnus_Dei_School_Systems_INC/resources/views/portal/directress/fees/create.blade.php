@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('directress.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <a href="{{ route('directress.fees') }}" class="no-underline" style="color: var(--muted);">Fee Schedule</a>
    <span class="opacity-40">/</span>
    <span class="current">Create</span>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Create Fee Schedule</h2>
    <p class="text-gray-600 mt-1">Set tuition and miscellaneous fees for a grade level and term.</p>
</div>

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-lg">
    <form method="POST" action="{{ route('directress.fees.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">School Year *</label>
            <input type="text" name="school_year" value="{{ old('school_year', date('Y') . '-' . (date('Y') + 1)) }}" required maxlength="20" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            @error('school_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
            <select name="grade_level" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select grade level</option>
                @foreach($gradeLevels as $gl)
                <option value="{{ $gl }}" {{ old('grade_level') === $gl ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
            </select>
            @error('grade_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Term *</label>
            <select name="term" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Select term</option>
                @foreach($terms as $term)
                <option value="{{ $term }}" {{ old('term') === $term ? 'selected' : '' }}>{{ $term }}</option>
                @endforeach
            </select>
            @error('term') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tuition Fee (₱) *</label>
                <input type="number" name="tuition_fee" value="{{ old('tuition_fee') }}" required step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @error('tuition_fee') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Misc Total (₱) *</label>
                <input type="number" id="misc_fee" name="misc_fee" value="{{ old('misc_fee') }}" required step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                @error('misc_fee') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="mb-2">
            <p class="text-sm font-medium text-gray-700">4-way split <span class="font-normal text-gray-500">(must add to the Misc total)</span></p>
            @error('misc_fee_items') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <p id="split-hint" class="text-xs text-gray-500 mt-1">Fill the 4 boxes below so they add to the Misc total.</p>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Books (₱) *</label>
                <input type="number" name="misc_fee_items[books]" value="{{ old('misc_fee_items.books', 0) }}" required step="0.01" min="0" data-split class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Uniform (₱) *</label>
                <input type="number" name="misc_fee_items[uniform]" value="{{ old('misc_fee_items.uniform', 0) }}" required step="0.01" min="0" data-split class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID (₱) *</label>
                <input type="number" name="misc_fee_items[id]" value="{{ old('misc_fee_items.id', 0) }}" required step="0.01" min="0" data-split class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Miscellaneous (₱) *</label>
                <input type="number" name="misc_fee_items[miscellaneous]" value="{{ old('misc_fee_items.miscellaneous', 0) }}" required step="0.01" min="0" data-split class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>
        <script>
            (function () {
                var total = document.getElementById('misc_fee');
                var hint = document.getElementById('split-hint');
                var boxes = document.querySelectorAll('[data-split]');
                function tick() {
                    var sum = 0;
                    boxes.forEach(function (b) { sum += parseFloat(b.value || 0); });
                    var t = parseFloat(total.value || 0);
                    sum = Math.round(sum * 100) / 100; t = Math.round(t * 100) / 100;
                    var diff = Math.round((sum - t) * 100) / 100;
                    hint.textContent = 'Split adds to ₱' + sum.toFixed(2) + ' of ₱' + t.toFixed(2)
                        + (diff === 0 ? ' — adds up, ready to save.' : ' — off by ₱' + Math.abs(diff).toFixed(2) + '.');
                }
                total.addEventListener('input', tick);
                boxes.forEach(function (b) { b.addEventListener('input', tick); });
                tick();
            })();
        </script>
        <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Create Fee Schedule</button>
    </form>
</div>
@endsection
