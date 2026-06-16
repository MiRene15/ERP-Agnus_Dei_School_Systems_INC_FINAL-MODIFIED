@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Admission Application</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('student.admission.status') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Admission Application</h2>
    <p class="text-gray-600 mt-1">Complete all sections to submit your application.</p>
</div>

@if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">{{ session('error') }}</div>
@endif

@if($pendingAdmission)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Application Already Submitted</h3>
                <p class="text-sm text-gray-600">Application #{{ $pendingAdmission->application_number }} — Status: <span class="font-medium text-yellow-700">Pending</span></p>
            </div>
        </div>
        <a href="{{ route('student.admission.status') }}" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition">View Application Status &rarr;</a>
    </div>
@else
    <form method="POST" action="{{ route('student.admission.store') }}" x-ref="form"
          x-data="admissionForm()">
        @csrf

        {{-- Step Indicator --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 overflow-x-auto">
            <div class="flex gap-1 min-w-max">
                <template x-for="(s, i) in steps" :key="i">
                    <button type="button" @click="goTo(i + 1)"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium transition whitespace-nowrap"
                            :class="step === i + 1 ? 'text-white shadow-sm' : 'text-gray-500 hover:bg-gray-100'"
                            :style="step === i + 1 ? 'background: var(--navy);' : ''">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                              :class="step === i + 1 ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600'">
                            <span x-text="i + 1"></span>
                        </span>
                        <span class="hidden sm:inline" x-text="s"></span>
                        <span class="sm:hidden" x-text="s.replace(/ .*/, '')"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- Step 1: Application Details --}}
        <div data-step="1" x-show="step === 1" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Application Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Application Type *</label>
                    <select name="application_type" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select one...</option>
                        <option value="New" {{ old('application_type') === 'New' ? 'selected' : '' }}>New Student</option>
                        <option value="Transferee" {{ old('application_type') === 'Transferee' ? 'selected' : '' }}>Transferee</option>
                    </select>
                    @error('application_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
                    <select name="grade_level" x-model="selectedGrade" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select grade...</option>
                        <optgroup label="Kindergarten">
                            <option value="Kinder">Kinder</option>
                        </optgroup>
                        <optgroup label="Elementary">
                            @foreach(['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Junior High School">
                            @foreach(['Grade 7','Grade 8','Grade 9','Grade 10'] as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Senior High School">
                            @foreach(['Grade 11','Grade 12'] as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    @error('grade_level') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div x-show="isSHS()" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SHS Strand *</label>
                    <select name="strand" x-bind:required="isSHS()"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select strand...</option>
                        <option value="STEM" {{ old('strand') === 'STEM' ? 'selected' : '' }}>STEM</option>
                        <option value="ABM" {{ old('strand') === 'ABM' ? 'selected' : '' }}>ABM</option>
                        <option value="HUMSS" {{ old('strand') === 'HUMSS' ? 'selected' : '' }}>HUMSS</option>
                        <option value="GAS" {{ old('strand') === 'GAS' ? 'selected' : '' }}>GAS</option>
                    </select>
                    @error('strand') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Year *</label>
                    <select name="school_year" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select year...</option>
                        <option value="2026-2027" {{ old('school_year') === '2026-2027' ? 'selected' : '' }}>2026-2027</option>
                        <option value="2027-2028" {{ old('school_year') === '2027-2028' ? 'selected' : '' }}>2027-2028</option>
                    </select>
                    @error('school_year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="button" @click="next()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Next &rarr;
                </button>
            </div>
        </div>

        {{-- Step 2: Personal Information --}}
        <div data-step="2" x-show="step === 2" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" name="date_of_birth" x-model="dob" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                    <input type="text" readonly disabled x-bind:value="age()"
                           class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Place of Birth *</label>
                    <input type="text" name="place_of_birth" value="{{ old('place_of_birth', $student->place_of_birth) }}" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship *</label>
                    <input type="text" name="citizenship" value="{{ old('citizenship', $student->citizenship) }}" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                    <input type="text" name="religion" value="{{ old('religion', $student->religion) }}" maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LRN (Learner Reference Number)</label>
                    <input type="text" name="legacy_lrn" value="{{ old('legacy_lrn', $student->legacy_lrn) }}" maxlength="20" placeholder="12-digit LRN"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input type="text" name="contact_number" value="{{ old('contact_number', $student->contact_number) }}" required maxlength="20" placeholder="09XX-XXX-XXXX"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="prev()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">&larr; Previous</button>
                <button type="button" @click="next()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Next &rarr;
                </button>
            </div>
        </div>

        {{-- Step 3: Address --}}
        <div data-step="3" x-show="step === 3" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Address</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Permanent Address *</label>
                    <textarea name="permanent_address" rows="2" required maxlength="500"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('permanent_address', $student->permanent_address) }}</textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="same_as_permanent" x-model="samePerm" value="1">
                    Same as permanent address
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Address</label>
                    <textarea name="current_address" rows="2" maxlength="500" x-bind:disabled="samePerm"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    >{{ old('current_address', $student->current_address) }}</textarea>
                </div>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="prev()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">&larr; Previous</button>
                <button type="button" @click="next()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Next &rarr;
                </button>
            </div>
        </div>

        {{-- Step 4: Family Background --}}
        <div data-step="4" x-show="step === 4" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Family Background</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name</label>
                    <input type="text" name="father_name" value="{{ old('father_name', $student->father_name) }}" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Father's Occupation</label>
                    <input type="text" name="father_occupation" value="{{ old('father_occupation', $student->father_occupation) }}" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                    <input type="text" name="mother_name" value="{{ old('mother_name', $student->mother_name) }}" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Occupation</label>
                    <input type="text" name="mother_occupation" value="{{ old('mother_occupation', $student->mother_occupation) }}" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Name (if not parent)</label>
                    <input type="text" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Contact</label>
                    <input type="text" name="guardian_contact" value="{{ old('guardian_contact', $student->guardian_contact) }}" maxlength="20"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="prev()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">&larr; Previous</button>
                <button type="button" @click="next()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Next &rarr;
                </button>
            </div>
        </div>

        {{-- Step 5: Emergency Contact --}}
        <div data-step="5" x-show="step === 5" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Emergency Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $student->emergency_contact_name) }}" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <input type="text" name="emergency_contact_number" value="{{ old('emergency_contact_number', $student->emergency_contact_number) }}" required maxlength="20"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
                    <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $student->emergency_contact_relationship) }}" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="prev()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">&larr; Previous</button>
                <button type="button" @click="next()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Next &rarr;
                </button>
            </div>
        </div>

        {{-- Step 6: Previous School --}}
        <div data-step="6" x-show="step === 6" x-cloak
             class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
            <h3 class="font-semibold text-gray-900 mb-4">Previous School</h3>
            <p class="text-sm text-gray-500 mb-4">If you are a new student or transferee, please provide your previous school details.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                    <input type="text" name="previous_school" value="{{ old('previous_school', $student->previous_school) }}" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Address</label>
                    <input type="text" name="previous_school_address" value="{{ old('previous_school_address', $student->previous_school_address) }}" maxlength="500"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="prev()"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition">&larr; Previous</button>
                <button type="button" @click="submitAll()"
                        class="px-6 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    Submit Application
                </button>
            </div>
        </div>
    </form>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('admissionForm', () => ({
            step: 1,
            dob: '{{ $student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('Y-m-d') : '' }}',
            samePerm: false,
            selectedGrade: '{{ old('grade_level') }}',
            steps: [
                'Application Details', 'Personal Information', 'Address',
                'Family Background', 'Emergency Contact', 'Previous School'
            ],
            age() {
                if (!this.dob) return '';
                const b = new Date(this.dob), t = new Date();
                let a = t.getFullYear() - b.getFullYear();
                const m = t.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && t.getDate() < b.getDate())) a--;
                return a;
            },
            isSHS() {
                return this.selectedGrade === 'Grade 11' || this.selectedGrade === 'Grade 12';
            },
            goTo(n) {
                if (n < this.step) { this.step = n; return; }
                const box = this.$el.querySelector('[data-step="' + this.step + '"]');
                if (!box) { this.step = n; return; }
                const reqs = box.querySelectorAll('[required]');
                let ok = true;
                reqs.forEach(f => {
                    f.classList.remove('border-red-500', 'bg-red-50');
                    if (!f.value.trim()) {
                        f.classList.add('border-red-500', 'bg-red-50');
                        ok = false;
                    }
                });
                if (ok) this.step = n;
            },
            next() { this.goTo(this.step + 1); },
            prev() { if (this.step > 1) this.step--; },
            submitAll() {
                const last = this.$el.querySelector('[data-step="6"]');
                const reqs = last.querySelectorAll('[required]');
                let ok = true;
                reqs.forEach(f => {
                    f.classList.remove('border-red-500', 'bg-red-50');
                    if (!f.value.trim()) {
                        f.classList.add('border-red-500', 'bg-red-50');
                        ok = false;
                    }
                });
                if (ok) this.$refs.form.submit();
            }
        }));
    });
    </script>
@endif
@endsection
