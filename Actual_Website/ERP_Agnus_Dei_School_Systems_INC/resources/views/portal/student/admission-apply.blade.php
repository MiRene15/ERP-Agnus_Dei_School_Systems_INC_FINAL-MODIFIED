@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Admission Application</span>
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
@elseif($draftAdmission)
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm flex items-center justify-between">
        <span>You have a saved draft from a previous session. Resume where you left off.</span>
        <form method="POST" action="{{ route('student.admission.discard') }}" class="inline">
            @csrf
            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800 ml-3"
                    onclick="return confirm('Discard your saved draft and start fresh?')">Discard Draft</button>
        </form>
    </div>
@endif

@if(!$pendingAdmission)
    <form method="POST" action="{{ route('student.admission.store') }}" x-ref="form"
          x-data="admissionForm()"
          x-init="initForm()">
        @csrf

        {{-- Step Indicator --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 overflow-x-auto">
            <div class="flex gap-1 min-w-max">
                <template x-for="(s, i) in steps" :key="i">
                    <button type="button" @click="goTo(i + 1)"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium transition whitespace-nowrap"
                            :class="step === i + 1 ? 'text-white shadow-sm' : (isStepComplete(i + 1) ? 'text-green-700 bg-green-50' : 'text-gray-500 hover:bg-gray-100')"
                            :style="step === i + 1 ? 'background: var(--navy);' : ''">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                              :class="step === i + 1 ? 'bg-white/20 text-white' : (isStepComplete(i + 1) ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600')">
                            <template x-if="isStepComplete(i + 1) && step !== i + 1">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 010 1.06l-7.25 7.25a.75.75 0 01-1.06 0L2.22 9.28a.75.75 0 011.06-1.06L6 10.94l6.72-6.72a.75.75 0 011.06 0z"/></svg>
                            </template>
                            <template x-if="!isStepComplete(i + 1) || step === i + 1">
                                <span x-text="i + 1"></span>
                            </template>
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
                    <select name="application_type" required x-model="f.application_type"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select one...</option>
                        <option value="New">New Student</option>
                        <option value="Transferee">Transferee</option>
                    </select>
                    @error('application_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level *</label>
                    <select name="grade_level" x-model="f.grade_level" required
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
                <div x-show="f.grade_level === 'Grade 11' || f.grade_level === 'Grade 12'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SHS Strand *</label>
                    <select name="strand" x-model="f.strand" x-bind:required="f.grade_level === 'Grade 11' || f.grade_level === 'Grade 12'"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select strand...</option>
                        <option value="STEM">STEM</option>
                        <option value="ABM">ABM</option>
                        <option value="HUMSS">HUMSS</option>
                        <option value="GAS">GAS</option>
                    </select>
                    @error('strand') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Year *</label>
                    <select name="school_year" required x-model="f.school_year"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select year...</option>
                        <option value="2026-2027">2026-2027</option>
                        <option value="2027-2028">2027-2028</option>
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
                    <input type="text" name="first_name" x-model="f.first_name" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" name="middle_name" x-model="f.middle_name" maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" x-model="f.last_name" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" name="date_of_birth" x-model="f.date_of_birth" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                    <input type="text" readonly disabled x-bind:value="computedAge"
                           class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Place of Birth *</label>
                    <input type="text" name="place_of_birth" x-model="f.place_of_birth" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship *</label>
                    <input type="text" name="citizenship" x-model="f.citizenship" required maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                    <input type="text" name="religion" x-model="f.religion" maxlength="100"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">LRN (Learner Reference Number)</label>
                    <input type="tel" name="legacy_lrn" x-model="f.legacy_lrn" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" placeholder="123456789012"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 12)"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-sm text-gray-600 font-medium">+63</span>
                        <input type="tel" name="contact_number" x-model="f.contact_number" required maxlength="11" inputmode="numeric" placeholder="09XX-XXX-XXXX"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)"
                               class="w-full rounded-r-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
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
                    <textarea name="permanent_address" rows="2" required maxlength="500" x-model="f.permanent_address"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="checkbox" name="same_as_permanent" x-model="f.same_as_permanent" value="1">
                    Same as permanent address
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Address</label>
                    <textarea name="current_address" rows="2" maxlength="500" x-model="f.current_address" x-bind:disabled="f.same_as_permanent"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"></textarea>
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
                    <input type="text" name="father_name" x-model="f.father_name" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Father's Occupation</label>
                    <input type="text" name="father_occupation" x-model="f.father_occupation" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name</label>
                    <input type="text" name="mother_name" x-model="f.mother_name" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Occupation</label>
                    <input type="text" name="mother_occupation" x-model="f.mother_occupation" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Name (if not parent)</label>
                    <input type="text" name="guardian_name" x-model="f.guardian_name" maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Guardian's Contact</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-sm text-gray-600 font-medium">+63</span>
                        <input type="tel" name="guardian_contact" x-model="f.guardian_contact" maxlength="11" inputmode="numeric" placeholder="09XX-XXX-XXXX"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)"
                               class="w-full rounded-r-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
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
                    <input type="text" name="emergency_contact_name" x-model="f.emergency_contact_name" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-sm text-gray-600 font-medium">+63</span>
                        <input type="tel" name="emergency_contact_number" x-model="f.emergency_contact_number" required maxlength="11" inputmode="numeric" placeholder="09XX-XXX-XXXX"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)"
                               class="w-full rounded-r-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Relationship *</label>
                    <input type="text" name="emergency_contact_relationship" x-model="f.emergency_contact_relationship" required maxlength="100"
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
                    <input type="text" name="previous_school" x-model="f.previous_school" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Address</label>
                    <input type="text" name="previous_school_address" x-model="f.previous_school_address" maxlength="500"
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
            step: {{ $draftStep ?? 1 }},
            saving: false,
            f: {
                application_type: @js($draftData['application_type'] ?? ''),
                grade_level: @js($draftData['grade_level'] ?? ''),
                strand: @js($draftData['strand'] ?? ''),
                school_year: @js($draftData['school_year'] ?? ''),
                first_name: @js($draftData['first_name'] ?? $student->first_name),
                middle_name: @js($draftData['middle_name'] ?? $student->middle_name),
                last_name: @js($draftData['last_name'] ?? $student->last_name),
                date_of_birth: @js($draftData['date_of_birth'] ?? ($student->date_of_birth ? \Carbon\Carbon::parse($student->date_of_birth)->format('Y-m-d') : '')),
                place_of_birth: @js($draftData['place_of_birth'] ?? $student->place_of_birth),
                citizenship: @js($draftData['citizenship'] ?? $student->citizenship),
                religion: @js($draftData['religion'] ?? $student->religion),
                legacy_lrn: @js($draftData['legacy_lrn'] ?? $student->legacy_lrn),
                contact_number: @js($draftData['contact_number'] ?? $student->contact_number),
                permanent_address: @js($draftData['permanent_address'] ?? $student->permanent_address),
                same_as_permanent: {{ ($draftData['same_as_permanent'] ?? $student->same_as_permanent) ? 'true' : 'false' }},
                current_address: @js($draftData['current_address'] ?? $student->current_address),
                father_name: @js($draftData['father_name'] ?? $student->father_name),
                father_occupation: @js($draftData['father_occupation'] ?? $student->father_occupation),
                mother_name: @js($draftData['mother_name'] ?? $student->mother_name),
                mother_occupation: @js($draftData['mother_occupation'] ?? $student->mother_occupation),
                guardian_name: @js($draftData['guardian_name'] ?? $student->guardian_name),
                guardian_contact: @js($draftData['guardian_contact'] ?? $student->guardian_contact),
                emergency_contact_name: @js($draftData['emergency_contact_name'] ?? $student->emergency_contact_name),
                emergency_contact_number: @js($draftData['emergency_contact_number'] ?? $student->emergency_contact_number),
                emergency_contact_relationship: @js($draftData['emergency_contact_relationship'] ?? $student->emergency_contact_relationship),
                previous_school: @js($draftData['previous_school'] ?? $student->previous_school),
                previous_school_address: @js($draftData['previous_school_address'] ?? $student->previous_school_address),
            },
            steps: [
                'Application Details', 'Personal Information', 'Address',
                'Family Background', 'Emergency Contact', 'Previous School'
            ],

            get computedAge() {
                if (!this.f.date_of_birth) return '';
                const b = new Date(this.f.date_of_birth), t = new Date();
                let a = t.getFullYear() - b.getFullYear();
                const m = t.getMonth() - b.getMonth();
                if (m < 0 || (m === 0 && t.getDate() < b.getDate())) a--;
                return a;
            },

            isStepComplete(n) {
                const requiredByStep = {
                    1: ['application_type', 'grade_level', 'school_year'],
                    2: ['first_name', 'last_name', 'date_of_birth', 'place_of_birth', 'citizenship', 'contact_number'],
                    3: ['permanent_address'],
                    4: [],
                    5: ['emergency_contact_name', 'emergency_contact_number', 'emergency_contact_relationship'],
                    6: ['previous_school'],
                };
                const fields = requiredByStep[n] || [];
                if (fields.length === 0) return true;
                return fields.every(f => this[f] && String(this[f]).trim() !== '');
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
                if (ok) { this.step = n; this.autoSave(); }
            },

            next() { this.goTo(this.step + 1); },

            prev() { if (this.step > 1) { this.step--; this.autoSave(); } },

            autoSave() {
                if (this.saving) return;
                this.saving = true;
                const payload = { _step: this.step, ...this.f };
                payload.same_as_permanent = payload.same_as_permanent ? 1 : 0;
                fetch('{{ route('student.admission.draft') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') },
                    body: JSON.stringify(payload)
                }).then(r => r.json()).then(d => {
                    this.saving = false;
                }).catch(() => { this.saving = false; });
            },

            submitAll() {
                let firstErrorStep = null;
                for (let s = 1; s <= 6; s++) {
                    const box = this.$el.querySelector('[data-step="' + s + '"]');
                    if (!box) continue;
                    const reqs = box.querySelectorAll('[required]');
                    let stepOk = true;
                    reqs.forEach(f => {
                        f.classList.remove('border-red-500', 'bg-red-50');
                        if (!f.value.trim()) {
                            f.classList.add('border-red-500', 'bg-red-50');
                            stepOk = false;
                        }
                    });
                    if (!stepOk && firstErrorStep === null) {
                        firstErrorStep = s;
                    }
                }
                if (firstErrorStep !== null) {
                    this.goTo(firstErrorStep);
                    return;
                }
                this.$refs.form.submit();
            },

            initForm() {
                if (this.step > 1) {
                    this.autoSave();
                }
            }
        }));
    });
    </script>
@endif
@endsection
