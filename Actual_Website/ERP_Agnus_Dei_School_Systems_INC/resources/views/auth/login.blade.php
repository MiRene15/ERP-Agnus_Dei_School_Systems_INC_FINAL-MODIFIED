@extends('PromotionalWebsite.layout')

@section('content')
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Account Portal</h1>
        <p class="page-subtitle">Log in with your institutional credentials to access your dashboard.</p>
    </div>
</div>

<div x-data>
    <style>@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}</style>

    <div class="container" style="max-width: 480px; margin-bottom: 100px;">
    <div class="card">

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" id="loginForm">
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
                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
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
                <button type="submit" id="loginBtn" class="btn-primary" style="border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    Log In
                </button>
            </div>
        </form>
    </div>
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
    var passwordInput = document.getElementById('password');
    var eyeOpen = document.getElementById('eyeOpen');
    var eyeClosed = document.getElementById('eyeClosed');
    var loginForm = document.getElementById('loginForm');
    var loginBtn = document.getElementById('loginBtn');

    document.getElementById('togglePassword').addEventListener('click', function () {
        var isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        eyeOpen.style.display = isPassword ? '' : 'none';
        eyeClosed.style.display = isPassword ? 'none' : '';
    });

    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<svg style="width:16px;height:16px;color:#fff;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Signing In...';

        var overlay = document.createElement('div');
        overlay.setAttribute('style', 'position:fixed !important; top:0 !important; left:0 !important; right:0 !important; bottom:0 !important; width:100vw !important; height:100vh !important; z-index:99999 !important; display:flex !important; align-items:center !important; justify-content:center !important; background:rgba(14,17,36,0.55) !important; backdrop-filter:blur(6px) !important;');
        overlay.id = 'signinModal';
        overlay.innerHTML = '<div style="background:#fff;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);width:100%;max-width:380px;padding:32px;text-align:center;"><div style="width:48px;height:48px;border-radius:50%;background:#24225C;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg style="width:24px;height:24px;color:#fff;animation:spin 1s linear infinite;" fill="none" viewBox="0 0 24 24"><circle style="opacity:0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div><h3 style="font-size:18px;font-weight:700;color:#111827;">Signing In</h3><p style="font-size:14px;color:#6b7280;margin-top:4px;">Please wait...</p></div>';
        document.body.appendChild(overlay);

        setTimeout(function () { loginForm.submit(); }, 200);
    });
});
</script>
@endsection
