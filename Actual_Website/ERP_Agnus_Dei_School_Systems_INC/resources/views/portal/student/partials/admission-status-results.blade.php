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
                                    <span class="text-yellow-700">Pending Review</span>
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

            @if($admission->status === 'Pending')
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                 x-data="{ docs: @js($requirements->pluck('document_type')) }">
                <h3 class="font-semibold text-gray-900 mb-4">Upload Requirements</h3>

                <form method="POST" action="{{ route('student.admission.requirements') }}" enctype="multipart/form-data" id="upload-form">
                    @csrf
                    @php
                        $requiredTypes = ['PSA Birth Certificate', 'Form 138 (Report Card)', 'Good Moral Certificate'];
                        $optionalTypes = ['ESC Grant Certificate', 'Other'];
                    @endphp
                    <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span><strong>Max file size: 5MB per file.</strong> Accepted formats: PDF, JPG, JPEG, PNG. Large photos should be compressed before uploading.</span>
                    </div>
                    <div class="space-y-3">
                        @foreach($requiredTypes as $dt)
                        <div class="flex items-center gap-3 p-3 rounded-lg border transition"
                             :class="docs.includes('{{ $dt }}') ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white'">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center transition"
                                 :class="docs.includes('{{ $dt }}') ? 'bg-green-500' : 'bg-gray-200'">
                                <svg x-show="docs.includes('{{ $dt }}')" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <label class="text-sm font-medium text-gray-700 min-w-[180px]">{{ $dt }}</label>
                            <input type="file" name="documents[{{ $dt }}]" accept=".pdf,.jpg,.jpeg,.png"
                                   class="flex-1 text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   @change="if($event.target.files.length) { if(!docs.includes('{{ $dt }}')) docs.push('{{ $dt }}') } else { docs = docs.filter(d => d !== '{{ $dt }}') }">
                            @error('documents.' . $dt) <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        @endforeach

                        <hr class="border-gray-200 my-2">

                        <p class="text-xs text-gray-400 font-medium">Optional Documents</p>
                        @foreach($optionalTypes as $dt)
                        <div class="flex items-center gap-3 p-3 rounded-lg border transition"
                             :class="docs.includes('{{ $dt }}') ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white'">
                            <div class="w-5 h-5 rounded-full flex items-center justify-center transition"
                                 :class="docs.includes('{{ $dt }}') ? 'bg-green-500' : 'bg-gray-200'">
                                <svg x-show="docs.includes('{{ $dt }}')" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <label class="text-sm font-medium text-gray-700 min-w-[180px]">{{ $dt }} <span class="text-gray-400 font-normal">(optional)</span></label>
                            <input type="file" name="documents[{{ $dt }}]" accept=".pdf,.jpg,.jpeg,.png"
                                   class="flex-1 text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   @change="if($event.target.files.length) { if(!docs.includes('{{ $dt }}')) docs.push('{{ $dt }}') } else { docs = docs.filter(d => d !== '{{ $dt }}') }">
                            @error('documents.' . $dt) <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-6 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Accepted: PDF, JPG, PNG — max 5MB each</p>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Upload Selected</button>
                            <button type="button"
                                    onclick="{{ $allRequiredUploaded ? "document.getElementById('proceed-timer-modal').classList.remove('hidden'); startProceedTimer();" : '' }}"
                                    class="px-5 py-2 rounded-lg text-sm font-semibold transition"
                                    style="background: {{ $allRequiredUploaded ? 'var(--navy)' : '#e5e7eb' }}; color: {{ $allRequiredUploaded ? 'white' : '#9ca3af' }}; cursor: {{ $allRequiredUploaded ? 'pointer' : 'default' }};"
                                    onmouseover="this.style.opacity='{{ $allRequiredUploaded ? '0.9' : '1' }}'"
                                    onmouseout="this.style.opacity='1'">Proceed</button>
                        </div>
                    </div>
                </form>
                <script>
                document.getElementById('upload-form').addEventListener('submit', function(e) {
                    var files = this.querySelectorAll('input[type="file"]');
                    var maxSize = 5 * 1024 * 1024;
                    for (var i = 0; i < files.length; i++) {
                        var input = files[i];
                        if (input.files.length > 0) {
                            var file = input.files[0];
                            if (file.size > maxSize) {
                                e.preventDefault();
                                var mb = (file.size / 1024 / 1024).toFixed(1);
                                alert('File too large: ' + file.name + ' (' + mb + 'MB). Maximum allowed is 5MB. Please compress or resize the image and try again.');
                                return;
                            }
                        }
                    }
                });
                document.querySelectorAll('#upload-form input[type="file"]').forEach(function(input) {
                    input.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            var file = this.files[0];
                            var maxSize = 5 * 1024 * 1024;
                            if (file.size > maxSize) {
                                var mb = (file.size / 1024 / 1024).toFixed(1);
                                alert('Warning: ' + file.name + ' is ' + mb + 'MB. Maximum allowed is 5MB. Please choose a smaller file.');
                                this.value = '';
                            }
                        }
                    });
                });
                </script>

                {{-- Timer Proceed Modal --}}
                <div id="proceed-timer-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40" style="transition: opacity 0.3s ease;">
                    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md mx-4 w-full text-center relative overflow-hidden">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #ede9fe;">
                            <svg class="w-8 h-8" style="color: #7c3aed;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Proceed to School</h3>
                        <p class="text-sm text-gray-600 leading-relaxed mb-4">
                            Please proceed to the school's Registrar Office on-site to complete your payment and finalize your full enrollment. Bring all original copies of your requirements for verification.
                        </p>
                        <p class="text-xs text-gray-400">Redirecting to dashboard in <span id="proceed-timer-count" class="font-bold text-gray-700">5</span> seconds...</p>
                        <div class="mt-3 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div id="proceed-timer-bar" class="h-full rounded-full" style="background: var(--navy); width: 100%; animation: proceedShrink 5s linear forwards;"></div>
                        </div>
                    </div>
                </div>
                <style>
                    @keyframes proceedShrink { from { transform: scaleX(1); } to { transform: scaleX(0); } }
                    #proceed-timer-bar { transform-origin: left; }
                </style>
                <script>
                function startProceedTimer() {
                    var count = 5, el = document.getElementById('proceed-timer-count');
                    var interval = setInterval(function() {
                        count--;
                        el.textContent = count;
                        if (count <= 0) {
                            clearInterval(interval);
                            var modal = document.getElementById('proceed-timer-modal');
                            modal.style.transition = 'opacity 0.5s ease';
                            modal.style.opacity = '0';
                            setTimeout(function() { window.location.href = '{{ route("student.dashboard") }}'; }, 500);
                        }
                    }, 1000);
                }
                </script>
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
                    @if($admission->status === 'Pending')
                    <li class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-yellow-400 mt-1.5 flex-shrink-0"></div>
                        <div>
                            <p class="font-medium text-gray-900">Under Review</p>
                            <p class="text-gray-500 text-xs">Awaiting registrar verification</p>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endif
