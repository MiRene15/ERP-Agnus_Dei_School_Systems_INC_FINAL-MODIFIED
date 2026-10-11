<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Application Review</h2>
    <p class="text-gray-600 mt-1">{{ $admission->application_number }} &middot; {{ $admission->application_type }} Student &middot; {{ $admission->school_year }}</p>
</div>

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
                    <dt class="text-gray-500">Student's Email</dt>
                    <dd class="font-medium text-gray-900">{{ $admission->student->personal_email ?? 'N/A' }}</dd>
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
                    <dd class="font-medium text-gray-900">{{ $admission->student->gender ?? 'Missing — set before forwarding' }}</dd>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="requirementsChecklist(@js($admission->requirements->map(fn($r) => ['id' => $r->id, 'document_type' => $r->document_type, 'status' => $r->status])))">
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
                    @case('Awaiting Downpayment')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">Awaiting Downpayment</span>
                        @break
                    @case('Returned')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Returned</span>
                        @break
                    @case('Approved By Registrar')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Approved</span>
                        @break
                    @case('Rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Rejected</span>
                        @break
                @endswitch
            </div>

            @if($admission->status === 'Pending' && empty($admission->downpayment_receipt))
            <form method="POST" action="{{ route('registrar.admissions.forward', $admission) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note for the record (optional)</label>
                    <textarea name="note" rows="2" maxlength="1000" placeholder="e.g. Complete file — ready for downpayment."
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('note') }}</textarea>
                    @error('note') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <p class="text-xs text-gray-500">Forward unlocks once every document above is verified and gender is set.</p>
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                    Forward to Cashier
                </button>
            </form>

            <div class="relative py-2 my-2">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center"><span class="bg-white px-2 text-xs text-gray-400">or</span></div>
            </div>

            <form method="POST" action="{{ route('registrar.admissions.return', $admission) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note for the family (optional)</label>
                    <p class="text-xs text-gray-500 mb-1">The family is told which documents are missing automatically — add anything else they should know (e.g. "student email needs fix").</p>
                    <textarea name="comment" rows="2" maxlength="1000" placeholder="Optional comment"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">{{ old('comment') }}</textarea>
                    @error('comment') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 transition">
                    Return with Note
                </button>
            </form>

            <form method="POST" action="{{ route('registrar.admissions.reject', $admission) }}"
                   onsubmit="return confirm('Reject this application? This cannot be undone.')">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition mt-2">
                    Reject Application
                </button>
            </form>
            @endif

            @if($admission->status === 'Pending' && !empty($admission->downpayment_receipt))
            <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                <strong>Downpayment confirmed — {{ $admission->downpayment_receipt }}</strong>
                @if($admission->downpayment_paid_at)
                <span class="text-xs"> on {{ \Carbon\Carbon::parse($admission->downpayment_paid_at)->format('M d, Y h:i A') }}</span>
                @endif
                <p class="text-xs mt-1">Assign a section and subjects below, then grant full approval. The enrollment and school account email are created in that step.</p>
            </div>

            <form method="POST" action="{{ route('registrar.admissions.approve', $admission) }}"
                  onsubmit="return confirm('Grant full approval? This creates the enrollment and the school account email.')">
                @csrf
                <div class="space-y-3 mt-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                        <select name="section_id" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Select section…</option>
                            @foreach(($sections ?? collect()) as $section)
                                <option value="{{ $section->id }}">{{ $section->section_name }} ({{ $section->grade_level }})</option>
                            @endforeach
                        </select>
                        @error('section_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subjects *</label>
                        <p class="text-xs text-gray-500 mb-2">Tick every subject for the chosen section above.</p>
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                            @forelse(($subjectClasses ?? collect())->groupBy('section') as $classSection => $classes)
                                <p class="text-xs font-semibold text-gray-500">Section {{ $classSection ?: 'Unassigned' }}</p>
                                @foreach($classes as $class)
                                <label class="flex items-start gap-2 text-sm cursor-pointer">
                                    <input type="checkbox" name="subject_ids[]" value="{{ $class->id }}" class="mt-1"
                                        @disabled($class->capacity && ($class->active_enrollments_count ?? 0) >= $class->capacity)>
                                    <span class="text-gray-700">{{ $class->subject->name ?? 'Subject #' . $class->id }}
                                        @if($class->capacity)
                                            <span class="text-xs {{ ($class->active_enrollments_count ?? 0) >= $class->capacity ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                                                ({{ max(0, $class->capacity - ($class->active_enrollments_count ?? 0)) }} seats left)
                                            </span>
                                        @endif
                                    </span>
                                </label>
                                @endforeach
                            @empty
                                <p class="text-xs text-gray-500">No active classes found for {{ $admission->grade_level }} ({{ $admission->school_year }}). Ask the Principal to set up classes first.</p>
                            @endforelse
                        </div>
                        @error('subject_ids') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700 transition">
                        Grant Full Approval
                    </button>
                </div>
            </form>
            @endif

            @if($admission->status === 'Awaiting Downpayment')
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                Forwarded to Cashier — the family sees Awaiting Downpayment. Downpayment confirmation and full approval happen next.
            </div>
            @if($admission->review_note)
            <p class="text-xs text-gray-500 mt-2">Note: {{ $admission->review_note }}</p>
            @endif
            @endif

            @if($admission->status === 'Returned')
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                <strong>Returned to family:</strong> {{ $admission->review_note }}
                <p class="text-xs mt-1">When the family uploads the missing document(s), the file returns to review automatically. Already-uploaded files are kept.</p>
            </div>
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
                @if($admission->status === 'Awaiting Downpayment' && $admission->updated_at)
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-gray-900">Forwarded to Cashier</p>
                        <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @endif
                @if($admission->status === 'Returned' && $admission->updated_at)
                <li class="flex items-start gap-2">
                    <div class="w-2 h-2 rounded-full bg-red-500 mt-1.5 flex-shrink-0"></div>
                    <div>
                        <p class="font-medium text-red-700">Returned to family</p>
                        <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </li>
                @endif
                @if($admission->status !== 'Pending' && !in_array($admission->status, ['Awaiting Downpayment', 'Returned']) && $admission->updated_at)
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
