@extends('PromotionalWebsite.layout')

@section('content')
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Set New Password</h1>
        <p class="page-subtitle">Choose a strong new password for your institutional account.</p>
    </div>
</div>

<div class="container" style="max-width: 480px; margin-bottom: 100px;">
    <div class="card">
        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Institutional Email</label>
                <input type="email" name="email" id="email" required readonly value="{{ old('email', $request->email) }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition); background: #f8f9fa;">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">New Password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div style="margin-bottom: 24px;">
                <label for="password_confirmation" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end;">
                <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Reset Password</button>
            </div>
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
