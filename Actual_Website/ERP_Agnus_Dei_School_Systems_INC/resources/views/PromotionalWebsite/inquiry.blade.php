@extends('PromotionalWebsite.layout')

@section('content')
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Admission Inquiry</h1>
        <p class="page-subtitle">Submit your details to start the enrollment process and receive your Agnus Dei institutional email address.</p>
    </div>
</div>

<div class="container" style="max-width: 600px; margin-bottom: 100px;">
    <div class="card glass-effect">
        @if(session('success'))
            <div style="background: rgba(46, 204, 113, 0.1); color: #27ae60; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid rgba(46, 204, 113, 0.3);">
                {{ session('success') }}
            </div>
        @endif

        <form action="/inquiry" method="POST">
            @csrf
            
            <div style="margin-bottom: 20px;">
                <label for="first_name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">First Name</label>
                <input type="text" name="first_name" id="first_name" required value="{{ old('first_name') }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                @error('first_name')
                    <span style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="last_name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Last Name</label>
                <input type="text" name="last_name" id="last_name" required value="{{ old('last_name') }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                @error('last_name')
                    <span style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 25px;">
                <label for="personal_email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Personal Email Address</label>
                <input type="email" name="personal_email" id="personal_email" required value="{{ old('personal_email') }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                <small style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-top: 5px;">Your new institutional credentials will be sent here.</small>
                @error('personal_email')
                    <span style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; text-align: center; border: none; cursor: pointer;">Generate Credentials & Inquire</button>
        </form>
    </div>
</div>

<style>
    input:focus {
        outline: none;
        border-color: var(--lilac-glow) !important;
        box-shadow: 0 0 0 3px rgba(163, 159, 233, 0.2);
    }
</style>
@endsection
