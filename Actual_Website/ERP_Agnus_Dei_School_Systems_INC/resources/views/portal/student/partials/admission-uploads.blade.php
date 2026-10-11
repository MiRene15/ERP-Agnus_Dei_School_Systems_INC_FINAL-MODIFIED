{{-- Shared upload-requirements checklist.
     Used as Step 7 of the apply page (uploads attach to the draft via AJAX,
     no page reload) and on the status page (in-flight Pending files +
     Returned files being completed). Computes its own uploaded-state from
     $admission (empty when no draft exists yet — the endpoint creates one). --}}
{{-- No x-data of its own: the host scope provides docs / uploadMsg /
     uploadErr / uploading (apply page: admissionForm; status page: the
     wrapper below). One scope only — never a nested component mid-page. --}}
<div id="upload-requirements" data-upload-block
     class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4 scroll-mt-6 transition-shadow"
     :data-uploaded="JSON.stringify(docs)">
    <h3 class="font-semibold text-gray-900 mb-4">Upload Requirements</h3>

    @if(!$admission)
    <p class="text-xs text-gray-500 mb-4">Your details save automatically as you go — choose your documents below and upload; they attach to your draft.</p>
    @endif

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
        <p x-show="uploadMsg" x-text="uploadMsg" class="text-green-600 text-xs mt-3"></p>
        <p x-show="uploadErr" x-text="uploadErr" class="text-red-500 text-xs mt-3"></p>
        <div class="mt-6 flex items-center justify-between">
            <p class="text-xs text-gray-400">Accepted: PDF, JPG, PNG — max 5MB each</p>
            <button type="submit" :disabled="uploading"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition disabled:opacity-60"
                    style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                <span x-text="uploading ? 'Uploading…' : 'Upload Selected'">Upload Selected</span>
            </button>
        </div>
    </form>
    <script>
    (function () {
        var form = document.getElementById('upload-form');
        if (!form || form.dataset.ajaxBound) return;
        form.dataset.ajaxBound = '1';
        var maxSize = 5 * 1024 * 1024;

        function tooBig(input) {
            if (input.files.length > 0 && input.files[0].size > maxSize) {
                var mb = (input.files[0].size / 1024 / 1024).toFixed(1);
                alert('Warning: ' + input.files[0].name + ' is ' + mb + 'MB. Maximum allowed is 5MB. Please choose a smaller file.');
                input.value = '';
                return true;
            }
            return false;
        }
        form.querySelectorAll('input[type="file"]').forEach(function (input) {
            input.addEventListener('change', function () { tooBig(input); });
        });

        form.addEventListener('submit', function (e) {
            var scope = form.closest('[data-upload-block]');
            var setMsg = function (ok, text) {
                if (!scope || !window.Alpine) return;
                try {
                    var data = window.Alpine.$data(scope);
                    data.uploadMsg = ok ? text : '';
                    data.uploadErr = ok ? '' : text;
                } catch (err) { /* noop: inline message is best-effort */ }
            };
            var files = form.querySelectorAll('input[type="file"]');
            var chosen = 0;
            for (var i = 0; i < files.length; i++) {
                if (files[i].files.length > 0) {
                    chosen++;
                    if (files[i].files[0].size > maxSize) {
                        e.preventDefault();
                        var mb = (files[i].files[0].size / 1024 / 1024).toFixed(1);
                        setMsg(false, 'File too large: ' + files[i].files[0].name + ' (' + mb + 'MB). Maximum allowed is 5MB.');
                        return;
                    }
                }
            }
            if (chosen === 0) {
                e.preventDefault();
                setMsg(false, 'Choose at least one document first, then upload.');
                return;
            }
            // No Alpine on this page (e.g. plain fallback) — normal submit.
            if (!window.Alpine || !window.fetch) return;
            e.preventDefault();
            var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (window.Alpine && scope) { try { var d = window.Alpine.$data(scope); d.uploading = true; d.uploadMsg = ''; d.uploadErr = ''; } catch (err) {} }
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            }).then(function (r) {
                return r.json().then(function (body) { return { status: r.status, body: body }; });
            }).then(function (res) {
                if (window.Alpine && scope) { try { window.Alpine.$data(scope).uploading = false; } catch (err) {} }
                if (res.status === 200 && res.body.success) {
                    if (window.Alpine && scope) {
                        try {
                            var data = window.Alpine.$data(scope);
                            if (Array.isArray(res.body.uploaded)) data.docs = res.body.uploaded;
                            data.uploadMsg = res.body.message || 'Uploaded successfully.';
                            data.uploadErr = '';
                        } catch (err) {}
                    }
                    form.querySelectorAll('input[type="file"]').forEach(function (input) { input.value = ''; });
                } else {
                    var msg = (res.body && res.body.message) || 'Upload failed. Please try again.';
                    if (res.body && res.body.errors) {
                        var first = Object.values(res.body.errors)[0];
                        if (Array.isArray(first) && first[0]) msg = first[0];
                    }
                    setMsg(false, msg);
                }
            }).catch(function () {
                if (window.Alpine && scope) { try { window.Alpine.$data(scope).uploading = false; } catch (err) {} }
                setMsg(false, 'Upload failed — check your connection and try again. Your details are still saved.');
            });
        });
    })();
    </script>
</div>
