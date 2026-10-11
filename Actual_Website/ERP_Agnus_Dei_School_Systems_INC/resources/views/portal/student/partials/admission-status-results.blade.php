<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Application Status</h2>
    <p class="text-gray-600 mt-1">Track your admission application and upload required documents.</p>
</div>

@if(!$admission)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">No Application Yet</h3>
                <p class="text-sm text-gray-600">You haven't submitted an admission application.</p>
            </div>
        </div>
        <a href="{{ route('student.admission.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Apply Now</a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Application Details</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Application No.</dt>
                        <dd class="font-medium text-gray-900">{{ $admission->application_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Type</dt>
                        <dd class="font-medium text-gray-900">{{ $admission->application_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">School Year</dt>
                        <dd class="font-medium text-gray-900">{{ $admission->school_year }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-medium">
                            @switch($admission->status)
                                @case('Pending')
                                    @if(!empty($admission->downpayment_receipt))
                                        <span class="text-green-700">Downpayment received — awaiting full approval</span>
                                    @else
                                        <span class="text-yellow-700">Under Review</span>
                                    @endif
                                    @break
                                @case('Awaiting Downpayment')
                                    <span class="text-blue-700">Awaiting Downpayment — pay ₱1.5k at Cashier to continue</span>
                                    @break
                                @case('Returned')
                                    <span class="text-red-700">Returned — action needed</span>
                                    @break
                                @case('Approved By Registrar')
                                    <span class="text-green-700">Approved</span>
                                    @break
                                @case('Rejected')
                                    <span class="text-red-700">Rejected</span>
                                    @break
                                @default
                                    <span>{{ $admission->status }}</span>
                            @endswitch
                        </dd>
                    </div>
                </dl>
            </div>

            @if($admission->status === 'Pending' && !empty($admission->downpayment_receipt))
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Downpayment received ({{ $admission->downpayment_receipt }}).</strong> Your file is with the Registrar for section assignment and full approval — no further action needed.</span>
            </div>
            @endif

            @if($admission->status === 'Approved By Registrar')
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Account Ready.</strong> Sign in with your school account email{{ $admission->student?->user?->email ? ' (' . $admission->student->user->email . ')' : '' }} — the notice was sent to the student's email on file.</span>
            </div>
            @endif

            @if($admission->status === 'Returned' && $admission->review_note)
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm flex items-start gap-2">
                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>{{ $admission->review_note }}</strong> — upload the missing document(s) below and your file goes back to review. Your other files are kept.</span>
            </div>
            @endif

            @if(in_array($admission->status, ['Pending', 'Returned']))
                <div x-data="{ docs: @js($admission->requirements->pluck('document_type')->all()), uploadMsg: '', uploadErr: '', uploading: false }">
                    @include('portal.student.partials.admission-uploads', ['admission' => $admission])
                </div>
            @endif

            @if($requirements->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Uploaded Documents</h3>
                <ul class="divide-y divide-gray-100">
                    @foreach($requirements as $req)
                    <li class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $req->document_type }}</p>
                                <p class="text-xs text-gray-500">{{ $req->status }}</p>
                            </div>
                        </div>
                        <a href="{{ route('student.admission.requirements.view', $req->id) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Timeline</h3>
                <ul class="space-y-3 text-sm">
                    @if($admission->created_at)
                    <li class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-gray-900">Application Submitted</p>
                            <p class="text-gray-500 text-xs">{{ $admission->created_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </li>
                    @endif
                    @if($admission->status === 'Pending')
                    <li class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-yellow-400 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-gray-900">Under Review</p>
                            <p class="text-gray-500 text-xs">Awaiting registrar verification</p>
                        </div>
                    </li>
                    @endif
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
                            <p class="font-medium text-red-700">Returned — documents needed</p>
                            <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </li>
                    @endif
                    @if($admission->status === 'Approved By Registrar' && $admission->updated_at)
                    <li class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-gray-900">Approved</p>
                            <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </li>
                    @endif
                    @if($admission->status === 'Rejected')
                    <li class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-red-500 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-red-700">Rejected</p>
                            <p class="text-gray-500 text-xs">{{ $admission->updated_at->format('M d, Y h:i A') }}</p>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endif
