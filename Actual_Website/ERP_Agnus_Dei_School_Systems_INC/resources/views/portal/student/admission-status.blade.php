@extends('portal.layouts.app')

@section('breadcrumbs')
    <a href="{{ route('student.dashboard') }}" class="no-underline" style="color: var(--muted);">Dashboard</a>
    <span class="opacity-40">/</span>
    <span class="current">Application Status</span>
@endsection

@section('sidebar-links')
    <a href="{{ route('student.admission.create') }}" class="sidebar-link">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="sidebar-label">Application Status</span>
    </a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Application Status</h2>
    <p class="text-gray-600 mt-1">Track your admission application and upload required documents.</p>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">{{ session('success') }}</div>
@endif

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
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Upload Requirements</h3>
                <form method="POST" action="{{ route('student.admission.requirements') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        <select name="document_type" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <option value="">Select document...</option>
                            <option value="PSA Birth Certificate">PSA Birth Certificate</option>
                            <option value="Form 138 (Report Card)">Form 138 (Report Card)</option>
                            <option value="Good Moral Certificate">Good Moral Certificate</option>
                            <option value="ESC Grant Certificate">ESC Grant Certificate</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">File (PDF, JPG, PNG — max 5MB)</label>
                        <input type="file" name="file" required accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition" style="background: var(--navy);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Upload</button>
                </form>
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
                        <a href="{{ asset('storage/' . $req->file_path) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View</a>
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
@endsection
