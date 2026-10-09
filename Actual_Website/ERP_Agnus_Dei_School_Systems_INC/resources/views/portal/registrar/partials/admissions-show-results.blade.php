<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Application Review</h2>
    <p class="text-gray-600 mt-1">{{ $admission->application_number }} &middot; {{ $admission->application_type }} Student &middot; {{ $admission->school_year }}</p>
</div>

<script type="application/json" id="requirements-data">
{!! json_encode($admission->requirements->map(fn($r) => ['id' => $r->id, 'document_type' => $r->document_type, 'status' => $r->status])) !!}
</script>
<script type="application/json" id="classes-data">
{!! json_encode($classes->map(fn($c) => ['id' => $c->id, 'subject' => $c->subject->name ?? 'N/A', 'teacher' => $c->teacher->name ?? 'No teacher', 'section' => $c->section])) !!}
</script>
<script type="application/json" id="section-map">
{!! json_encode($sections->pluck('section_name', 'id')->toArray()) !!}
</script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Applicant Information</h3>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Full Name</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->first_name }} {{ $admission->student->last_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Grade Level</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->grade_level }}</dd>
                </div>
                @if($admission->strand)
                <div>
                    <dt class="text-gray-500">SHS Elective</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->strand }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500">Personal Email</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->personal_email ?? 'N/A' }}
                        @if($admission->student?->user?->hasVerifiedEmail())
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                        @else
                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Unverified</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500">Institutional Email</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student?->user?->email ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Student No.</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->student_number ?? 'Not yet assigned' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-gray-500">Gender</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->gender ?? 'Missing — set before approving' }}</dd>
                    @if($admission->status === 'Pending')
                    <form method="POST" action="{{ route('registrar.admissions.gender', $admission) }}" class="mt-2 flex flex-wrap items-center gap-2">
                        @csrf
                        <select name="gender" required class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Select…</option>
                            @foreach(['Male','Female','Non-binary','Prefer not to say'] as $g)
                                <option value="{{ $g }}" @selected(($admission->student->gender ?? '') === $g)>{{ $g }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="gender_detail" maxlength="100" placeholder="Describe (optional)" value="{{ $admission->student->gender_detail ?? '' }}"
                               class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">Save Gender</button>
                    </form>
                    @error('gender') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @endif
                </div>
            </dl>
        </div>

        {{-- Requirements Checklist --}}
        @if($admission->requirements->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="requirementsChecklist()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Requirements Checklist</h3>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500" x-text="verifiedCount + ' / ' + totalCount + ' verified'">{{ $admission->requirements->where('status', 'Verified')->count() }} / {{ $admission->requirements->count() }} verified</span>
                    @if($admission->status === 'Pending')
                    <form id="verify-all-form" method="POST" action="{{ route('registrar.admissions.verify-all', $admission) }}">
                        @csrf
                        <button type="button" @click="verifyAll()"
                                :disabled="verifyAllBusy"
                                class="text-xs font-medium px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Verify All
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-3">Click <strong>Verify All</strong> to approve all documents at once, or toggle individually.</p>
            <ul class="divide-y divide-gray-100">
                @foreach($admission->requirements as $req)
                <li class="py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0"
                             style="background: {{ $req->status === 'Verified' ? '#22c55e' : '#e5e7eb' }};"
                             :style="'background: ' + (requirements.find(r => r.id === {{ $req->id }}).status === 'Verified' ? '#22c55e' : '#e5e7eb')">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 style="{{ $req->status === 'Verified' ? '' : 'display: none;' }}"
                                 x-show="requirements.find(r => r.id === {{ $req->id }}).status === 'Verified'">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $req->document_type }}</p>
                            <p class="text-xs {{ $req->status === 'Verified' ? 'text-green-600' : 'text-gray-500' }}"
                               :class="requirements.find(r => r.id === {{ $req->id }}).status === 'Verified' ? 'text-green-600' : 'text-gray-500'"
                               x-text="requirements.find(r => r.id === {{ $req->id }}).status">{{ $req->status }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('registrar.requirements.view', $req->id) }}" target="_blank"
                           class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
                        @if($admission->status === 'Pending')
                        <form id="verify-form-{{ $req->id }}" method="POST"
                              action="{{ route('registrar.admissions.verify-requirement', $req) }}" class="inline">
                            @csrf
                            <input type="hidden" name="verify" value="{{ $req->status === 'Verified' ? '0' : '1' }}">
                            <button type="button" @click="toggleVerify({{ $req->id }})"
                                    class="text-sm font-medium px-3 py-1 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="busy[{{ $req->id }}]"
                                    style="{{ $req->status === 'Verified' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dcfce7; color: #16a34a;' }}"
                                    :style="requirements.find(r => r.id === {{ $req->id }}).status === 'Verified' ? 'background: #fee2e2; color: #dc2626;' : 'background: #dcfce7; color: #16a34a;'"
                                    x-text="requirements.find(r => r.id === {{ $req->id }}).status === 'Verified' ? 'Unverify' : 'Verify'">{{ $req->status === 'Verified' ? 'Unverify' : 'Verify' }}
                            </button>
                        </form>
                        @endif
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm text-gray-500 text-center py-4">No requirements uploaded yet.</p>
        </div>
        @endif
    </div>

    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Status</h3>
            <div class="mb-4">
                @switch($admission->status)
                    @case('Pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">Pending</span>
                        @break
                    @case('Approved By Registrar')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Approved</span>
                        @break
                    @case('Rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Rejected</span>
                        @break
                @endswitch
            </div>

            @if($admission->status === 'Pending')
            <form method="POST" action="{{ route('registrar.admissions.approve', $admission) }}" class="space-y-3" x-data="approveForm()">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assign Section</label>
                    @if($sections->isEmpty())
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">No sections available for {{ $admission->grade_level }}. Please create one first.</div>
                    @else
                    <select name="section_id" required x-model="selectedSection"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">Select section...</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->section_name }}</option>
                        @endforeach
                    </select>
                    @endif
                </div>

                {{-- Subject Assignment --}}
                <div x-show="selectedSection !== ''" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign Subjects</label>
                    <template x-if="filteredClasses.length === 0">
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">No classes found for this section.</div>
                    </template>
                    <div class="space-y-1 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-2">
                        <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 cursor-pointer text-sm font-semibold text-gray-700 border-b border-gray-100 mb-1">
                            <input type="checkbox" :checked="allSelected" @click="toggleAll()"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>Select All</span>
                            <span class="text-xs text-gray-400 ml-auto" x-text="selectedIds.length + ' / ' + filteredClasses.length + ' selected'"></span>
                        </label>
                        <template x-for="cls in filteredClasses" :key="cls.id">
                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                                <input type="checkbox" name="subject_ids[]" :value="cls.id" :checked="isSelected(cls.id)" @change="toggleId(cls.id)"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="font-medium text-gray-700" x-text="cls.subject"></span>
                                <span class="text-xs text-gray-400 ml-auto" x-text="cls.teacher"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">
                    Approve & Enroll
                </button>
            </form>

            <form method="POST" action="{{ route('registrar.admissions.reject', $admission) }}"
                  onsubmit="return confirm('Reject this application? This cannot be undone.')">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition mt-2">
                    Reject Application
                </button>
            </form>
            @endif
            @if($admission->status === 'Approved By Registrar')
            <form method="POST" action="{{ route('registrar.admissions.resend-approval-email', $admission) }}" class="mt-2">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                    Resend Confirmation Email
                </button>
            </form>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Timeline</h3>
            <ul class="space-y-3 text-sm">
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-gray-900">Submitted</p>
                        <p class="text-gray-500 text-xs">{{ $admission->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @if($admission->status !== 'Pending')
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full {{ $admission->status === 'Approved By Registrar' ? 'bg-green-500' : 'bg-red-500' }} mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $admission->status === 'Approved By Registrar' ? 'Approved' : 'Rejected' }}</p>
                        <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>
