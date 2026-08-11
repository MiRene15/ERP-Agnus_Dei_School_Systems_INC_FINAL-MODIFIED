@extends('PromotionalWebsite.layout')

@section('content')
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Account Portal</h1>
        <p class="page-subtitle">Log in with your institutional credentials to access your dashboard.</p>
    </div>
</div>

<div class="container" style="max-width: 480px; margin-bottom: 100px;">
    <div class="card">

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Email Address</label>
                <input type="email" name="email" id="email" required autofocus autocomplete="username" value="{{ old('email') }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="password" required autocomplete="current-password"
                           style="width: 100%; padding: 12px 15px; padding-right: 45px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                    <button type="button" id="togglePassword" aria-label="Show password"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; color: var(--text-muted); font-size: 1.1rem; line-height: 1;">
                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                <div style="display: flex; align-items: center; margin-top: 8px;">
                    <input type="checkbox" id="showPassword" style="width: 16px; height: 16px; accent-color: var(--primary-navy); margin-right: 8px;">
                    <label for="showPassword" style="font-size: 0.9rem; color: var(--text-muted); cursor: pointer;">Show password</label>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div style="display: flex; align-items: center; margin-bottom: 24px;">
                <input type="checkbox" name="remember" id="remember_me" style="width: 16px; height: 16px; accent-color: var(--primary-navy); margin-right: 8px;">
                <label for="remember_me" style="font-size: 0.9rem; color: var(--text-muted); cursor: pointer;">Remember me</label>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between;">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" style="font-size: 0.85rem; color: var(--primary-navy); text-decoration: underline; text-underline-offset: 3px;">
                        Forgot your password?
                    </a>
                @endif
                <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Log In</button>
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
    input[type="checkbox"]:focus {
        outline: 2px solid var(--lilac-glow);
        outline-offset: 2px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const showCheckbox = document.getElementById('showPassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    function syncEye() {
        if (showCheckbox.checked) {
            eyeOpen.style.display = 'none';
            eyeClosed.style.display = '';
        } else {
            eyeOpen.style.display = '';
            eyeClosed.style.display = 'none';
        }
    }

    showCheckbox.addEventListener('change', function () {
        passwordInput.type = showCheckbox.checked ? 'text' : 'password';
        syncEye();
    });

    document.getElementById('togglePassword').addEventListener('click', function () {
        showCheckbox.checked = !showCheckbox.checked;
        passwordInput.type = showCheckbox.checked ? 'text' : 'password';
        syncEye();
    });

    syncEye();
});
</script>
@endsection
