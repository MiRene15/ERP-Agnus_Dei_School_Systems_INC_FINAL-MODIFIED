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
                <small style="color: var(--text-muted); font-size: 0.85rem; display: block; margin-top: 5px;">Must be Gmail, Yahoo, Proton, or Outlook.</small>
                @error('personal_email')
                    <span style="color: #e74c3c; font-size: 0.85rem; margin-top: 5px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; text-align: center; border: none; cursor: pointer;">Generate Credentials & Inquire</button>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div id="success-modal" class="modal-overlay hidden">
    <div class="modal-backdrop"></div>
    <div class="modal-card">
        <button onclick="closeSuccessModal()" class="modal-close-btn">&times;</button>
        <div class="modal-icon">&#10003;</div>
        <h2 class="modal-title">Inquiry Submitted!</h2>
        <p class="modal-text">Your institutional credentials have been generated.<br>Please check your email inbox to find your login details.</p>
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

@endsection
