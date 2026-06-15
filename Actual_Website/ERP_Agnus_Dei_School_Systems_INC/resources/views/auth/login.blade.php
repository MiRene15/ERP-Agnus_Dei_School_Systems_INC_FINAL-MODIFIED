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
                <input type="password" name="password" id="password" required autocomplete="current-password"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
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
@endsection
