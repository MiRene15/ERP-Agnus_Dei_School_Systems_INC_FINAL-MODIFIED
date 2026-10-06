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
        <form action="/inquiry" method="POST">
            @csrf
            {{-- Honeypot: invisible to humans, irresistible to bots. --}}
            <div style="position: absolute; left: -9999px; top: auto; width: 1px; height: 1px; overflow: hidden;" aria-hidden="true">
                <label>Website <input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="first_name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">First Name</label>
                <input type="text" name="first_name" id="first_name" required value="{{ old('first_name') }}"
                       style="width: 100%; padding: 12px 15px; border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                @error('first_name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="last_name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Last Name</label>
                <input type="text" name="last_name" id="last_name" required value="{{ old('last_name') }}"
                       style="width: 100%; padding: 12px 15px; border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                @error('last_name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-bottom: 25px;">
                <label for="personal_email" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--primary-navy);">Personal Email Address</label>
                <input type="email" name="personal_email" id="personal_email" required value="{{ old('personal_email') }}"
                       style="width: 100%; padding: 12px 15px; border-radius: 8px; font-family: var(--font-main); font-size: 1rem; transition: var(--transition);">
                <small style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-top: 5px;">Must be Gmail, Yahoo, Proton, or Outlook.</small>
                @error('personal_email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; text-align: center; border: none; cursor: pointer;">Generate Credentials & Inquire</button>
        </form>
    </div>

    @if(session('error'))
        <div class="error-box" style="padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.95rem;">
            {{ session('error') }}
        </div>
    @endif
    @if(session('mail_failed'))
        <div class="error-box" style="padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.95rem;">
            Your account was created, but the email with your login details could not be sent. Please use the <a href="/forgot-password">Forgot Password</a> page with your personal email to set a password — do not submit another application. If that fails, contact the registrar.
        </div>
    @endif
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal-overlay hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-card">
        <button onclick="closeSuccessModal()" class="modal-close-btn">&times;</button>
        <div class="modal-icon">&#10003;</div>
        <h2 class="modal-title">Inquiry Submitted!</h2>
        <p class="modal-text">Your institutional credentials have been generated.<br>Please check your email inbox to find your login details and confirm your email address.</p>
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
    .field-error { color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block; }
    .error-box { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }

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
        background: rgba(46, 204, 113, 0.12);
        color: #27ae60; font-size: 2rem; font-weight: 700;
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

    html.dark label { color: #A39FE9; }
    html.dark input, html.dark textarea, html.dark select { background: #23274C; border-color: #3B4172; color: #E8EAF6; }
    html.dark input::placeholder, html.dark textarea::placeholder { color: #6A7094; }
    html.dark .error-box { background: rgba(248,113,113,0.12); border-color: rgba(248,113,113,0.25); color: #FCA5A5; }
    html.dark .modal-card { background: #1A1E3B; }
    html.dark .modal-title { color: #E8EAF6; }
    html.dark .modal-icon { color: #4ade80; background: rgba(74,222,128,0.12); }
    html.dark .field-error { color: #f87171; }
</style>

@if(session('success'))
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

<script>
// Safe Actions coverage sweep (spec: safe-actions-coverage-sweep.md): local
// press-lock + per-submit marker for the public inquiry form. Mirrors the
// portal's global guard, which never loads on this shell. Honeypot,
// browser validation, and the success modal flow are untouched: invalid
// forms never fire submit (no lock), navigation releases naturally.
(function () {
    var form = document.querySelector('form[action="/inquiry"]');
    if (!form || form.dataset.safeGuarded === '1') return;
    form.dataset.safeGuarded = '1';
    var RELEASE_MS = 30000;
    function release(btn) {
        try {
            btn.disabled = false;
            btn.removeAttribute('aria-disabled');
            btn.classList.remove('is-busy');
        } catch (e) { /* noop */ }
    }
    form.addEventListener('submit', function (event) {
        try {
            if (event.defaultPrevented) return;
            var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitter || submitter.disabled) return;
            submitter.disabled = true;
            submitter.setAttribute('aria-disabled', 'true');
            submitter.classList.add('is-busy');
            try {
                var keyInput = form.querySelector('input[name="_idempotency_key"]');
                if (!keyInput) {
                    keyInput = document.createElement('input');
                    keyInput.type = 'hidden';
                    keyInput.name = '_idempotency_key';
                    form.appendChild(keyInput);
                }
                keyInput.value = (window.crypto && typeof window.crypto.randomUUID === 'function')
                    ? window.crypto.randomUUID()
                    : 'key-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10);
            } catch (e) { /* noop: minting must never block a submit */ }
            window.setTimeout(function () { release(submitter); }, RELEASE_MS);
        } catch (e) {
            try {
                if (event.submitter) release(event.submitter);
            } catch (ignored) { /* noop */ }
        }
    });
    window.addEventListener('pageshow', function () {
        try {
            form.querySelectorAll('.is-busy').forEach(function (el) {
                el.classList.remove('is-busy');
                el.removeAttribute('aria-disabled');
                if ('disabled' in el) el.disabled = false;
            });
        } catch (e) { /* noop */ }
    });
})();
</script>
<style>
form[action="/inquiry"] button.is-busy { opacity: .7; cursor: wait; }
</style>

@endsection
