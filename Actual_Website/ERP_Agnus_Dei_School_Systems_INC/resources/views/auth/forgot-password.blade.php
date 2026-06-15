@extends('PromotionalWebsite.layout')

@section('content')
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Reset Password</h1>
        <p class="page-subtitle">Enter your institutional email and we'll send a reset link to your connected personal email.</p>
    </div>
</div>

<div class="container" style="max-width: 480px; margin-bottom: 100px;">
    <div class="card">
        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div style="margin-bottom: 24px;">
                <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Institutional Email</label>
                <input type="email" name="email" id="email" required autofocus value="{{ old('email') }}"
                       style="width: 100%; padding: 12px 15px; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);"
                       placeholder="you@agnusdei.edu.ph">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                <small style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-top: 6px;">The reset link will be sent to the personal email connected to your account.</small>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between;">
                <a href="{{ route('login') }}" style="font-size: 0.85rem; color: var(--primary-navy); text-decoration: underline; text-underline-offset: 3px;">
                    Back to Login
                </a>
                <button type="submit" class="btn-primary" style="border: none; cursor: pointer;">Send Reset Link</button>
            </div>
        </form>
    </div>
</div>

<div id="success-modal" class="modal-overlay hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-card">
        <button onclick="closeSuccessModal()" class="modal-close-btn">&times;</button>
        <div class="modal-icon">&#9993;</div>
        <h2 class="modal-title">Reset Link Sent!</h2>
        <p class="modal-text">Check your personal email inbox for the password reset link.<br>It may take a few minutes to arrive.</p>
        <div class="modal-timer">
            <div class="timer-bar" id="timer-bar"></div>
            <span id="timer-count">5</span>
        </div>
    </div>
</div>

<style>
    input:focus {
        outline: none;
        border-color: var(--lilac-glow) !important;
        box-shadow: 0 0 0 3px rgba(163, 159, 233, 0.2);
    }

    .modal-overlay {
        position: fixed; inset: 0; z-index: 9999;
        display: flex; align-items: center; justify-content: center;
    }
    .modal-overlay.hidden { display: none; }
    .modal-backdrop {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.35); backdrop-filter: blur(4px);
    }
    .modal-close-btn {
        position: absolute; top: 12px; right: 16px;
        background: none; border: none;
        font-size: 1.8rem; color: #94a3b8; cursor: pointer;
        transition: color 0.2s; line-height: 1;
    }
    .modal-close-btn:hover { color: var(--text-dark); }
    .modal-card {
        position: relative; z-index: 10;
        background: var(--surface-white);
        border-radius: 24px; padding: 48px 40px 36px;
        max-width: 420px; width: 90%;
        text-align: center;
        box-shadow: 0 25px 60px rgba(0,0,0,0.15);
        animation: popIn 0.4s cubic-bezier(0.16,1,0.3,1);
    }
    @keyframes popIn {
        from { opacity: 0; transform: scale(0.9) translateY(20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .modal-icon {
        width: 64px; height: 64px; border-radius: 50%;
        background: rgba(99, 102, 241, 0.12);
        color: #4f46e5; font-size: 1.8rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 20px;
    }
    .modal-title {
        font-size: 1.5rem; font-weight: 800; color: var(--primary-navy);
        margin-bottom: 12px;
    }
    .modal-text {
        font-size: 1rem; color: var(--text-muted); line-height: 1.6;
        margin-bottom: 24px;
    }
    .modal-timer {
        display: flex; align-items: center; gap: 10px; justify-content: center;
    }
    .timer-bar {
        width: 120px; height: 4px; background: #e2e8f0; border-radius: 4px; overflow: hidden;
        position: relative;
    }
    .timer-bar::after {
        content: ''; position: absolute; inset: 0;
        background: var(--primary-navy);
        animation: shrink 5s linear forwards;
        transform-origin: left;
    }
    @keyframes shrink {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
    #timer-count {
        font-size: 0.85rem; font-weight: 700; color: var(--text-muted);
        min-width: 20px;
    }
</style>

@if(session('status'))
<script>
    var modal = document.getElementById('success-modal');
    var timerInterval, redirectTimer;

    function closeSuccessModal() {
        if (timerInterval) clearInterval(timerInterval);
        if (redirectTimer) clearTimeout(redirectTimer);
        modal.style.transition = 'opacity 0.3s ease';
        modal.style.opacity = '0';
        setTimeout(function() { modal.classList.add('hidden'); modal.style.opacity = '1'; }, 300);
    }

    (function() {
        modal.classList.remove('hidden');
        var count = 5;
        var el = document.getElementById('timer-count');
        timerInterval = setInterval(function() {
            count--;
            el.textContent = count;
            if (count <= 0) {
                clearInterval(timerInterval);
                modal.style.transition = 'opacity 0.5s ease';
                modal.style.opacity = '0';
                redirectTimer = setTimeout(function() { window.location.href = '/login'; }, 500);
            }
        }, 1000);
    })();
</script>
@endif
@endsection
